<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Skill\PlaceholderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class PlaceholderController extends AbstractController
{
    public function __construct(private readonly PlaceholderService $placeholders)
    {
    }

    /**
     * Called by the app shell on every page load to decide whether to flag the Skills nav item.
     */
    #[Route(path: '/api/placeholders/missing', name: 'app_placeholders_missing', methods: ['GET'])]
    public function missing(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse(['missing' => (object) $this->placeholders->missingForEnabledSkills($user)]);
    }
}
