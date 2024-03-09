<?php

namespace Accounting\Controller;

use Accounting\Entity\Transaction;
use Accounting\Repository\TransactionRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_ADMIN')]
class AnalyticsDashboardController
{
    private TransactionRepository $transactionRepository;

    public function __construct(
        TransactionRepository $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    #[Route('/api/analytics/user_earnings', name: 'analytics_user_earnings')]
    public function getUserEarnings(): JsonResponse
    {
        $dateFrom = new \DateTime();
        $dateTo = clone $dateFrom;

        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(23, 59, 59);

        $userEarnings = $this->transactionRepository->calculateDayUserEarnings($dateFrom, $dateTo);

        $data = [
            'earnings' => [],
            'total' => '0',
            'negative_balance_users_count' => 0,
        ];

        foreach ($userEarnings as $userEarning) {
            $userEarning['balance'] = bcadd((string) $userEarning['deposit_withdraw'], (string) $userEarning['deposit_refund'], 3);

            $data['earnings'][] = $userEarning;
            $data['total'] = bcadd($data['total'], $userEarning['balance'], 3);

            if (bccomp($userEarning['balance'], '0', 2) === -1) {
                $data['negative_balance_users_count']++;
            }
        }

        return new JsonResponse($data);
    }

    #[Route('/api/analytics/highest_cost_task', name: 'analytics_highest_cost_task')]
    public function getHighestCostTaskForPeriod(Request $request): JsonResponse
    {
        $dateFrom = $request->query->get('from', 'now');
        $dateTo = $request->query->get('to', 'now');

        $dateFrom = new \DateTime($dateFrom);
        $dateTo = new \DateTime($dateTo);

        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(23, 59, 59);

        $data = $this->transactionRepository->findHighestCostTaskForPeriod($dateFrom, $dateTo);
        /** @var Transaction $transaction */
        $transaction = $data[0][0];

        $data = [
            'amount' => $transaction->getAmount(),
            'task_public_id' => $transaction->getMeta()['task_public_id'] ?? null,
            'task_name' => $transaction->getMeta()['task_name'] ?? null,
            'task_jira_id' => $transaction->getMeta()['task_jira_id'] ?? null,
        ];

        return new JsonResponse($data);
    }
}