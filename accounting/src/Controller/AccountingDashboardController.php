<?php

namespace Accounting\Controller;

use Accounting\Repository\TransactionRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_USER')]
class AccountingDashboardController
{
    private TransactionRepository $transactionRepository;

    public function __construct(
        TransactionRepository $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/dashboard/daily_earnings', name: 'dashboard_daily_earnings')]
    public function getDailyEarnings(): JsonResponse
    {
        $data = $this->transactionRepository->calculateDailyEarnings()[0];
        $data['balance'] = bcadd((string) $data['deposit_withdraw'], (string) $data['deposit_refund'], 3);

        return new JsonResponse($data);
    }
}