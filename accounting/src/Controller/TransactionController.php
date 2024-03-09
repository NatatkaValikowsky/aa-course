<?php

namespace Accounting\Controller;

use Accounting\Entity\Balance;
use Accounting\Entity\Transaction;
use Accounting\Entity\User;
use Accounting\Repository\BalanceRepository;
use Accounting\Repository\TransactionRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[IsGranted('ROLE_USER')]
class TransactionController
{
    private TransactionRepository $transactionRepository;
    private TokenStorageInterface $tokenStorage;
    private BalanceRepository $balanceRepository;

    public function __construct(
        TransactionRepository $transactionRepository,
        TokenStorageInterface $tokenStorage,
        BalanceRepository $balanceRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->tokenStorage = $tokenStorage;
        $this->balanceRepository = $balanceRepository;
    }

    #[Route('/api/transactions', name: 'transaction_list')]
    public function transactionList(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user->isManager()) {
            $transactions = $this->transactionRepository->findAll();
        } else {
            $transactions = $this->transactionRepository->findBy([
                'user' => $user,
            ]);
        }

        $transactions = array_map(function (Transaction $transaction) {
            return $transaction->toArray();
        }, $transactions);

        return new JsonResponse($transactions);
    }

    #[Route('/api/balances', name: 'balance_list')]
    public function balanceList(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user->isManager()) {
            $balances = $this->balanceRepository->findAll();
        } else {
            $balances = $this->balanceRepository->findBy([
                'user' => $user,
            ]);
        }

        $balances = array_map(function (Balance $balance) {
            return $balance->toArray();
        }, $balances);

        return new JsonResponse($balances);
    }
}