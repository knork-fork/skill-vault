<?php

declare(strict_types=1);

namespace App\Controller;

use App\Avatar\AvatarPalette;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SkillGroupController extends AbstractController
{
    #[Route(path: '/skill-groups', name: 'app_skill_groups', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): Response
    {
        $groups = [
            [
                'name' => 'Trello Workflows',
                'description' => 'Project management skills for Trello automation and workflows.',
                'icon' => 'trello',
                'color' => 'blue',
                'skillCount' => 5,
                'access' => ['type' => 'public', 'label' => 'Public', 'scope' => 'All users (Read-only)'],
                'enabled' => true,
            ],
            [
                'name' => 'Backend',
                'description' => 'Backend development, APIs, databases, and cloud infrastructure.',
                'icon' => 'terminal',
                'color' => 'green',
                'skillCount' => 8,
                'access' => ['type' => 'private', 'label' => 'Private', 'scope' => 'Only me (Writable)'],
                'enabled' => true,
            ],
            [
                'name' => 'Product',
                'description' => 'Product strategy, user research, and roadmap planning.',
                'icon' => 'cube',
                'color' => 'purple',
                'skillCount' => 6,
                'access' => ['type' => 'workspace', 'label' => 'Workspace', 'scope' => 'Editors (Writable)'],
                'enabled' => true,
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales processes, outreach, and customer engagement.',
                'icon' => 'users',
                'color' => 'orange',
                'skillCount' => 4,
                'access' => ['type' => 'public', 'label' => 'Public', 'scope' => 'All users (Read-only)'],
                'enabled' => false,
            ],
            [
                'name' => 'Research',
                'description' => 'Research methods, analysis, and information synthesis.',
                'icon' => 'chart',
                'color' => 'red',
                'skillCount' => 7,
                'access' => ['type' => 'workspace', 'label' => 'Workspace', 'scope' => 'Viewers (Read-only)'],
                'enabled' => true,
            ],
            [
                'name' => 'Personal',
                'description' => 'Personal productivity, notes, and custom experiments.',
                'icon' => 'heart',
                'color' => 'teal',
                'skillCount' => 3,
                'access' => ['type' => 'private', 'label' => 'Private', 'scope' => 'Only me (Writable)'],
                'enabled' => true,
            ],
        ];

        $ungroupedSkills = [
            [
                'name' => 'compile_trello_notes',
                'description' => 'Summarize and structure Trello board notes',
                'icon' => 'doc',
                'enabled' => true,
            ],
            [
                'name' => 'review_backend_task',
                'description' => 'Analyze backend tasks and suggest next steps',
                'icon' => 'code',
                'enabled' => true,
            ],
            [
                'name' => 'investigate_api_bug',
                'description' => 'Deep dive into API errors and suggest fixes',
                'icon' => 'bug',
                'enabled' => false,
            ],
            [
                'name' => 'create_product_brief',
                'description' => 'Generate a product brief from notes',
                'icon' => 'doc',
                'enabled' => true,
            ],
        ];

        return $this->render('skill_groups/index.html.twig', [
            'user' => $user,
            'groups' => $groups,
            'ungroupedSkills' => $ungroupedSkills,
            'iconOptions' => AvatarPalette::ICONS,
            'colorOptions' => AvatarPalette::COLORS,
        ]);
    }
}
