<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OAuthClientRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OAuthClientRepository::class)]
#[ORM\Table(name: 'oauth_clients')]
final class OAuthClient
{
    #[ORM\Id]
    #[ORM\Column(name: 'client_id', length: 64)]
    private string $clientId;

    #[ORM\Column(name: 'client_name', length: 255)]
    private string $clientName;

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'redirect_uris', type: Types::JSON)]
    private array $redirectUris;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    /**
     * @param list<string> $redirectUris
     */
    public function __construct(string $clientId, string $clientName, array $redirectUris)
    {
        $this->clientId = $clientId;
        $this->clientName = $clientName;
        $this->redirectUris = $redirectUris;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    /**
     * @return list<string>
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    public function allowsRedirectUri(string $redirectUri): bool
    {
        return \in_array($redirectUri, $this->redirectUris, true);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
