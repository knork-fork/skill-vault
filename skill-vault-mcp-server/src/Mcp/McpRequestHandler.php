<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Security\TokenIdentity;
use stdClass;

/**
 * Handles a single decoded MCP JSON-RPC 2.0 request and returns the JSON-RPC
 * response payload, or null for notifications (which get no response).
 *
 * Skills and tools are both exposed as MCP tools (direct exposure mode, per
 * docs/Skills_vs_Tools.md): MCP prompts are user-invoked, not model-invoked,
 * so a skill exposed as a prompt is never picked up automatically. Calling a
 * skill by name returns its instructions; calling a tool by name is meant to
 * run its backend logic.
 *
 * Skills are scoped to the authenticated caller's enabled set (see
 * EnabledSkillsRepository). Tools have no per-user enablement concept yet, so
 * every tool under resources/ is still exposed to every authenticated caller.
 */
final class McpRequestHandler
{
    private const PROTOCOL_VERSION = '2025-06-18';

    public function __construct(
        private readonly EnabledSkillsRepository $skills,
        private readonly ToolFileRepository $tools,
    ) {
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return array<string, mixed>|null
     */
    public function handle(array $request, TokenIdentity $identity): ?array
    {
        $id = $request['id'] ?? null;
        $isNotification = !\array_key_exists('id', $request);
        $method = \is_string($request['method'] ?? null) ? $request['method'] : '';
        $params = \is_array($request['params'] ?? null) ? $request['params'] : [];

        if ($isNotification) {
            // Client notifications (e.g. notifications/initialized) require no response.
            return null;
        }

        try {
            $result = match ($method) {
                'initialize' => $this->initialize(),
                'ping' => new stdClass(),
                'tools/list' => $this->listTools($identity),
                'tools/call' => $this->callTool($params, $identity),
                default => throw new McpMethodNotFoundException($method),
            };
        } catch (McpMethodNotFoundException $e) {
            return $this->errorResponse($id, -32601, $e->getMessage());
        } catch (McpInvalidParamsException $e) {
            return $this->errorResponse($id, -32602, $e->getMessage());
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function initialize(): array
    {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => ['listChanged' => false],
            ],
            'serverInfo' => [
                'name' => 'skill-vault-mcp-server',
                'version' => '0.1.0',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listTools(TokenIdentity $identity): array
    {
        // MCP tool `name` must be a bare identifier (Claude.ai enforces this strictly and
        // silently drops any tool whose name fails; Claude Code is more lenient), so the
        // wire name is the filesystem slug, with the human-readable skill/tool name kept
        // as `title` (protocol 2025-06-18 added `title` on Tool for exactly this split).
        $skillTools = array_map(
            static fn (array $skill): array => [
                'name' => $skill['slug'],
                'title' => $skill['name'],
                'description' => $skill['description'],
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
            $this->skills->findAllForUser($identity->userId),
        );

        $tools = array_map(
            static fn (array $tool): array => [
                'name' => $tool['slug'],
                'title' => $tool['name'],
                'description' => $tool['description'],
                'inputSchema' => $tool['inputSchema'],
            ],
            $this->tools->findAll(),
        );

        return ['tools' => [...$skillTools, ...$tools]];
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function callTool(array $params, TokenIdentity $identity): array
    {
        $name = \is_string($params['name'] ?? null) ? $params['name'] : '';

        $skill = $this->skills->findBySlugForUser($name, $identity->userId);
        if ($skill !== null) {
            return [
                'content' => [
                    ['type' => 'text', 'text' => $skill['content']],
                ],
            ];
        }

        $tool = $this->tools->findBySlug($name);
        if ($tool !== null) {
            // Tools are currently only listed, not executed: no backend service is
            // wired up yet to fulfil a tools/call request.
            return [
                'isError' => true,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => \sprintf('Tool "%s" is registered but has no execution backend yet.', $name),
                    ],
                ],
            ];
        }

        throw new McpInvalidParamsException(\sprintf('Unknown tool "%s".', $name));
    }

    /**
     * @return array<string, mixed>
     */
    private function errorResponse(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
