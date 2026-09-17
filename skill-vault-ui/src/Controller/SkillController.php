<?php

declare(strict_types=1);

namespace App\Controller;

use App\Avatar\AvatarPalette;
use App\Entity\SkillGroup;
use App\Entity\User;
use App\Repository\SkillGroupRepository;
use App\Skill\SkillAccessService;
use App\Skill\SkillFileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SkillController extends AbstractController
{
    public function __construct(
        private readonly SkillFileRepository $skills,
        private readonly SkillGroupRepository $skillGroups,
        private readonly SkillAccessService $skillAccess,
    ) {
    }

    /**
     * @return list<string>
     */
    private function groupNames(): array
    {
        return array_map(
            static fn (SkillGroup $group): string => $group->getName(),
            $this->skillGroups->findAll(),
        );
    }

    #[Route(path: '/skills', name: 'app_skills', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $context = $this->skillAccess->contextForUser($user);
        $skills = array_map(
            static function (array $skill) use ($context): array {
                $skill['enabled'] = $context->isSkillEnabled(
                    \is_string($skill['slug']) ? $skill['slug'] : '',
                    \is_string($skill['group'] ?? null) ? $skill['group'] : null,
                );

                return $skill;
            },
            $this->skills->findAll(),
        );

        return $this->render('skills/index.html.twig', [
            'user' => $user,
            'skills' => $skills,
            'groupNames' => $this->groupNames(),
        ]);
    }

    #[Route(path: '/skills/new', name: 'app_skills_new', methods: ['GET'])]
    public function new(#[CurrentUser] User $user): Response
    {
        return $this->render('skills/new.html.twig', [
            'user' => $user,
            'groupNames' => $this->groupNames(),
            'iconOptions' => AvatarPalette::ICONS,
            'colorOptions' => AvatarPalette::COLORS,
        ]);
    }

    #[Route(path: '/skills', name: 'app_skills_create', methods: ['POST'])]
    public function create(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('create_skill', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $name = trim($request->request->getString('name'));
        if ($name === '') {
            return $this->redirectToRoute('app_skills_new');
        }

        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', trim($name)));
        $slug = trim($slug, '_') ?: 'skill';

        $group = trim($request->request->getString('group'));
        if ($group !== '' && $this->skillGroups->findOneByName($group) === null) {
            $group = '';
        }

        $this->skills->save($slug, [
            'name' => $name,
            'description' => trim($request->request->getString('description')),
            'group' => $group !== '' ? $group : null,
            'icon' => $request->request->getString('icon', 'folder'),
            'color' => $request->request->getString('color', 'blue'),
            'content' => $request->request->getString('content'),
        ]);

        return $this->redirectToRoute('app_skill', ['slug' => $slug]);
    }

    #[Route(path: '/skills/{slug}', name: 'app_skill', methods: ['GET'])]
    public function show(#[CurrentUser] User $user, string $slug): Response
    {
        $skill = $this->skills->findBySlug($slug);
        if ($skill === null) {
            throw $this->createNotFoundException('Skill not found.');
        }

        return $this->render('skills/show.html.twig', [
            'user' => $user,
            'skill' => $skill,
            'groupNames' => $this->groupNames(),
        ]);
    }

    #[Route(path: '/skills/{slug}/edit', name: 'app_skill_edit', methods: ['GET'])]
    public function edit(#[CurrentUser] User $user, string $slug): Response
    {
        $skill = $this->skills->findBySlug($slug);
        if ($skill === null) {
            throw $this->createNotFoundException('Skill not found.');
        }

        return $this->render('skills/edit.html.twig', [
            'user' => $user,
            'skill' => $skill,
            'groupNames' => $this->groupNames(),
            'iconOptions' => AvatarPalette::ICONS,
            'colorOptions' => AvatarPalette::COLORS,
        ]);
    }

    #[Route(path: '/skills/{slug}/edit', name: 'app_skill_update', methods: ['POST'])]
    public function update(Request $request, string $slug): RedirectResponse
    {
        if ($this->skills->findBySlug($slug) === null) {
            throw $this->createNotFoundException('Skill not found.');
        }

        if (!$this->isCsrfTokenValid('edit_skill', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $name = trim($request->request->getString('name'));
        if ($name === '') {
            return $this->redirectToRoute('app_skill_edit', ['slug' => $slug]);
        }

        $group = trim($request->request->getString('group'));
        if ($group !== '' && $this->skillGroups->findOneByName($group) === null) {
            $group = '';
        }

        $this->skills->save($slug, [
            'name' => $name,
            'description' => trim($request->request->getString('description')),
            'group' => $group !== '' ? $group : null,
            'icon' => $request->request->getString('icon', 'folder'),
            'color' => $request->request->getString('color', 'blue'),
            'content' => $request->request->getString('content'),
        ]);

        return $this->redirectToRoute('app_skill', ['slug' => $slug]);
    }

    #[Route(path: '/skills/{slug}/state', name: 'app_skill_set_state', methods: ['POST'])]
    public function setState(#[CurrentUser] User $user, Request $request, string $slug): JsonResponse
    {
        if (!$this->isCsrfTokenValid('set_skill_state', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $skill = $this->skills->findBySlug($slug);
        if ($skill === null) {
            throw $this->createNotFoundException('Skill not found.');
        }

        $groupName = \is_string($skill['group'] ?? null) ? $skill['group'] : null;
        $group = $groupName !== null ? $this->skillGroups->findOneByName($groupName) : null;

        $siblingSlugs = $groupName !== null
            ? array_values(array_map(
                static fn (array $s): string => \is_string($s['slug']) ? $s['slug'] : '',
                array_filter(
                    $this->skills->findAll(),
                    static fn (array $s): bool => $s['group'] === $groupName,
                ),
            ))
            : [];

        $this->skillAccess->setSkillEnabled($user, $slug, $request->request->getBoolean('enabled'), $group, $siblingSlugs);

        return new JsonResponse(['slug' => $slug, 'enabled' => $request->request->getBoolean('enabled')]);
    }

    #[Route(path: '/skills/{slug}/delete', name: 'app_skill_delete', methods: ['POST'])]
    public function delete(Request $request, string $slug): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_skill', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->skills->delete($slug);

        $redirect = $request->request->getString('redirect');

        return $this->redirectToRoute($redirect === 'skill_groups' ? 'app_skill_groups' : 'app_skills');
    }
}
