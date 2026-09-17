<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use League\CommonMark\CommonMarkConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class DocsController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    private function docsDir(): string
    {
        return $this->projectDir . '/docs';
    }

    private function titleFor(string $slug): string
    {
        return str_replace('_', ' ', $slug);
    }

    /**
     * @return list<array<string, string>>
     */
    private function articles(): array
    {
        $dir = $this->docsDir();
        if (!is_dir($dir)) {
            return [];
        }

        $articles = [];
        foreach (glob($dir . '/*.md') ?: [] as $path) {
            $slug = basename($path, '.md');

            $articles[] = [
                'slug' => $slug,
                'title' => $this->titleFor($slug),
            ];
        }

        usort($articles, static fn (array $a, array $b): int => $a['title'] <=> $b['title']);

        return $articles;
    }

    #[Route(path: '/docs', name: 'app_docs', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('docs/index.html.twig', [
            'user' => $user,
            'articles' => $this->articles(),
        ]);
    }

    #[Route(path: '/docs/{slug}', name: 'app_docs_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user, string $slug): Response
    {
        $path = $this->docsDir() . '/' . $slug . '.md';
        if (!is_file($path)) {
            throw $this->createNotFoundException('Article not found.');
        }

        $content = file_get_contents($path) ?: '';
        $converter = new CommonMarkConverter();

        return $this->render('docs/show.html.twig', [
            'user' => $user,
            'title' => $this->titleFor($slug),
            'html' => $converter->convert($content)->getContent(),
        ]);
    }
}
