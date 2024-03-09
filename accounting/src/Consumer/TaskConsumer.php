<?php

namespace Accounting\Consumer;

use Accounting\Entity\Task;
use Accounting\Repository\TaskRepository;
use Accounting\Service\Task\TaskService;
use Accounting\Service\Transaction\TaskTransactionService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
  
class TaskConsumer implements ConsumerInterface
{
    private const TASK_ASSIGNED_EVENT_NAME = 'Task.Assigned';
    private const TASK_COMPLETED_EVENT_NAME = 'Task.Completed';
    private TaskTransactionService $taskTransactionService;
    private TaskService $taskService;
    private TaskRepository $taskRepository;

    public function __construct(
        TaskTransactionService $taskTransactionService,
        TaskService $taskService,
        TaskRepository $taskRepository
    ) {
        $this->taskTransactionService = $taskTransactionService;
        $this->taskService = $taskService;
        $this->taskRepository = $taskRepository;
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
                    $task = $this->taskRepository->findOneBy([
                        'publicId' => $content['data']['public_id']
                    ]) ?? new Task();

                    $this->taskTransactionService->createAssignedTaskTransaction($content['data']);
                    $this->taskService->updateStatus($task, $content['data']['status']);
                }

                break;

            case self::TASK_COMPLETED_EVENT_NAME:
                if ($content['data']['status'] === 'completed') {
                    $task = $this->taskRepository->findOneBy([
                            'publicId' => $content['data']['public_id']
                        ]) ?? new Task();


                    $this->taskTransactionService->createCompletedTaskTransaction($content['data']);
                    $this->taskService->updateStatus($task, $content['data']['status']);
                }

                break;
        }
        return true;
    }
}