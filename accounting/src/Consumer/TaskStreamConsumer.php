<?php

namespace Accounting\Consumer;

use Accounting\Service\Transaction\TaskTransactionService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class TaskStreamConsumer implements ConsumerInterface
{
    private const TASK_CREATED_EVENT_NAME = 'Task.Created';
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
            case self::TASK_CREATED_EVENT_NAME:
                if ($content['data']['status'] === 'assigned') {
                    $this->taskTransactionService->createAssignedTaskTransaction($content['data']);
                }

                break;
        }

        return true;
    }
}