<?php
namespace App\Controller;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonSchema\Validator;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
#[IsGranted('ROLE_ADMIN')]
class UserController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private ProducerInterface $userProducer;
    private Validator $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ProducerInterface $userProducer
    ) 
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->userProducer = $userProducer;

        $this->validator = new Validator();
    }

    #[Route('/api/user/create', name: 'user_create', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
        }
        if (!isset($data['email'])) {
            throw new HttpException(400, 'Email is required');
        }
        $user = new User();
        $user->setEmail($data['email']);
        $user->setRoles($data['roles'] ?? []);
        $user->setName($data['name'] ?? sprintf('User%s', rand(0,100)));

        $eventData = json_encode([
            'event' => 'User.Created',
            'user' => $user->toArray(),
        ]);

        $validationData = json_decode($eventData);
        $this->validator->validate(
            $validationData,
            (object)['$ref' => 'file://' . realpath('../../json-schema/user/created/1.json')]
        );

        if (!$this->validator->isValid()) {
            throw new HttpException(422, 'Invalid event schema');
        }

        try {
            $this->entityManager->persist($user);

    
     public function createUser(Request $request): JsonResponse
  
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }

        $this->userProducer->publish($eventData, 'user_stream');

        return new JsonResponse([
            'public_id' => $user->getPublicId(),
        ]);

    #[Route('/api/user/{id}/update', name: 'user_update', methods: ['POST'])]
    public function updateUser(Request $request, string $id): JsonResponse
    {
        $user = $this->userRepository->findOneBy([
            'publicId' => $id,
        ]);
        if (!$user instanceof User) {
            throw new HttpException(422, 'Invalid user');
        }

        $data = json_decode($request->getContent(), true);
        $roleChanged = false;

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
    
    public function updateUser(Request $request, string $id): JsonResponse
  
        }
        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
            $roleChanged = true;
        }

        $user->setUpdatedAt(new \DateTime());
    }
    
    public function updateUser(Request $request, string $id): JsonResponse
  
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }

        $userUpdatedEventData = json_encode([
            'event' => 'User.Updated',
            'user' => $user->toArray(),
        ]);

        $validationData = json_decode($userUpdatedEventData);
        $this->validator->validate(
            $validationData,
            (object)['$ref' => 'file://' . realpath('../../json-schema/user/updated/1.json')]
        );

        if (!$this->validator->isValid()) {
            throw new HttpException(422, 'Invalid event schema');
        }

        $this->userProducer->publish($userUpdatedEventData, 'user_stream');

        if ($roleChanged) {
            $roleChangedEventData = json_encode([
                'event' => 'User.RoleChanged',
                'user' => [
                    'public_id' => $user->getPublicId(),
                    'roles' => $user->getRoles(),
                ],
            ]);

            $validationData = json_decode($roleChangedEventData);
            $this->validator->validate(
                $validationData,
                (object)['$ref' => 'file://' . realpath('../../json-schema/user/role/changed/1.json')]
            );

            if (!$this->validator->isValid()) {
                throw new HttpException(422, 'Invalid event schema');
            }

            $this->userProducer->publish($roleChangedEventData, 'user');
        }

        return new JsonResponse([
            'public_id' => $user->getPublicId(),
        ]);
    }