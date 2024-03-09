<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findUserByEmail(string $email): ?User
    {
        return $this
            ->createQueryBuilder('u')
            ->select()
            ->where('u.email = :email')
            ->setMaxResults(1)
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }
}