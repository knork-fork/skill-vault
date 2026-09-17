<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserSkillStateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSkillStateRepository::class)]
#[ORM\Table(name: 'user_skill_states')]
#[ORM\UniqueConstraint(name: 'user_skill_states_user_id_skill_slug_key', columns: ['user_id', 'skill_slug'])]
final class UserSkillState
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'skill_slug', length: 255)]
    private string $skillSlug;

    #[ORM\Column]
    private bool $enabled;

    public function __construct(User $user, string $skillSlug, bool $enabled)
    {
        $this->user = $user;
        $this->skillSlug = $skillSlug;
        $this->enabled = $enabled;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSkillSlug(): string
    {
        return $this->skillSlug;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }
}
