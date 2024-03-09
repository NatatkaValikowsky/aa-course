<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[IsGranted('ROLE_USER')]
class SecurityController
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }
    #[Route('/api/user/me', name: 'user_current')]
    public function getCurrentUser(Request $request): JsonResponse
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null || $token->getUser() === null) {
            throw new HttpException(403, 'Missing credentials');
        }

        return new JsonResponse($token->getUser()->toArray());
    }
}