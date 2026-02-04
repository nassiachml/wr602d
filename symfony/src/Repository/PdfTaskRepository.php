<?php

namespace App\Repository;

use App\Entity\PdfTask;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PdfTask>
 */
class PdfTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PdfTask::class);
    }

    /** @return PdfTask[] */
    public function findPending(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', PdfTask::STATUS_PENDING)
            ->orderBy('t.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return PdfTask[] */
    public function findPendingByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', PdfTask::STATUS_PENDING)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
