<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OAuthAuthCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OAuthAuthCode>
 */
final class OAuthAuthCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthAuthCode::class);
    }

    public function findOneByCodeHash(string $codeHash): ?OAuthAuthCode
    {
        return $this->findOneBy(['codeHash' => $codeHash]);
    }
}
