<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserSkillGroupStateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSkillGroupStateRepository::class)]
#[ORM\Table(name: 'user_skill_group_states')]
#[ORM\UniqueConstraint(name: 'user_skill_group_states_user_id_skill_group_id_key', columns: ['user_id', 'skill_group_id'])]
final class UserSkillGroupState
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: SkillGroup::class)]
    #[ORM\JoinColumn(name: 'skill_group_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private SkillGroup $skillGroup;

    #[ORM\Column]
    private bool $enabled;

    public function __construct(User $user, SkillGroup $skillGroup, bool $enabled)
    {
        $this->user = $user;
        $this->skillGroup = $skillGroup;
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

    public function getSkillGroup(): SkillGroup
    {
        return $this->skillGroup;
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
