<?php

namespace Accounting\Repository;

use Accounting\Entity\Transaction;
use Doctrine\ORM\EntityRepository;

class TransactionRepository extends EntityRepository
{
    public function calculateDailyEarnings(): array
    {
        $dateFrom = new \DateTime();
        $dateTo = clone $dateFrom;

        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(23, 59, 59);

        return $this
            ->createQueryBuilder('t')
            ->select('
                SUM(CASE WHEN t.status = :completed AND t.type = :deposit AND cast(t.amount as decimal) < 0 THEN cast(t.amount as decimal) ELSE 0 END) as deposit_withdraw,
                SUM(CASE WHEN t.status = :completed AND t.type = :top_up AND cast(t.amount as decimal) > 0 THEN cast(t.amount as decimal) ELSE 0 END) as as deposit_refund
            ')
            ->andWhere('t.status = :completed')
            ->andWhere('t.createdAt >= :date_from')
            ->andWhere('t.createdAt <= :date_to')
            ->setParameters([
                'completed' => Transaction::STATUS_COMPLETED,
                'deposit' => Transaction::TYPE_DEPOSIT,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ])
            ->getQuery()
            ->getResult();
    }

    public function calculateDayUserEarnings(\DateTime $dateFrom, \DateTime $dateTo): array
    {
        return $this
            ->createQueryBuilder('t')
            ->select('
                IDENTITY(t.user) as user_id,
                SUM(CASE WHEN t.status = :completed AND t.type = :deposit AND cast(t.amount as decimal) < 0 THEN cast(t.amount as decimal) ELSE 0 END) as deposit_withdraw,
                SUM(CASE WHEN t.status = :completed AND t.type = :deposit AND cast(t.amount as decimal) > 0 THEN cast(t.amount as decimal) ELSE 0 END) as deposit_refund
            ')
            ->andWhere('t.status = :completed')
            ->andWhere('t.createdAt >= :date_from')
            ->andWhere('t.createdAt <= :date_to')
            ->groupBy('t.user')
            ->setParameters([
                'completed' => Transaction::STATUS_COMPLETED,
                'deposit' => Transaction::TYPE_DEPOSIT,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ])
            ->getQuery()
            ->getResult();
    }

    public function findHighestCostTaskForPeriod(\DateTime $dateFrom, \DateTime $dateTo): array
    {
        return $this
            ->createQueryBuilder('t')
            ->select('MAX(cast(t.amount as decimal)) as amount')
            ->addSelect('t')
            ->andWhere('t.status = :completed')
            ->andWhere('t.type = :deposit')
            ->andWhere('t.createdAt >= :date_from')
            ->andWhere('t.createdAt <= :date_to')
            ->setParameters([
                'completed' => Transaction::STATUS_COMPLETED,
                'deposit' => Transaction::TYPE_DEPOSIT,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ])
            ->groupBy('t.id')
            ->getQuery()
            ->getResult();
    }
}