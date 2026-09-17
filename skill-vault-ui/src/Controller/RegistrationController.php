<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'app.invite_secret')]
        private readonly string $inviteSecret,
    ) {
    }

    #[Route(path: '/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        if ($this->inviteSecret === 'disabled') {
            return $this->render('security/signup_unavailable.html.twig', [
                'message' => 'Signup is currently closed.',
            ], new Response(status: 404));
        }

        if ($this->inviteSecret !== '' && $request->query->get('invite') !== $this->inviteSecret) {
            return $this->render('security/signup_unavailable.html.twig', [
                'message' => 'A valid invite link is required to sign up.',
            ], new Response(status: 403));
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (!\is_string($plainPassword)) {
                throw new LogicException('Expected plainPassword field to submit a string.');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $security->login($user);

            return new RedirectResponse($this->generateUrl('app_home'));
        }

        return $this->render('security/signup.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
