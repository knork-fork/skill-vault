<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class IntegrationController extends AbstractController
{
    private const EXPOSURE_SETTINGS = ['direct_exposure', 'routed_exposure'];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/integrations', name: 'app_integrations', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): Response
    {
        $integrations = [
            [
                'key' => 'trello',
                'name' => 'Trello',
                'connected' => false,
                'account' => $user->getEmail(),
                'description' => 'Access your boards, tasks, and project data.',
            ],
            [
                'key' => 'google_drive',
                'name' => 'Google Drive',
                'connected' => false,
                'account' => $user->getEmail(),
                'description' => 'Access your files, documents, and drive content.',
            ],
        ];

        return $this->render('integrations/index.html.twig', [
            'user' => $user,
            'integrations' => $integrations,
            'exposureMode' => $user->getExposureSetting(),
        ]);
    }

    #[Route(path: '/integrations/exposure', name: 'app_integrations_set_exposure', methods: ['POST'])]
    public function setExposure(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('set_exposure_setting', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $mode = $request->request->getString('mode');
        if (!\in_array($mode, self::EXPOSURE_SETTINGS, true)) {
            return new JsonResponse(['error' => 'Invalid exposure setting.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setExposureSetting($mode);
        $this->entityManager->flush();

        return new JsonResponse(['mode' => $mode]);
    }
}
