<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * MCP clients (e.g. Claude Code) call the OAuth discovery/DCR/token endpoints cross-origin and
 * preflight non-simple requests (JSON bodies) with OPTIONS, which Symfony's router otherwise
 * rejects with 405 since none of OAuthController's routes declare OPTIONS. Runs ahead of the
 * router (priority higher than its default 32) so the preflight is answered before routing ever
 * sees the OPTIONS method.
 */
final class OAuthCorsSubscriber implements EventSubscriberInterface
{
    private const CORS_PATHS = [
        '/.well-known/oauth-authorization-server',
        '/oauth/register',
        '/oauth/authorize',
        '/oauth/token',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 64],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getMethod() === 'OPTIONS' && self::isCorsPath($request)) {
            $event->setResponse(new Response('', Response::HTTP_NO_CONTENT));
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!self::isCorsPath($request)) {
            return;
        }

        $event->getResponse()->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    private static function isCorsPath(Request $request): bool
    {
        return \in_array($request->getPathInfo(), self::CORS_PATHS, true);
    }
}
