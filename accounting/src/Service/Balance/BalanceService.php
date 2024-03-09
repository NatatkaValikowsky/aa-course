<?php

namespace Accounting\Service\Balance;

use Accounting\Entity\Balance;
use Accounting\Entity\Transaction;
use Accounting\Entity\User;
use Accounting\Repository\BalanceRepository;
use Doctrine\ORM\EntityManagerInterface;

class BalanceService
{
    private BalanceRepository $balanceRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        BalanceRepository $balanceRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->balanceRepository = $balanceRepository;
        $this->entityManager = $entityManager;
    }

    public function updateBalance(User $user, Transaction $transaction): void
    {
        $balance = $this->balanceRepository->findOneBy([
            'user' => $user,
        ]);

        if ($balance === null) {
            $balance = new Balance();
            $balance->setUser($user);
        }

        $balance->applyTransaction($transaction);

        $this->entityManager->persist($balance);
        $this->entityManager->flush();
    }
}