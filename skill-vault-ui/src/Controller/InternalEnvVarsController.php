<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserEnvVarRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Internal, service-to-service endpoint (see InternalSkillsController) that lets
 * skill-vault-mcp-server load a user's environment variables to fill skill placeholders.
 */
final class InternalEnvVarsController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserEnvVarRepository $envVars,
        #[Autowire(param: 'app.oauth_introspection_shared_secret')]
        private readonly string $internalSharedSecret,
    ) {
    }

    #[Route(path: '/internal/env-vars', name: 'app_internal_env_vars', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
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

        $values = [];
        foreach ($this->envVars->findAllByUser($user) as $envVar) {
            $values[$envVar->getPlaceholder()] = $envVar->getValue();
        }

        return new JsonResponse(['env_vars' => (object) $values]);
    }
}
