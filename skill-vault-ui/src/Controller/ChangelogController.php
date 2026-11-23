<?php

declare(strict_types=1);

namespace App\Controller;

use App\Changelog\ChangelogService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChangelogController extends AbstractController
{
    #[Route(path: '/whats-new/seen', name: 'app_whats_new_seen', methods: ['POST'])]
    public function seen(
        #[CurrentUser]
        User $user,
        Request $request,
        ChangelogService $changelog,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('whats_new_seen', $request->request->getString('_token'))) {
            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        $changelog->markSeen($user);
        $entityManager->flush();

        return new JsonResponse(['ok' => true]);
    }
}
