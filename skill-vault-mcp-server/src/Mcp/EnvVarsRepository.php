<?php

declare(strict_types=1);

namespace App\Mcp;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Fetches a user's saved environment variables (placeholder => value) from skill-vault-ui's
 * internal endpoint, authenticated like EnabledSkillsRepository.
 */
final class EnvVarsRepository
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(param: 'app.skill_vault_ui_internal_url')]
        private readonly string $skillVaultUiInternalUrl,
        #[Autowire(param: 'app.oauth_introspection_shared_secret')]
        private readonly string $sharedSecret,
    ) {
    }

    /**
     * Returns no variables on any failure, so skills are served with their placeholders intact.
     *
     * @return array<string, string>
     */
    public function findAllForUser(int $userId): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->skillVaultUiInternalUrl . '/internal/env-vars', [
                'headers' => ['X-Internal-Secret' => $this->sharedSecret],
                'query' => ['user_id' => $userId],
            ]);
            $data = $response->toArray(false);
        } catch (Throwable) {
            return [];
        }

        $values = [];
        foreach (\is_array($data['env_vars'] ?? null) ? $data['env_vars'] : [] as $name => $value) {
            if (\is_string($value)) {
                $values[(string) $name] = $value;
            }
        }

        return $values;
    }
}
