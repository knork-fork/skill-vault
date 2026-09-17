<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Validates bearer tokens by calling skill-vault-ui's POST /oauth/introspect
 * over the container-internal network. This service holds no OAuth data of
 * its own — skill-vault-ui is the sole owner of users and tokens.
 */
final class TokenIntrospectionClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(param: 'app.skill_vault_ui_internal_url')]
        private readonly string $introspectionBaseUrl,
        #[Autowire(param: 'app.oauth_introspection_shared_secret')]
        private readonly string $sharedSecret,
    ) {
    }

    public function introspect(string $bearerToken): ?TokenIdentity
    {
        try {
            $response = $this->httpClient->request('POST', $this->introspectionBaseUrl . '/oauth/introspect', [
                'headers' => ['X-Internal-Secret' => $this->sharedSecret],
                'json' => ['token' => $bearerToken],
            ]);

            $data = $response->toArray(false);
        } catch (Throwable) {
            return null;
        }

        $userId = $data['user_id'] ?? null;
        $username = $data['username'] ?? null;

        if (($data['active'] ?? false) !== true || !\is_int($userId) || !\is_string($username)) {
            return null;
        }

        return new TokenIdentity($userId, $username);
    }
}
