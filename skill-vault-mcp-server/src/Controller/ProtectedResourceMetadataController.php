<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * OAuth 2.0 Protected Resource Metadata (RFC 9728) for the MCP transport,
 * pointing MCP clients at skill-vault-ui as the authorization server.
 */
final class ProtectedResourceMetadataController
{
    public function __construct(
        #[Autowire(param: 'app.skill_vault_ui_url')]
        private readonly string $skillVaultUiUrl,
    ) {
    }

    #[Route('/.well-known/oauth-protected-resource', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse([
            'resource' => $request->getSchemeAndHttpHost() . '/mcp',
            'authorization_servers' => [$this->skillVaultUiUrl],
        ]);
    }
}
