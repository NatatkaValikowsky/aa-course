<?php

namespace Accounting\Consumer;

use Accounting\Entity\User;
use Accounting\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class UserStreamConsumer implements ConsumerInterface
{
    private const USER_CREATED_EVENT = 'User.Created';
    private const USER_UPDATED_EVENT = 'User.Updated';

    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function execute(AMQPMessage $msg)
    {
        $content = json_decode($msg->body, true);

        if (!isset($content['event']) || !isset($content['user'])) {
            return true;
        }

        if (!in_array($content['event'], [
            self::USER_UPDATED_EVENT,
            self::USER_CREATED_EVENT
        ])) {
            return true;
        }

        if ($content['event'] === self::USER_UPDATED_EVENT) {
            $userPublicId = $content['user']['public_id'];
            $user = $this->userRepository->findOneBy([
                'publicId' => $userPublicId,
            ]);

            if ($user === null) {
                return true;
            }
        } else {
            $user = new User();
            $user->setEmail($content['user']['email']);
            $user->setPublicId($content['user']['public_id']);
        }

        $user->setName($content['user']['name']);
        $user->setRoles($content['user']['roles']);
        $user->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->logger->info('Created/updated user');

        return true;
    }
}