<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Tool\ToolFileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ToolController extends AbstractController
{
    public function __construct(private readonly ToolFileRepository $tools)
    {
    }

    #[Route(path: '/tools', name: 'app_tools', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('tools/index.html.twig', [
            'user' => $user,
            'tools' => $this->tools->findAll(),
        ]);
    }

    #[Route(path: '/tools/{slug}', name: 'app_tool', methods: ['GET'])]
    public function show(#[CurrentUser] User $user, string $slug): Response
    {
        $tool = $this->tools->findBySlug($slug);
        if ($tool === null) {
            throw $this->createNotFoundException('Tool not found.');
        }

        return $this->render('tools/show.html.twig', [
            'user' => $user,
            'tool' => $tool,
        ]);
    }
}
