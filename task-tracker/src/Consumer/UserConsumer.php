<?php

namespace Task\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class UserConsumer implements ConsumerInterface
{
    private const USER_ROLE_CHANGED_EVENT = 'User.RoleChanged';

    public function execute(AMQPMessage $msg)
    {
        $content = json_decode($msg->body, true);

        return true;
    }
}