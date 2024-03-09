<?php

namespace Accounting\Command;

use Accounting\Entity\Balance;
use Accounting\Repository\BalanceRepository;
use Accounting\Service\Transaction\TaskTransactionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DailyPayoutCommand extends Command
{
    protected static $defaultName = 'app:daily-payout';
    private TaskTransactionService $taskTransactionService;
    private BalanceRepository $balanceRepository;

    public function __construct(
        TaskTransactionService $taskTransactionService,
        BalanceRepository $balanceRepository
    ) {
        parent::__construct();
        $this->taskTransactionService = $taskTransactionService;
        $this->balanceRepository = $balanceRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateFrom = new \DateTime('yesterday');
        $dateTo = clone $dateFrom;

        $dateFrom->setTime(0, 0, 0);
        $dateTo->setTime(23, 59, 59);

        $balances = $this->balanceRepository->findAll();

        /** @var Balance $balance */
        foreach ($balances as $balance) {
            if (bccomp($balance->getAmount(), '0', 3) === 1) {
                $this->taskTransactionService->createPayoutTransaction($balance->getUser(), $balance->getAmount());
            }
        }

        return Command::SUCCESS;
    }
}