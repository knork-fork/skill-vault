<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Refuses to serve any page until scripts/setup.sh has been run, since large parts of the app
 * (OAuth introspection secret, invite secret, MCP server URL) are only ever configured there.
 */
final class RequireEnvLocalSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8192],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (is_file($this->projectDir . '/.env.local')) {
            return;
        }

        $event->setResponse(new Response(<<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Skill Vault</title>
                <style>
                    body {
                        margin: 0;
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: #f9fafb;
                        color: #0f1b33;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    }
                    .message {
                        max-width: 480px;
                        padding: 32px;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class="message">Please contact maintainer to run scripts/setup.sh</div>
            </body>
            </html>
            HTML, Response::HTTP_SERVICE_UNAVAILABLE));
    }
}
