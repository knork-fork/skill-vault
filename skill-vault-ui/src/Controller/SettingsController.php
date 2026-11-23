<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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

    #[Route(path: '/settings/user', name: 'app_settings_user', methods: ['GET', 'POST'])]
    public function user(
        #[CurrentUser]
        User $user,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            if (!\is_string($newPassword)) {
                throw new LogicException('Expected newPassword field to submit a string.');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $entityManager->flush();

            $this->addFlash('success', 'Your password has been updated.');

            return new RedirectResponse($this->generateUrl('app_settings_user'));
        }

        return $this->render('settings/user.html.twig', [
            'user' => $user,
            'passwordForm' => $form,
        ]);
    }
}
