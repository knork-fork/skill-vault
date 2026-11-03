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
 * In routed exposure mode (the user's `exposure_setting`) individual skills are not listed
 * or callable. Instead a `gc-vault` MCP prompt (user-invoked as `/gc-vault <request>`) hands
 * Claude the request plus a lightweight index of the user's enabled skills, and Claude then
 * calls the `gc_vault` tool with the chosen slug to load that skill's full instructions
 * (access is re-checked on that call). Executable tools stay listed in both modes.
 *
 * Skills are scoped to the authenticated caller's enabled set (see
 * EnabledSkillsRepository). Tools have no per-user enablement concept yet, so
 * every tool under resources/ is still exposed to every authenticated caller.
 */
final class McpRequestHandler
{
    private const PROTOCOL_VERSION = '2025-06-18';
    private const ROUTER_PROMPT = 'gc-vault';
    private const ROUTER_TOOL = 'gc_vault';

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
                'prompts/list' => $this->listPrompts($identity),
                'prompts/get' => $this->getPrompt($params, $identity),
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
                'prompts' => ['listChanged' => false],
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
        $enabled = $this->skills->fetchForUser($identity->userId);

        $skillTools = array_map(
            static fn (array $skill): array => [
                'name' => $skill['slug'],
                'title' => $skill['name'],
                'description' => $skill['description'],
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
            $enabled['skills'],
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

        if ($enabled['exposureSetting'] === EnabledSkillsRepository::ROUTED_EXPOSURE) {
            $skillTools = [[
                'name' => self::ROUTER_TOOL,
                'title' => 'Skill Vault router',
                'description' => 'Load the full instructions of a Skill Vault skill by slug, as chosen from the index provided by the gc-vault prompt.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => ['slug' => ['type' => 'string', 'description' => 'Slug of the skill to load.']],
                    'required' => ['slug'],
                ],
            ]];
        }

        return ['tools' => [...$skillTools, ...$tools]];
    }

    /**
     * @return array<string, mixed>
     */
    private function listPrompts(TokenIdentity $identity): array
    {
        if (!$this->isRouted($identity)) {
            return ['prompts' => []];
        }

        return ['prompts' => [[
            'name' => self::ROUTER_PROMPT,
            'title' => 'Skill Vault router',
            'description' => 'Pick and run the most relevant Skill Vault skill for a free-text request.',
            'arguments' => [[
                'name' => 'request',
                'description' => 'What you want to do, in plain language.',
                'required' => true,
            ]],
        ]]];
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function getPrompt(array $params, TokenIdentity $identity): array
    {
        $name = \is_string($params['name'] ?? null) ? $params['name'] : '';
        $enabled = $this->skills->fetchForUser($identity->userId);
        if ($name !== self::ROUTER_PROMPT || $enabled['exposureSetting'] !== EnabledSkillsRepository::ROUTED_EXPOSURE) {
            throw new McpInvalidParamsException(\sprintf('Unknown prompt "%s".', $name));
        }

        $arguments = \is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
        $request = \is_string($arguments['request'] ?? null) ? $arguments['request'] : '';

        // Routing metadata only: never the skill content.
        $index = json_encode(array_map(
            static fn (array $skill): array => [
                'slug' => $skill['slug'],
                'name' => $skill['name'],
                'description' => $skill['description'],
            ],
            $enabled['skills'],
        ), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);

        $text = <<<TEXT
            You are the Skill Vault router. Choose the single skill from the index below that best matches the user's request, comparing the request to each skill's description.

            If no skill clearly matches, tell the user and ask which one to use (or list the closest candidates). Otherwise call the `gc_vault` tool with the chosen skill's `slug`, then follow the full instructions it returns. If those instructions reference other MCP tools, call them normally.

            User request:
            {$request}

            Skill index:
            {$index}
            TEXT;

        return [
            'description' => 'Skill Vault router',
            'messages' => [[
                'role' => 'user',
                'content' => ['type' => 'text', 'text' => $text],
            ]],
        ];
    }

    private function isRouted(TokenIdentity $identity): bool
    {
        return $this->skills->fetchForUser($identity->userId)['exposureSetting'] === EnabledSkillsRepository::ROUTED_EXPOSURE;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function callTool(array $params, TokenIdentity $identity): array
    {
        $name = \is_string($params['name'] ?? null) ? $params['name'] : '';

        // Fetched fresh on every call so permissions are re-validated at execution time.
        $enabled = $this->skills->fetchForUser($identity->userId);
        $routed = $enabled['exposureSetting'] === EnabledSkillsRepository::ROUTED_EXPOSURE;

        if ($routed && $name === self::ROUTER_TOOL) {
            $arguments = \is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
            $slug = \is_string($arguments['slug'] ?? null) ? $arguments['slug'] : '';
            $skill = $this->findSkill($enabled['skills'], $slug);
            if ($skill === null) {
                return [
                    'isError' => true,
                    'content' => [
                        ['type' => 'text', 'text' => \sprintf('Skill "%s" does not exist or is not available to you.', $slug)],
                    ],
                ];
            }

            return ['content' => [['type' => 'text', 'text' => $skill['content']]]];
        }

        $skill = $routed ? null : $this->findSkill($enabled['skills'], $name);
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
     * @param list<array{slug: string, name: string, description: string, requires: list<string>, content: string}> $skills
     *
     * @return array{slug: string, name: string, description: string, requires: list<string>, content: string}|null
     */
    private function findSkill(array $skills, string $slug): ?array
    {
        foreach ($skills as $skill) {
            if ($skill['slug'] === $slug) {
                return $skill;
            }
        }

        return null;
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
