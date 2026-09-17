<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\OAuth\OAuthTokenService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * The OAuth 2.0 authorization server (authorization-code + PKCE, public
 * clients only) that lets Claude Code and other MCP clients sign in as a
 * Skill Vault user. skill-vault-mcp-server holds no OAuth data of its own —
 * it validates bearer tokens by calling POST /oauth/introspect.
 */
final class OAuthController extends AbstractController
{
    public function __construct(
        private readonly OAuthTokenService $tokens,
        #[Autowire(param: 'app.oauth_introspection_shared_secret')]
        private readonly string $introspectionSharedSecret,
    ) {
    }

    #[Route('/.well-known/oauth-authorization-server', name: 'app_oauth_metadata', methods: ['GET'])]
    public function metadata(Request $request): JsonResponse
    {
        return new JsonResponse([
            'issuer' => $request->getSchemeAndHttpHost(),
            'authorization_endpoint' => $this->generateUrl('app_oauth_authorize', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'token_endpoint' => $this->generateUrl('app_oauth_token', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'registration_endpoint' => $this->generateUrl('app_oauth_register', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
        ]);
    }

    #[Route('/oauth/register', name: 'app_oauth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        if (!\is_array($body)) {
            return new JsonResponse(['error' => 'invalid_client_metadata'], 400);
        }

        $clientName = \is_string($body['client_name'] ?? null) ? $body['client_name'] : 'MCP client';

        $redirectUris = $body['redirect_uris'] ?? null;
        if (!\is_array($redirectUris) || $redirectUris === [] || !array_is_list($redirectUris)) {
            return new JsonResponse(['error' => 'invalid_redirect_uri'], 400);
        }

        $validatedRedirectUris = [];
        foreach ($redirectUris as $redirectUri) {
            if (!\is_string($redirectUri) || $redirectUri === '') {
                return new JsonResponse(['error' => 'invalid_redirect_uri'], 400);
            }

            $validatedRedirectUris[] = $redirectUri;
        }

        $client = $this->tokens->registerClient($clientName, $validatedRedirectUris);

        return new JsonResponse([
            'client_id' => $client->getClientId(),
            'client_name' => $client->getClientName(),
            'redirect_uris' => $client->getRedirectUris(),
            'token_endpoint_auth_method' => 'none',
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
        ], 201);
    }

    #[Route('/oauth/authorize', name: 'app_oauth_authorize', methods: ['GET'])]
    public function authorize(Request $request, #[CurrentUser] User $user): Response
    {
        $clientId = $request->query->getString('client_id');
        $redirectUri = $request->query->getString('redirect_uri');
        $client = $this->tokens->findClient($clientId);

        if ($client === null || !$client->allowsRedirectUri($redirectUri)) {
            throw $this->createNotFoundException('Unknown OAuth client or redirect URI.');
        }

        $state = $request->query->getString('state');
        $codeChallenge = $request->query->getString('code_challenge');
        $codeChallengeMethod = $request->query->getString('code_challenge_method');

        if ($request->query->getString('response_type') !== 'code' || $codeChallenge === '' || $codeChallengeMethod !== 'S256') {
            return $this->redirectWithError($redirectUri, 'invalid_request', $state);
        }

        return $this->render('security/authorize.html.twig', [
            'user' => $user,
            'client' => $client,
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
        ]);
    }

    #[Route('/oauth/authorize', name: 'app_oauth_authorize_submit', methods: ['POST'])]
    public function authorizeSubmit(Request $request, #[CurrentUser] User $user): Response
    {
        if (!$this->isCsrfTokenValid('oauth_authorize', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $clientId = $request->request->getString('client_id');
        $redirectUri = $request->request->getString('redirect_uri');
        $client = $this->tokens->findClient($clientId);

        if ($client === null || !$client->allowsRedirectUri($redirectUri)) {
            throw $this->createNotFoundException('Unknown OAuth client or redirect URI.');
        }

        $state = $request->request->getString('state');

        if ($request->request->getString('decision') !== 'allow') {
            return $this->redirectWithError($redirectUri, 'access_denied', $state);
        }

        $userId = $user->getId();
        if ($userId === null) {
            throw new LogicException('Authenticated user is missing an id.');
        }

        $code = $this->tokens->issueAuthorizationCode(
            $client,
            $userId,
            $redirectUri,
            $request->request->getString('code_challenge'),
            $request->request->getString('code_challenge_method'),
        );

        $query = $state !== '' ? ['code' => $code, 'state' => $state] : ['code' => $code];

        return new RedirectResponse(self::appendQuery($redirectUri, $query));
    }

    #[Route('/oauth/token', name: 'app_oauth_token', methods: ['POST'])]
    public function token(Request $request): JsonResponse
    {
        $grantType = $request->request->getString('grant_type');
        $clientId = $request->request->getString('client_id');

        if (!\in_array($grantType, ['authorization_code', 'refresh_token'], true)) {
            return new JsonResponse(['error' => 'unsupported_grant_type'], 400);
        }

        $pair = $grantType === 'authorization_code'
            ? $this->tokens->exchangeAuthorizationCode(
                $clientId,
                $request->request->getString('code'),
                $request->request->getString('redirect_uri'),
                $request->request->getString('code_verifier'),
            )
            : $this->tokens->refreshAccessToken($clientId, $request->request->getString('refresh_token'));

        if ($pair === null) {
            return new JsonResponse(['error' => 'invalid_grant'], 400);
        }

        return new JsonResponse([
            'access_token' => $pair->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $pair->expiresIn,
            'refresh_token' => $pair->refreshToken,
        ]);
    }

    #[Route('/oauth/introspect', name: 'app_oauth_introspect', methods: ['POST'])]
    public function introspect(Request $request): JsonResponse
    {
        $providedSecret = $request->headers->get('X-Internal-Secret') ?? '';
        if ($this->introspectionSharedSecret === '' || !hash_equals($this->introspectionSharedSecret, $providedSecret)) {
            return new JsonResponse(['active' => false], 403);
        }

        $body = json_decode($request->getContent(), true);
        $token = \is_array($body) && \is_string($body['token'] ?? null) ? $body['token'] : '';

        $identity = $token !== '' ? $this->tokens->introspectAccessToken($token) : null;
        if ($identity === null) {
            return new JsonResponse(['active' => false]);
        }

        return new JsonResponse([
            'active' => true,
            'user_id' => $identity->userId,
            'username' => $identity->username,
        ]);
    }

    private function redirectWithError(string $redirectUri, string $error, string $state): RedirectResponse
    {
        $query = $state !== '' ? ['error' => $error, 'state' => $state] : ['error' => $error];

        return new RedirectResponse(self::appendQuery($redirectUri, $query));
    }

    /**
     * @param array<string, string> $query
     */
    private static function appendQuery(string $redirectUri, array $query): string
    {
        return $redirectUri . (str_contains($redirectUri, '?') ? '&' : '?') . http_build_query($query);
    }
}
