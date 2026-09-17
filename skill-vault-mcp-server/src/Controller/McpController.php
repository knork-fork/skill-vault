<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mcp\McpRequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Streamable HTTP MCP transport (single JSON response per request, no SSE,
 * no session id) per docs/Skills_vs_Tools.md. Auth is not implemented yet:
 * every request sees every skill/tool under resources/.
 */
final class McpController
{
    public function __construct(
        private readonly McpRequestHandler $handler,
    ) {
    }

    #[Route('/mcp', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload) || array_is_list($payload)) {
            return new JsonResponse([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => ['code' => -32700, 'message' => 'Parse error.'],
            ], 400);
        }

        /** @var array<string, mixed> $payload */
        $response = $this->handler->handle($payload);
        if ($response === null) {
            return new Response('', 202);
        }

        return new JsonResponse($response);
    }
}
