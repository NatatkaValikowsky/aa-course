<?php

namespace Task\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private const AUTH_URL = 'http://localhost:8000/api/user/me';

    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse([
            'message' => 'Auth header required.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function supports(Request $request)
    {
        return true;
    }

    public function getCredentials(Request $request)
    {
        $token = $request->headers->get('auth-token');

        if (!isset($token)) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Auth header required');
        }

        $response = $this->client->request(
            'GET',
            self::AUTH_URL,
            [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $token),
                ]
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        $userData = json_decode($response->getContent(), true);

        if (!is_array($userData) || !isset($userData['public_id'])) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Invalid credentials');
        }

        return $userData['public_id'];
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
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        return null;
    }

    public function supportsRememberMe()
    {
        return false;
    }
}