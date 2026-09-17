<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Skill\SkillAccessService;
use App\Skill\SkillFileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Internal, service-to-service endpoints called by other containers (e.g. skill-vault-mcp-server)
 * rather than by a logged-in browser session. Authenticated the same way as
 * OAuthController::introspect(): a shared secret in the X-Internal-Secret header, since the
 * caller isn't a Skill Vault user itself.
 */
final class InternalSkillsController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly SkillFileRepository $skills,
        private readonly SkillAccessService $skillAccess,
        #[Autowire(param: 'app.oauth_introspection_shared_secret')]
        private readonly string $internalSharedSecret,
    ) {
    }

    #[Route(path: '/internal/skills/enabled', name: 'app_internal_skills_enabled', methods: ['GET'])]
    public function enabledSkills(Request $request): JsonResponse
    {
        $providedSecret = $request->headers->get('X-Internal-Secret') ?? '';
        if ($this->internalSharedSecret === '' || !hash_equals($this->internalSharedSecret, $providedSecret)) {
            return new JsonResponse(['error' => 'forbidden'], 403);
        }

        $userId = $request->query->getInt('user_id');
        $user = $userId > 0 ? $this->users->find($userId) : null;
        if ($user === null) {
            return new JsonResponse(['error' => 'user_not_found'], 404);
        }

        $context = $this->skillAccess->contextForUser($user);

        $enabledSkills = array_values(array_filter(
            $this->skills->findAll(),
            static fn (array $skill): bool => $context->isSkillEnabled(
                \is_string($skill['slug']) ? $skill['slug'] : '',
                \is_string($skill['group'] ?? null) ? $skill['group'] : null,
            ),
        ));

        $skills = array_map(
            static fn (array $skill): array => [
                'slug' => $skill['slug'],
                'name' => $skill['name'],
                'description' => $skill['description'],
                'group' => $skill['group'],
                'icon' => $skill['icon'],
                'color' => $skill['color'],
                'requires' => $skill['requires'],
                'content' => $skill['content'],
            ],
            $enabledSkills,
        );

        return new JsonResponse(['skills' => $skills]);
    }
}
