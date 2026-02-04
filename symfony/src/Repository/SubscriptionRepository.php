<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.price', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
