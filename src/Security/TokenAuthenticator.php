<?php

namespace ControleOnline\Security;

use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TokenAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(private EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function supports(Request $request): ?bool
    {
        return $this->getKey($request) !== null;
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $this->getKey($request);
        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        return new Passport(
            new UserBadge($apiToken, function ($apiToken) {
                return $this->em->getRepository(User::class)->findOneBy(['apiKey' => $apiToken]);
            }),
            new CustomCredentials(
                function ($credentials, UserInterface $user) {
                    return true;
                },
                $apiToken
            )
        );
    }

    public function createAuthenticatedToken(Passport $passport, string $firewallName): ?TokenInterface
    {
        $user = $passport->getUser();
        return new UsernamePasswordToken($user, $firewallName, $user->getRoles());
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return $this->createAuthenticatedToken($passport, $firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = ['message' => strtr($exception->getMessageKey(), $exception->getMessageData())];
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $data = ['message' => 'Authentication Required'];
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    private function getKey(Request $request)
    {
        return $request->headers->get('Authorization') ?? $request->headers->get('API-TOKEN') ?? $request->headers->get('API-KEY');
    }
}