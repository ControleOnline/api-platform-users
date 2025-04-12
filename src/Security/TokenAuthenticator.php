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
        error_log('TokenAuthenticator::construct called');
    }

    public function supports(Request $request): ?bool
    {
        error_log('TokenAuthenticator::supports called for path: ' . $request->getPathInfo());

        $token = new NullToken();
        $isPublic = $١AccessDecisionManager->decide($token, ['PUBLIC_ACCESS'], $request);
        error_log('Public access check: ' . ($isPublic ? 'true' : 'false'));

        if ($isPublic) {
            error_log('Skipping authentication for public route');
            return false;
        }

        $key = $this->getKey($request);
        error_log('API key: ' . ($key !== null ? 'present' : 'missing'));

        return $key !== null && !empty(trim($key));
    }

    public function authenticate(Request $request): Passport
    {
        error_log('TokenAuthenticator::authenticate called');

        $apiToken = $this->getKey($request);
        if (null === $apiToken) {
            error_log('No API token provided');
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        error_log('Attempting to load user with API token');
        return new Passport(
            new UserBadge($apiToken, function ($apiToken) {
                $user = $this->em->getRepository(User::class)->findOneBy(['apiKey' => $apiToken]);
                if (null === $user) {
                    error_log('Invalid API token');
                    throw new CustomUserMessageAuthenticationException('Invalid API token');
                }
                error_log('User found: ' . $user->getUsername());
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
        error_log('Creating token for user: ' . $user->getUsername());
        return new UsernamePasswordToken($user, $firewallName, $user->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        error_log('Authentication successful for user: ' . $token->getUserIdentifier());
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        error_log('Authentication failed: ' . $exception->getMessage());
        return new JsonResponse(['message' => 'Authentication failed'], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        error_log('Authentication required for path: ' . $request->getPathInfo());
        return new JsonResponse(['message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
    }

    private function getKey(Request $request): ?string
    {
        $key = $request->headers->get('API-KEY') ?? $request->headers->get('API-TOKEN');
        error_log('Checking API key in headers: ' . ($key !== null ? 'present' : 'missing'));
        return $key;
    }
}