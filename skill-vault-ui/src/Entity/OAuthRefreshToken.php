<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OAuthRefreshTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OAuthRefreshTokenRepository::class)]
#[ORM\Table(name: 'oauth_refresh_tokens')]
final class OAuthRefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'token_hash', length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(name: 'client_id', length: 64)]
    private string $clientId;

    #[ORM\Column(name: 'user_id', type: Types::BIGINT)]
    private int $userId;

    #[ORM\Column(name: 'expires_at')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'revoked_at', nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    public function __construct(string $tokenHash, string $clientId, int $userId, DateTimeImmutable $expiresAt)
    {
        $this->tokenHash = $tokenHash;
        $this->clientId = $clientId;
        $this->userId = $userId;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isValid(): bool
    {
        return $this->revokedAt === null && $this->expiresAt >= new DateTimeImmutable();
    }

    public function revoke(): void
    {
        $this->revokedAt = new DateTimeImmutable();
    }
}
