<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserEnvVar;
use App\Repository\UserEnvVarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class EnvVarController extends AbstractController
{
    private const PLACEHOLDER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function __construct(
        private readonly UserEnvVarRepository $envVars,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(path: '/settings/environment-variables', name: 'app_settings_env_vars', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('settings/env_vars.html.twig', [
            'user' => $user,
            'envVars' => $this->envVars->findAllByUser($user),
        ]);
    }

    #[Route(path: '/settings/environment-variables', name: 'app_settings_env_vars_add', methods: ['POST'])]
    public function add(#[CurrentUser] User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('add_env_var', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid form token. Please try again.');

            return $this->back();
        }

        $placeholder = trim($request->request->getString('placeholder'));
        $value = $request->request->getString('value');

        if ($placeholder === '' || \strlen($placeholder) > 255 || preg_match(self::PLACEHOLDER_PATTERN, $placeholder) !== 1) {
            $this->addFlash('error', 'Placeholder must start with a letter or underscore and contain only letters, digits and underscores.');
        } elseif ($value === '') {
            $this->addFlash('error', 'Value must not be empty.');
        } elseif ($this->envVars->findOneByUserAndPlaceholder($user, $placeholder) !== null) {
            $this->addFlash('error', \sprintf('A variable named "%s" already exists.', $placeholder));
        } else {
            $this->entityManager->persist(new UserEnvVar($user, $placeholder, $value));
            $this->entityManager->flush();
        }

        return $this->back();
    }

    #[Route(path: '/settings/environment-variables/{id}/delete', name: 'app_settings_env_vars_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(#[CurrentUser] User $user, Request $request, int $id): Response
    {
        if (!$this->isCsrfTokenValid('delete_env_var', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Invalid form token. Please try again.');

            return $this->back();
        }

        $envVar = $this->envVars->findOneByUserAndId($user, $id);
        if ($envVar !== null) {
            $this->entityManager->remove($envVar);
            $this->entityManager->flush();
        }

        return $this->back();
    }

    private function back(): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_settings_env_vars'));
    }
}
