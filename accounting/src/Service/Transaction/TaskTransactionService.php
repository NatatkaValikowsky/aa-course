<?php

namespace Accounting\Service\Transaction;

use Accounting\Entity\Transaction;
use Accounting\Entity\User;
use Accounting\Repository\UserRepository;
use Accounting\Service\Balance\BalanceService;
use Accounting\Service\Cost\TaskCostService;
use Doctrine\ORM\EntityManagerInterface;
use JsonSchema\Validator;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

class TaskTransactionService
{
    private TaskCostService $costService;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private BalanceService $balanceService;
    private Validator $validator;
    private ProducerInterface $transactionProducer;
    private MailerInterface $mailer;

    public function __construct(
        TaskCostService $costService,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        BalanceService $balanceService,
        ProducerInterface $transactionProducer,
        MailerInterface $mailer
    ) {
        $this->costService = $costService;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->balanceService = $balanceService;
        $this->transactionProducer = $transactionProducer;
        $this->mailer = $mailer;

        $this->validator = new Validator();
    }

    public function createAssignedTaskTransaction(array $data): void
    {
        $user = $this->userRepository->findOneBy([
            'publicId' => $data['assignee'],
        ]);

        $transaction = new Transaction();
        $transaction->setAmount($this->costService->getTaskCost());
        $transaction->setUser($user);
        $transaction->setMeta([
            'task_public_id' => $data['public_id'],
            'task_name' => $data['name'],
            'task_jira_id' => $data['jira_id'],
        ]);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->balanceService->updateBalance($user, $transaction);
        $this->publishEvent($transaction);
    }

    public function createCompletedTaskTransaction(array $data): void
    {
        $user = $this->userRepository->findOneBy([
            'publicId' => $data['completed_by'],
        ]);

        $transaction = new Transaction();
        $transaction->setAmount($this->costService->getCompletedTaskCost());
        $transaction->setUser($user);
        $transaction->setMeta([
            'task_public_id' => $data['public_id'],
            'task_name' => $data['name'],
            'task_jira_id' => $data['jira_id'],
        ]);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->balanceService->updateBalance($user, $transaction);
        $this->publishEvent($transaction);
    }

    public function createPayoutTransaction(User $user, string $amount): void
    {
        $transaction = new Transaction();
        $transaction->setType(Transaction::TYPE_PAYOUT);
        $transaction->setAmount(bcmul($amount, '-1', '3'));
        $transaction->setUser($user);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->balanceService->updateBalance($user, $transaction);
        $this->publishEvent($transaction);
        $this->sendPayoutEmail($user, $transaction);
    }

    private function publishEvent(Transaction $transaction): void
    {
        $eventData = json_encode([
            'event_id' => Uuid::v4()->toRfc4122(),
            'event_version' => 1,
            'event_name' => 'Task.Created',
            'event_time' => (string) time(),
            'event_producer' => get_class($this->transactionProducer),
            'data' => [
                'public_id' => $transaction->getPublicId(),
                'type' => $transaction->getType(),
                'status' => $transaction->getStatus(),
                'user_public_id' => $transaction->getUser()->getPublicId(),
                'created_at' => $transaction->getCreatedAt()->format(DATE_ATOM),
                'meta' => $transaction->getMeta(),
            ]
        ]);

        $validationData = json_decode($eventData);
        $this->validator->validate(
            $validationData,
            (object)['$ref' => 'file://' . realpath('../../json-schema/transaction/created/1.json')]
        );

        if (!$this->validator->isValid()) {
            throw new HttpException(422, 'Invalid event schema');
        }

        $this->transactionProducer->publish($eventData, 'transaction_stream');
    }

    private function sendPayoutEmail(User $user, Transaction $transaction): void
    {
        $email = (new Email())
            ->from('admin@ates.com')
            ->to($user->getEmail())
            ->subject('Payout')
            ->text(sprintf(
                'A payout transaction with amount %s was proceed',
                bcmul($transaction->getAmount(), '-1', '3'))
            );

        $this->mailer->send($email);
    }
}