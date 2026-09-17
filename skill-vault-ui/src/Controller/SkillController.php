<?php

declare(strict_types=1);

namespace App\Controller;

use App\Avatar\AvatarPalette;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SkillController extends AbstractController
{
    private const SESSION_KEY = 'mock_skills';

    /**
     * @var list<string>
     */
    private const GROUP_NAMES = [
        'Trello Workflows',
        'Backend',
        'Product',
        'Sales',
        'Research',
        'Personal',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    private function baseSkills(): array
    {
        return [
            [
                'slug' => 'compile_trello_notes',
                'name' => 'Compile Trello Notes',
                'description' => 'Turn a Trello card into decisions, history and unresolved questions.',
                'group' => 'Trello Workflows',
                'icon' => 'doc',
                'color' => 'blue',
                'daysAgo' => 2,
                'updated' => '2 days ago',
                'content' => <<<'SKILL'
                    # Compile Trello Notes

                    Fetch the Trello card using `trello_get_card`.

                    Then:
                    - read the description
                    - read comments chronologically
                    - identify decisions
                    - treat later explicit decisions as superseding earlier ones
                    - list unresolved questions
                    SKILL,
            ],
            [
                'slug' => 'draft_client_reply',
                'name' => 'Draft Client Reply',
                'description' => 'Write a clear, concise and professional reply to a client message.',
                'group' => 'Sales',
                'icon' => 'message',
                'color' => 'cyan',
                'daysAgo' => 3,
                'updated' => '3 days ago',
                'content' => <<<'SKILL'
                    # Draft Client Reply

                    Read the client's message and any prior thread history.

                    Then:
                    - acknowledge their main concern first
                    - answer every question they asked, in order
                    - keep the tone professional and concise
                    - propose a clear next step
                    SKILL,
            ],
            [
                'slug' => 'summarize_meeting',
                'name' => 'Summarize Meeting',
                'description' => 'Turn meeting notes into a structured summary with action items.',
                'group' => 'Product',
                'icon' => 'calendar',
                'color' => 'red',
                'daysAgo' => 5,
                'updated' => '5 days ago',
                'content' => <<<'SKILL'
                    # Summarize Meeting

                    Read the raw meeting notes provided.

                    Then produce:
                    - a short summary of what was discussed
                    - decisions made
                    - action items with an owner, if mentioned
                    - open questions
                    SKILL,
            ],
            [
                'slug' => 'analyze_document',
                'name' => 'Analyze Document',
                'description' => 'Extract key information and insights from a document.',
                'group' => 'Research',
                'icon' => 'doc',
                'color' => 'teal',
                'daysAgo' => 7,
                'updated' => '1 week ago',
                'content' => <<<'SKILL'
                    # Analyze Document

                    Read the attached document in full before summarizing.

                    Then:
                    - list the key points, most important first
                    - flag anything that looks inconsistent or incomplete
                    - note any figures, dates or names worth double-checking
                    SKILL,
            ],
            [
                'slug' => 'plan_project',
                'name' => 'Plan Project',
                'description' => 'Create a structured project plan with milestones and tasks.',
                'group' => 'Product',
                'icon' => 'briefcase',
                'color' => 'green',
                'daysAgo' => 7,
                'updated' => '1 week ago',
                'content' => <<<'SKILL'
                    # Plan Project

                    Given a project goal and constraints, produce:
                    - a milestone breakdown with rough sequencing
                    - a task list per milestone
                    - risks or open dependencies worth flagging
                    SKILL,
            ],
            [
                'slug' => 'research_topic',
                'name' => 'Research Topic',
                'description' => 'Research a topic and provide a structured summary of findings.',
                'group' => 'Research',
                'icon' => 'chart',
                'color' => 'red',
                'daysAgo' => 14,
                'updated' => '2 weeks ago',
                'content' => <<<'SKILL'
                    # Research Topic

                    Investigate the given topic.

                    Then structure the findings as:
                    - overview
                    - key facts, each with its source
                    - open questions or areas of disagreement
                    SKILL,
            ],
            [
                'slug' => 'create_newsletter',
                'name' => 'Create Newsletter',
                'description' => 'Write a newsletter based on the provided content and tone.',
                'group' => 'Sales',
                'icon' => 'mail',
                'color' => 'orange',
                'daysAgo' => 14,
                'updated' => '2 weeks ago',
                'content' => <<<'SKILL'
                    # Create Newsletter

                    Given the source content and target tone:
                    - write a short, attention-grabbing subject line
                    - open with the single most important update
                    - group remaining updates under short headings
                    - close with a clear call to action
                    SKILL,
            ],
            [
                'slug' => 'review_contract',
                'name' => 'Review Contract',
                'description' => 'Analyze a contract and highlight key terms and risks.',
                'group' => null,
                'icon' => 'doc',
                'color' => 'blue',
                'daysAgo' => 21,
                'updated' => '3 weeks ago',
                'content' => <<<'SKILL'
                    # Review Contract

                    Read the contract in full.

                    Then report:
                    - the key obligations for each party
                    - term length, renewal and termination conditions
                    - any clause that looks unusual or unfavorable
                    SKILL,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function createdSkills(Request $request): array
    {
        return self::normalizeSkillList($request->getSession()->get(self::SESSION_KEY, []));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function normalizeSkillList(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $skills = [];
        foreach ($value as $item) {
            if (\is_array($item)) {
                $skills[] = $item;
            }
        }

        return $skills;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allSkills(Request $request): array
    {
        return array_merge($this->createdSkills($request), $this->baseSkills());
    }

    #[Route(path: '/skills', name: 'app_skills', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, Request $request): Response
    {
        return $this->render('skills/index.html.twig', [
            'user' => $user,
            'skills' => $this->allSkills($request),
            'groupNames' => self::GROUP_NAMES,
        ]);
    }

    #[Route(path: '/skills/new', name: 'app_skills_new', methods: ['GET'])]
    public function new(#[CurrentUser] User $user): Response
    {
        return $this->render('skills/new.html.twig', [
            'user' => $user,
            'groupNames' => self::GROUP_NAMES,
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

        $skill = [
            'slug' => $slug,
            'name' => $name,
            'description' => trim($request->request->getString('description')),
            'group' => $group !== '' ? $group : null,
            'icon' => $request->request->getString('icon', 'folder'),
            'color' => $request->request->getString('color', 'blue'),
            'daysAgo' => 0,
            'updated' => 'just now',
            'content' => $request->request->getString('content'),
        ];

        $created = array_filter(
            $this->createdSkills($request),
            static fn (array $existing): bool => $existing['slug'] !== $slug
        );
        array_unshift($created, $skill);
        $request->getSession()->set(self::SESSION_KEY, $created);

        return $this->redirectToRoute('app_skill', ['slug' => $slug]);
    }

    #[Route(path: '/skills/{slug}', name: 'app_skill', methods: ['GET'])]
    public function show(#[CurrentUser] User $user, Request $request, string $slug): Response
    {
        foreach ($this->allSkills($request) as $skill) {
            if ($skill['slug'] === $slug) {
                return $this->render('skills/show.html.twig', [
                    'user' => $user,
                    'skill' => $skill,
                ]);
            }
        }

        throw $this->createNotFoundException('Skill not found.');
    }
}
