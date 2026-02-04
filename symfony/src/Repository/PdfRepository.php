<?php

namespace App\Repository;

use App\Entity\Pdf;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pdf>
 */
class PdfRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pdf::class);
    }

    /** @return Pdf[] */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{0: Pdf[], 1: int} [pdfs, total]
     */
    public function findByUserPaginated(User $user, ?string $search = null, ?string $status = null, int $page = 1, int $perPage = 15): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC');

        if ($search !== null && $search !== '') {
            $qb->andWhere('p.originalUrl LIKE :search OR p.filename LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($status === 'success') {
            $qb->andWhere('p.isSuccess = :ok')->setParameter('ok', true);
        } elseif ($status === 'failed') {
            $qb->andWhere('p.isSuccess = :ok')->setParameter('ok', false);
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $pdfs = $qb->getQuery()->getResult();

        return [$pdfs, $total];
    }

    public function countByUserThisMonth(User $user): int
    {
        $start = (new \DateTimeImmutable())->modify('first day of this month')->setTime(0, 0, 0);
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->andWhere('p.createdAt >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUserTotal(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSuccessfulToday(User $user): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.user = :user')
            ->andWhere('p.isSuccess = :ok')
            ->andWhere('p.createdAt >= :start')
            ->setParameter('user', $user)
            ->setParameter('ok', true)
            ->setParameter('start', (new \DateTimeImmutable())->setTime(0, 0, 0));
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
