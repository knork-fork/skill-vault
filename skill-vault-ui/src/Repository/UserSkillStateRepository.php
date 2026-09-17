<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSkillState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSkillState>
 */
final class UserSkillStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSkillState::class);
    }

    public function findOneByUserAndSlug(User $user, string $skillSlug): ?UserSkillState
    {
        return $this->findOneBy(['user' => $user, 'skillSlug' => $skillSlug]);
    }

    /**
     * @return list<UserSkillState>
     */
    public function findAllByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}
