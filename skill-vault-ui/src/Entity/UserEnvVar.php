<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserEnvVarRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserEnvVarRepository::class)]
#[ORM\Table(name: 'user_env_vars')]
#[ORM\UniqueConstraint(name: 'user_env_vars_user_id_placeholder_key', columns: ['user_id', 'placeholder'])]
final class UserEnvVar
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $placeholder;

    #[ORM\Column(type: Types::TEXT)]
    private string $value;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    public function __construct(User $user, string $placeholder, string $value)
    {
        $this->user = $user;
        $this->placeholder = $placeholder;
        $this->value = $value;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setPlaceholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
