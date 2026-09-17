<?php

declare(strict_types=1);

namespace App\Mcp;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Fetches a user's currently-enabled skills from skill-vault-ui's internal endpoint,
 * rather than reading resources/skills directly — skill-vault-ui is the sole owner of
 * per-user enablement state (its SkillAccessService), so this never sees a skill the
 * user hasn't been granted, and a disabled skill simply isn't in the response.
 *
 * Authenticated the same way as TokenIntrospectionClient: a shared secret in the
 * X-Internal-Secret header, since this is a server-to-server call, not a user request.
 */
final class EnabledSkillsRepository
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
     * @return list<array{slug: string, name: string, description: string, requires: list<string>, content: string}>
     */
    public function findAllForUser(int $userId): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->skillVaultUiInternalUrl . '/internal/skills/enabled', [
                'headers' => ['X-Internal-Secret' => $this->sharedSecret],
                'query' => ['user_id' => $userId],
            ]);
            $data = $response->toArray(false);
        } catch (Throwable) {
            // Fail closed: if skill-vault-ui can't be reached or rejects the call, the
            // caller sees no skills rather than falling back to everything on disk.
            return [];
        }

        $skills = \is_array($data['skills'] ?? null) ? $data['skills'] : [];

        $result = [];
        foreach ($skills as $skill) {
            if (!\is_array($skill) || !\is_string($skill['slug'] ?? null) || !\is_string($skill['name'] ?? null)) {
                continue;
            }

            $requires = [];
            foreach (\is_array($skill['requires'] ?? null) ? $skill['requires'] : [] as $tool) {
                if (\is_string($tool)) {
                    $requires[] = $tool;
                }
            }

            $result[] = [
                'slug' => $skill['slug'],
                'name' => $skill['name'],
                'description' => \is_string($skill['description'] ?? null) ? $skill['description'] : '',
                'requires' => $requires,
                'content' => \is_string($skill['content'] ?? null) ? $skill['content'] : '',
            ];
        }

        return $result;
    }

    /**
     * @return array{slug: string, name: string, description: string, requires: list<string>, content: string}|null
     */
    public function findByNameForUser(string $name, int $userId): ?array
    {
        foreach ($this->findAllForUser($userId) as $skill) {
            if ($skill['name'] === $name) {
                return $skill;
            }
        }

        return null;
    }
}
