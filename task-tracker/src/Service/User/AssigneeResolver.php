<?php

namespace Task\Service\User;

use Task\Entity\User;
use Task\Repository\UserRepository;

class AssigneeResolver
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getRandomAssignee(): User
    {
        $users = $this->userRepository->findAll();
        $users = array_filter($users, function (User $user) {
            return $user->mightBeAssignedToTask();
        });

        return $users[array_rand($users)];
    }
}