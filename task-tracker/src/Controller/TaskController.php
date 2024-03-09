<?php
namespace Task\Controller;

use Doctrine\ORM\EntityManagerInterface;
use JsonSchema\Validator;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Uuid;
use Task\Entity\Task;
use Task\Repository\TaskRepository;
use Task\Service\User\AssigneeResolver;
  
#[IsGranted('ROLE_USER')]
class TaskController
{
    private TaskRepository $taskRepository;
    private TokenStorageInterface $tokenStorage;
    private AssigneeResolver $assigneeResolver;
    private EntityManagerInterface $entityManager;
    private ProducerInterface $taskProducer;
    private Validator $validator;

    public function __construct(
        TaskRepository $taskRepository,
        TokenStorageInterface $tokenStorage,
        AssigneeResolver $assigneeResolver,
        EntityManagerInterface $entityManager,
        ProducerInterface $taskProducer
    ) {
        $this->taskRepository = $taskRepository;
        $this->tokenStorage = $tokenStorage;
        $this->assigneeResolver = $assigneeResolver;
        $this->entityManager = $entityManager;
        $this->taskProducer = $taskProducer;

        $this->validator = new Validator();
    }

    #[Route('/api/tasks', name: 'task_list')]
    public function taskList(Request $request): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if ($user->canManageTasks()) {
            $tasks = $this->taskRepository->findAll();
        } else {
            $tasks = $this->taskRepository->findBy([
                'assignee' => $user,
            ]);
        }
        $tasks = array_map(function (Task $task) {
            return $task->toArray();
        }, $tasks);
        return new JsonResponse($tasks);
    }
    #[Route('/api/task/create', name: 'create_task', methods: ['POST'])]
    public function createTask(Request $request): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
        }
        if (!isset($data['name'])) {
            throw new HttpException(400, 'Invalid json');
        }

        if (str_contains($data['name'], '[') || str_contains($data['name'], ']')) {
            throw new HttpException(422, 'Invalid name');
        }

        if (!isset($data['jira_id'])) {
            throw new HttpException(400, 'Invalid json');
        }

        $task = new Task();
        $task->setName($data['name']);
        $task->setJiraId($data['jira_id']);
        $task->setOwner($user);
        $task->setAssignee($this->assigneeResolver->getRandomAssignee());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $eventData = json_encode([
            'event_id' => Uuid::v4()->toRfc4122(),
            'event_version' => 2,
            'event_name' => 'Task.Created',
            'event_time' => (string) time(),
            'event_producer' => get_class($this->taskProducer),
            'data' => $task->toArray(),
        ]);

        $validationData = json_decode($eventData);
        $this->validator->validate(
            $validationData,
            (object)['$ref' => 'file://' . realpath('../../json-schema/task/created/2.json')]
        );

        if (!$this->validator->isValid()) {
            throw new HttpException(422, 'Invalid event schema');
        }

        $this->taskProducer->publish($eventData, 'task_stream');

        return new JsonResponse($task->toArray());
    }
  
    #[Route('/api/task/{id}/complete', name: 'complete_task', methods: ['POST'])]
    public function completeTask(Request $request, string $id): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $task = $this->taskRepository->findOneBy([
            'publicId' => $id,
        ]);
        if (!$task instanceof Task) {
            throw new HttpException(422, 'Invalid user');
        }
        if ($task->getAssignee()->getId() !== $user->getId()) {
            throw new HttpException(401, 'Not granted');
        }
        if (!$task->mightBeMarkedAsCompleted()) {
            throw new HttpException(401, 'Not granted');
        }
        $task->complete();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $eventData = json_encode([
            'event_id' => Uuid::v4()->toRfc4122(),
            'event_version' => 2,
            'event_name' => 'Task.Completed',
            'event_time' => (string) time(),
            'event_producer' => get_class($this->taskProducer),
            'data' => [
                'public_id' => $task->getPublicId(),
                'status' => $task->getStatus(),
                'completed_by' => $task->getAssignee()->getPublicId(),
                'name' => $task->getName(),
                'jira_id' => $task->getJiraId(),
            ],
        ]);

        $validationData = json_decode($eventData);
        $this->validator->validate(
            $validationData,
            (object)['$ref' => 'file://' . realpath('../../json-schema/task/completed/2.json')]
        );

        if (!$this->validator->isValid()) {
            throw new HttpException(422, 'Invalid event schema');
        }

        $this->taskProducer->publish($eventData, 'task_lifecycle');

        return new JsonResponse($task->toArray());
    }
  
    #[Route('/api/tasks/reassign', name: 'task_reassign')]
    public function taskMassReassign(Request $request): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user->canManageTasks()) {
            throw new HttpException(401, 'Not granted');
        }
        $tasks = $this->taskRepository->findBy([
            'status' => Task::STATUS_ASSIGNED,
        ]);
        /** @var Task $task */
        foreach ($tasks as $task) {
            $task->setAssignee($this->assigneeResolver->getRandomAssignee());
            $task->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($task);
            $this->entityManager->flush();

            $eventData = json_encode([
                'event_id' => Uuid::v4()->toRfc4122(),
                'event_version' => 2,
                'event_name' => 'Task.Assigned',
                'event_time' => (string) time(),
                'event_producer' => get_class($this->taskProducer),
                'data' => [
                    'public_id' => $task->getPublicId(),
                    'status' => $task->getStatus(),
                    'assignee' => $task->getAssignee()->getPublicId(),
                ],
            ]);

            $validationData = json_decode($eventData);
            $this->validator->validate(
                $validationData,
                (object)['$ref' => 'file://' . realpath('../../json-schema/task/assigned/2.json')]
            );

            if (!$this->validator->isValid()) {
                throw new HttpException(422, 'Invalid event schema');
            }

            $this->taskProducer->publish($eventData, 'task_lifecycle');
        }

        return new JsonResponse([]);
    }
}