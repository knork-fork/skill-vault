<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SettingsController extends AbstractController
{
    #[Route(path: '/settings', name: 'app_settings', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): Response
    {
        return $this->render('settings/index.html.twig', [
            'user' => $user,
        ]);
    }
}
