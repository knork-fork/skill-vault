<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ToolController extends AbstractController
{
    /**
     * @return list<array<string, mixed>>
     */
    private function tools(): array
    {
        return [
            [
                'slug' => 'trello_get_card',
                'name' => 'trello_get_card',
                'description' => "Fetch a Trello card and its relevant data using the current user's Trello account.",
                'yaml' => <<<'YAML'
                    name: trello_get_card
                    description: Fetch a Trello card and its relevant data using the current user's Trello account.

                    inputSchema:
                      type: object
                      properties:
                        card_url:
                          type: string
                      required:
                        - card_url
                    YAML,
            ],
            [
                'slug' => 'trello_add_comment',
                'name' => 'trello_add_comment',
                'description' => "Add a comment to a Trello card using the current user's Trello account.",
                'yaml' => <<<'YAML'
                    name: trello_add_comment
                    description: Add a comment to a Trello card using the current user's Trello account.

                    inputSchema:
                      type: object
                      properties:
                        card_url:
                          type: string
                        text:
                          type: string
                      required:
                        - card_url
                        - text
                    YAML,
            ],
            [
                'slug' => 'gdrive_search_files',
                'name' => 'gdrive_search_files',
                'description' => "Search the current user's Google Drive for files matching a query.",
                'yaml' => <<<'YAML'
                    name: gdrive_search_files
                    description: Search the current user's Google Drive for files matching a query.

                    inputSchema:
                      type: object
                      properties:
                        query:
                          type: string
                        max_results:
                          type: integer
                      required:
                        - query
                    YAML,
            ],
            [
                'slug' => 'gdrive_read_file',
                'name' => 'gdrive_read_file',
                'description' => "Read the contents of a Google Drive file using the current user's account.",
                'yaml' => <<<'YAML'
                    name: gdrive_read_file
                    description: Read the contents of a Google Drive file using the current user's account.

                    inputSchema:
                      type: object
                      properties:
                        file_id:
                          type: string
                      required:
                        - file_id
                    YAML,
            ],
            [
                'slug' => 'trello_list_board_cards',
                'name' => 'trello_list_board_cards',
                'description' => "List cards on a Trello board using the current user's Trello account.",
                'yaml' => <<<'YAML'
                    name: trello_list_board_cards
                    description: List cards on a Trello board using the current user's Trello account.

                    inputSchema:
                      type: object
                      properties:
                        board_url:
                          type: string
                        include_archived:
                          type: boolean
                      required:
                        - board_url
                    YAML,
            ],
        ];
    }

    #[Route(path: '/tools', name: 'app_tools', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('tools/index.html.twig', [
            'user' => $user,
            'tools' => $this->tools(),
        ]);
    }

    #[Route(path: '/tools/{slug}', name: 'app_tool', methods: ['GET'])]
    public function show(#[CurrentUser] User $user, string $slug): Response
    {
        foreach ($this->tools() as $tool) {
            if ($tool['slug'] === $slug) {
                return $this->render('tools/show.html.twig', [
                    'user' => $user,
                    'tool' => $tool,
                ]);
            }
        }

        throw $this->createNotFoundException('Tool not found.');
    }
}
