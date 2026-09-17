<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mcp\McpRequestHandler;
use App\Security\TokenIdentity;
use App\Security\TokenIntrospectionClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Streamable HTTP MCP transport (single JSON response per request, no SSE,
 * no session id) per docs/Skills_vs_Tools.md. Every request must carry a
 * bearer token issued by skill-vault-ui's OAuth server; the resolved identity
 * is passed to McpRequestHandler so skill listing/execution is scoped to
 * that user's enabled skills.
 */
final class McpController
{
    public function __construct(
        private readonly McpRequestHandler $handler,
        private readonly TokenIntrospectionClient $introspectionClient,
    ) {
    }

    #[Route('/mcp', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $identity = $this->authenticate($request);
        if ($identity === null) {
            return $this->unauthorizedResponse($request);
        }

        $request->attributes->set('vaultUser', $identity);

        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload) || array_is_list($payload)) {
            return new JsonResponse([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => ['code' => -32700, 'message' => 'Parse error.'],
            ], 400);
        }

        /** @var array<string, mixed> $payload */
        $response = $this->handler->handle($payload, $identity);
        if ($response === null) {
            return new Response('', 202);
        }

        return new JsonResponse($response);
    }

    private function authenticate(Request $request): ?TokenIdentity
    {
        $header = $request->headers->get('Authorization') ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return $this->introspectionClient->introspect(substr($header, 7));
    }

    private function unauthorizedResponse(Request $request): JsonResponse
    {
        $metadataUrl = $request->getSchemeAndHttpHost() . '/.well-known/oauth-protected-resource';

        return new JsonResponse(
            ['error' => 'unauthorized'],
            401,
            ['WWW-Authenticate' => \sprintf('Bearer resource_metadata="%s"', $metadataUrl)],
        );
    }
}
