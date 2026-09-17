<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SkillGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SkillGroup>
 */
final class SkillGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SkillGroup::class);
    }

    /**
     * @return list<SkillGroup>
     */
    public function findAll(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }

    public function findOneByName(string $name): ?SkillGroup
    {
        return $this->findOneBy(['name' => $name]);
    }
}
