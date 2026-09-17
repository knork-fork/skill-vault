<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SkillGroup;
use App\Entity\User;
use App\Entity\UserSkillGroupState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSkillGroupState>
 */
final class UserSkillGroupStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSkillGroupState::class);
    }

    public function findOneByUserAndGroup(User $user, SkillGroup $skillGroup): ?UserSkillGroupState
    {
        return $this->findOneBy(['user' => $user, 'skillGroup' => $skillGroup]);
    }

    /**
     * @return list<UserSkillGroupState>
     */
    public function findAllByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}
