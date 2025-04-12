<?php

namespace ControleOnline\Security;

use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class TokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private $em;
    private $accessDecisionManager;

    public function __construct(EntityManagerInterface $em, AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->em = $em;
        $this->accessDecisionManager = $accessDecisionManager;
    }

    public function supports(Request $request): ?bool
    {
        $token = new NullToken();
        $isPublic = $this->accessDecisionManager->decide($token, ['PUBLIC_ACCESS'], $request);
        error_log('aqui');
        if ($isPublic)
            return false;

        $key = $this->getKey($request);
        error_log($key);
        return $key !== null && !empty(trim($key));
    }

    public function authenticate(Request $request): Passport
    {
        error_log('aqui');
        $apiToken = $this->getKey($request);
        if (null === $apiToken)
            throw new CustomUserMessageAuthenticationException('No API token provided');


        return new Passport(
            new UserBadge($apiToken, function ($apiToken) {
                $user = $this->em->getRepository(User::class)->findOneBy(['apiKey' => $apiToken]);
                if (null === $user) 
                    throw new CustomUserMessageAuthenticationException('Invalid API token');
                

                return $user;
            }),
            new CustomCredentials(
                fn($credentials, $user) => true,
                $apiToken
            )
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();
        return new UsernamePasswordToken($user, $firewallName, $user->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => 'Authentication failed'], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
    }

    private function getKey(Request $request): ?string
    {
        return $request->headers->get('API-KEY') ?? $request->headers->get('API-TOKEN');
    }
}
