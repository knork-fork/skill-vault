<?php

declare(strict_types=1);

namespace App\Controller;

use App\Avatar\AvatarPalette;
use App\Entity\SkillGroup;
use App\Entity\User;
use App\Repository\SkillGroupRepository;
use App\Skill\SkillFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SkillGroupController extends AbstractController
{
    public function __construct(
        private readonly SkillGroupRepository $skillGroups,
        private readonly SkillFileRepository $skills,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(path: '/skill-groups', name: 'app_skill_groups', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $skills = $this->skills->findAll();

        $groups = array_map(
            static function (SkillGroup $group) use ($skills): array {
                $groupSkills = array_values(array_filter(
                    $skills,
                    static fn (array $skill): bool => $skill['group'] === $group->getName(),
                ));

                return [
                    'name' => $group->getName(),
                    'description' => $group->getDescription(),
                    'icon' => $group->getIcon(),
                    'color' => $group->getColor(),
                    'skillCount' => \count($groupSkills),
                    'skills' => $groupSkills,
                    // Access control and per-group enablement aren't implemented yet — every
                    // group is displayed as public/editable/enabled for now.
                    'access' => ['type' => 'public', 'label' => 'Public', 'scope' => 'All users (Writable)'],
                    'enabled' => true,
                ];
            },
            $this->skillGroups->findAll(),
        );

        $ungroupedSkills = array_values(array_filter(
            $skills,
            static fn (array $skill): bool => $skill['group'] === null,
        ));

        return $this->render('skill_groups/index.html.twig', [
            'user' => $user,
            'groups' => $groups,
            'ungroupedSkills' => $ungroupedSkills,
            'iconOptions' => AvatarPalette::ICONS,
            'colorOptions' => AvatarPalette::COLORS,
        ]);
    }

    #[Route(path: '/skill-groups', name: 'app_skill_groups_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('create_group', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $name = trim($request->request->getString('name'));
        if ($name === '' || $this->skillGroups->findOneByName($name) !== null) {
            return $this->redirectToRoute('app_skill_groups');
        }

        $group = new SkillGroup();
        $group->setName($name);
        $group->setDescription(trim($request->request->getString('description')) ?: null);
        $group->setIcon($request->request->getString('icon', 'folder'));
        $group->setColor($request->request->getString('color', 'blue'));

        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_skill_groups');
    }

    #[Route(path: '/skill-groups/{name}/delete', name: 'app_skill_groups_delete', methods: ['POST'])]
    public function delete(Request $request, string $name): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_group', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $group = $this->skillGroups->findOneByName($name);
        if ($group === null) {
            throw $this->createNotFoundException('Skill group not found.');
        }

        foreach ($this->skills->findAll() as $skill) {
            if ($skill['group'] !== $name) {
                continue;
            }

            $slug = \is_string($skill['slug']) ? $skill['slug'] : '';

            $this->skills->save($slug, [
                'name' => \is_string($skill['name']) ? $skill['name'] : $slug,
                'description' => \is_string($skill['description']) ? $skill['description'] : '',
                'group' => null,
                'icon' => \is_string($skill['icon']) ? $skill['icon'] : 'folder',
                'color' => \is_string($skill['color']) ? $skill['color'] : 'blue',
                'content' => \is_string($skill['content']) ? $skill['content'] : '',
            ]);
        }

        $this->entityManager->remove($group);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_skill_groups');
    }

    #[Route(path: '/skill-groups/move-skill', name: 'app_skill_move_to_group', methods: ['POST'])]
    public function moveSkill(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('move_skill', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $slug = $request->request->getString('slug');
        $skill = $this->skills->findBySlug($slug);
        if ($skill === null) {
            throw $this->createNotFoundException('Skill not found.');
        }

        $group = trim($request->request->getString('group'));
        if ($group !== '' && $this->skillGroups->findOneByName($group) === null) {
            $group = '';
        }

        $this->skills->save($slug, [
            'name' => \is_string($skill['name']) ? $skill['name'] : $slug,
            'description' => \is_string($skill['description']) ? $skill['description'] : '',
            'group' => $group !== '' ? $group : null,
            'icon' => \is_string($skill['icon']) ? $skill['icon'] : 'folder',
            'color' => \is_string($skill['color']) ? $skill['color'] : 'blue',
            'content' => \is_string($skill['content']) ? $skill['content'] : '',
        ]);

        $redirect = $request->request->getString('redirect');

        return $redirect === 'skill'
            ? $this->redirectToRoute('app_skill', ['slug' => $slug])
            : $this->redirectToRoute('app_skill_groups');
    }
}
