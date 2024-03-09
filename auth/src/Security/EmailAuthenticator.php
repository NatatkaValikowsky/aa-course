<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class EmailAuthenticator extends AbstractGuardAuthenticator
{
    private AuthenticationSuccessHandler $successHandler;
    private AuthenticationFailureHandler $failureHandler;

    public function __construct(
        AuthenticationSuccessHandler $successHandler,
        AuthenticationFailureHandler $failureHandler
    ) {
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
    }

    public function supports(Request $request)
    {
        return true;
    }

    public function getCredentials(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'Invalid json');
        }

        if (!isset($data['username'])) {
            throw new HttpException(400, 'Username is required');
        }

        return $data['username'];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) return null;

        return $userProvider->loadUserByUsername($credentials);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new Response('Auth header required', 401);
    }
}