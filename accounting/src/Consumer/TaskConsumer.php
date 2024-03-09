<?php

namespace Accounting\Consumer;

use Accounting\Service\Transaction\TaskTransactionService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class TaskConsumer implements ConsumerInterface
{
    private const TASK_ASSIGNED_EVENT_NAME = 'Task.Assigned';
    private const TASK_COMPLETED_EVENT_NAME = 'Task.Completed';
    private TaskTransactionService $taskTransactionService;

    public function __construct(TaskTransactionService $taskTransactionService)
    {
        $this->taskTransactionService = $taskTransactionService;
    }

    public function execute(AMQPMessage $msg)
    {
        $content = json_decode($msg->body, true);

        if (!isset($content['event_name'])) {
            return true;
        }

        switch ($content['event_name']) {
            case self::TASK_ASSIGNED_EVENT_NAME:
                if ($content['data']['status'] === 'assigned') {
                    $this->taskTransactionService->createAssignedTaskTransaction($content['data']);
                }

                break;

            case self::TASK_COMPLETED_EVENT_NAME:
                if ($content['data']['status'] === 'completed') {
                    $this->taskTransactionService->createCompletedTaskTransaction($content['data']);
                }

                break;
        }

        return true;
    }
}