<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class IntegrationController extends AbstractController
{
    #[Route(path: '/integrations', name: 'app_integrations', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): Response
    {
        $integrations = [
            [
                'key' => 'trello',
                'name' => 'Trello',
                'connected' => true,
                'account' => $user->getEmail(),
                'description' => 'Access your boards, tasks, and project data.',
            ],
            [
                'key' => 'google_drive',
                'name' => 'Google Drive',
                'connected' => true,
                'account' => $user->getEmail(),
                'description' => 'Access your files, documents, and drive content.',
            ],
        ];

        return $this->render('integrations/index.html.twig', [
            'user' => $user,
            'integrations' => $integrations,
            'exposureMode' => 'direct',
        ]);
    }
}
