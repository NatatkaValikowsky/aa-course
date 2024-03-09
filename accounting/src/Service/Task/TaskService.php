<?php

namespace Accounting\Service\Task;

use Accounting\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;

class TaskService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function createTask(array $data): void
    {
        $task = new Task();
        $task->setName($data['name']);
        $task->setJiraId($data['jira_id']);
        $task->setPublicId($data['public_id']);
        $task->setStatus($data['status']);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function updateStatus(Task $task, string $status): void
    {
        $task->setStatus($status);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }
}