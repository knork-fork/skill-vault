<?php

declare(strict_types=1);

namespace App\OAuth;

use App\Entity\OAuthAccessToken;
use App\Entity\OAuthAuthCode;
use App\Entity\OAuthClient;
use App\Entity\OAuthRefreshToken;
use App\Repository\OAuthAccessTokenRepository;
use App\Repository\OAuthAuthCodeRepository;
use App\Repository\OAuthClientRepository;
use App\Repository\OAuthRefreshTokenRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Minimal OAuth 2.0 authorization-code-with-PKCE authorization server. Issues
 * and validates every OAuth artifact (clients, codes, access/refresh tokens)
 * on behalf of skill-vault-ui, which is the sole owner of user identity.
 * skill-vault-mcp-server never touches these tables directly — it calls the
 * introspection endpoint, which is backed by {@see introspectAccessToken()}.
 */
final class OAuthTokenService
{
    private const int AUTH_CODE_TTL_SECONDS = 60;
    private const int ACCESS_TOKEN_TTL_SECONDS = 3600;
    private const int REFRESH_TOKEN_TTL_SECONDS = 180 * 24 * 3600;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OAuthClientRepository $clients,
        private readonly OAuthAuthCodeRepository $authCodes,
        private readonly OAuthAccessTokenRepository $accessTokens,
        private readonly OAuthRefreshTokenRepository $refreshTokens,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * @param list<string> $redirectUris
     */
    public function registerClient(string $clientName, array $redirectUris): OAuthClient
    {
        $client = new OAuthClient('client_' . bin2hex(random_bytes(16)), $clientName, $redirectUris);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
    }

    public function findClient(string $clientId): ?OAuthClient
    {
        return $this->clients->findOneByClientId($clientId);
    }

    public function issueAuthorizationCode(
        OAuthClient $client,
        int $userId,
        string $redirectUri,
        string $codeChallenge,
        string $codeChallengeMethod,
    ): string {
        $code = bin2hex(random_bytes(32));

        $authCode = new OAuthAuthCode(
            self::hash($code),
            $client->getClientId(),
            $userId,
            $redirectUri,
            $codeChallenge,
            $codeChallengeMethod,
            new DateTimeImmutable('+' . self::AUTH_CODE_TTL_SECONDS . ' seconds'),
        );

        $this->entityManager->persist($authCode);
        $this->entityManager->flush();

        return $code;
    }

    public function exchangeAuthorizationCode(
        string $clientId,
        string $code,
        string $redirectUri,
        string $codeVerifier,
    ): ?IssuedTokenPair {
        $authCode = $this->authCodes->findOneByCodeHash(self::hash($code));

        if ($authCode === null
            || $authCode->isUsed()
            || $authCode->isExpired()
            || $authCode->getClientId() !== $clientId
            || $authCode->getRedirectUri() !== $redirectUri
            || !self::verifyPkce($codeVerifier, $authCode->getCodeChallenge(), $authCode->getCodeChallengeMethod())
        ) {
            return null;
        }

        $authCode->markUsed();
        $pair = $this->issueTokenPair($clientId, $authCode->getUserId());

        $this->entityManager->flush();

        return $pair;
    }

    public function refreshAccessToken(string $clientId, string $refreshToken): ?IssuedTokenPair
    {
        $existing = $this->refreshTokens->findOneByTokenHash(self::hash($refreshToken));

        if ($existing === null || !$existing->isValid() || $existing->getClientId() !== $clientId) {
            return null;
        }

        $existing->revoke();
        $pair = $this->issueTokenPair($clientId, $existing->getUserId());

        $this->entityManager->flush();

        return $pair;
    }

    public function introspectAccessToken(string $accessToken): ?TokenIdentity
    {
        $token = $this->accessTokens->findOneByTokenHash(self::hash($accessToken));

        if ($token === null || !$token->isValid()) {
            return null;
        }

        $user = $this->users->find($token->getUserId());
        if ($user === null) {
            return null;
        }

        return new TokenIdentity($token->getUserId(), $user->getUsername());
    }

    private function issueTokenPair(string $clientId, int $userId): IssuedTokenPair
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));

        $this->entityManager->persist(new OAuthAccessToken(
            self::hash($accessToken),
            $clientId,
            $userId,
            new DateTimeImmutable('+' . self::ACCESS_TOKEN_TTL_SECONDS . ' seconds'),
        ));

        $this->entityManager->persist(new OAuthRefreshToken(
            self::hash($refreshToken),
            $clientId,
            $userId,
            new DateTimeImmutable('+' . self::REFRESH_TOKEN_TTL_SECONDS . ' seconds'),
        ));

        return new IssuedTokenPair($accessToken, $refreshToken, self::ACCESS_TOKEN_TTL_SECONDS);
    }

    private static function verifyPkce(string $codeVerifier, string $codeChallenge, string $codeChallengeMethod): bool
    {
        if ($codeChallengeMethod !== 'S256') {
            return false;
        }

        $computed = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        return hash_equals($codeChallenge, $computed);
    }

    private static function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
