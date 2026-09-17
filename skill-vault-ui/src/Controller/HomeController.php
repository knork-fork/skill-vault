<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SkillGroup;
use App\Entity\User;
use App\Repository\SkillGroupRepository;
use App\Skill\SkillFileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly SkillFileRepository $skills,
        private readonly SkillGroupRepository $skillGroups,
    ) {
    }

    #[Route(path: '/', name: 'app_home', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): Response
    {
        $skills = $this->skills->findAll();
        $groups = $this->skillGroups->findAll();

        $topGroups = array_map(
            static function (SkillGroup $group) use ($skills): array {
                $skillCount = 0;
                foreach ($skills as $skill) {
                    if ($skill['group'] === $group->getName()) {
                        ++$skillCount;
                    }
                }

                return [
                    'name' => $group->getName(),
                    'icon' => $group->getIcon(),
                    'color' => $group->getColor(),
                    'skillCount' => $skillCount,
                ];
            },
            \array_slice($groups, 0, 4),
        );

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'totalSkills' => \count($skills),
            'totalGroups' => \count($groups),
            'topGroups' => $topGroups,
        ]);
    }
}
