<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OAuthAuthCodeRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OAuthAuthCodeRepository::class)]
#[ORM\Table(name: 'oauth_auth_codes')]
final class OAuthAuthCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'code_hash', length: 64, unique: true)]
    private string $codeHash;

    #[ORM\Column(name: 'client_id', length: 64)]
    private string $clientId;

    #[ORM\Column(name: 'user_id', type: Types::BIGINT)]
    private int $userId;

    #[ORM\Column(name: 'redirect_uri', type: Types::TEXT)]
    private string $redirectUri;

    #[ORM\Column(name: 'code_challenge', length: 128)]
    private string $codeChallenge;

    #[ORM\Column(name: 'code_challenge_method', length: 10)]
    private string $codeChallengeMethod;

    #[ORM\Column(name: 'expires_at')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'used_at', nullable: true)]
    private ?DateTimeImmutable $usedAt = null;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $codeHash,
        string $clientId,
        int $userId,
        string $redirectUri,
        string $codeChallenge,
        string $codeChallengeMethod,
        DateTimeImmutable $expiresAt,
    ) {
        $this->codeHash = $codeHash;
        $this->clientId = $clientId;
        $this->userId = $userId;
        $this->redirectUri = $redirectUri;
        $this->codeChallenge = $codeChallenge;
        $this->codeChallengeMethod = $codeChallengeMethod;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getCodeChallenge(): string
    {
        return $this->codeChallenge;
    }

    public function getCodeChallengeMethod(): string
    {
        return $this->codeChallengeMethod;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markUsed(): void
    {
        $this->usedAt = new DateTimeImmutable();
    }
}
