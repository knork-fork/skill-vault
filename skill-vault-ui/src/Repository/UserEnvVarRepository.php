<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserEnvVar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserEnvVar>
 */
final class UserEnvVarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserEnvVar::class);
    }

    /**
     * @return list<UserEnvVar>
     */
    public function findAllByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['placeholder' => 'ASC']);
    }

    public function findOneByUserAndPlaceholder(User $user, string $placeholder): ?UserEnvVar
    {
        return $this->findOneBy(['user' => $user, 'placeholder' => $placeholder]);
    }

    public function findOneByUserAndId(User $user, int $id): ?UserEnvVar
    {
        return $this->findOneBy(['user' => $user, 'id' => $id]);
    }
}
