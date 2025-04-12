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
use Psr\Log\LoggerInterface;

class TokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private $em;
    private $accessDecisionManager;
    private $logger;

    public function __construct(
        EntityManagerInterface $em, 
        AccessDecisionManagerInterface $accessDecisionManager, 
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->logger = $logger;
    }

    public function supports(Request $request): ?bool
    {
        $this->logger->info('TokenAuthenticator::supports called', [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
        ]);

        $token = new NullToken();
        $isPublic = $this->accessDecisionManager->decide($token, ['PUBLIC_ACCESS'], $request);
        
        $this->logger->info('Public access check', [
            'is_public' => $isPublic,
        ]);

        if ($isPublic) {
            $this->logger->info('Skipping authentication for public route');
            return false;
        }

        $key = $this->getKey($request);
        $this->logger->info('API key check', [
            'key_present' => $key !== null,
            'key_empty' => empty(trim($key ?? '')),
        ]);

        return $key !== null && !empty(trim($key));
    }

    public function authenticate(Request $request): Passport
    {
        $this->logger->info('TokenAuthenticator::authenticate called');

        $apiToken = $this->getKey($request);
        if (null === $apiToken) {
            $this->logger->warning('No API token provided');
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $this->logger->info('Attempting to load user with API token');

        return new Passport(
            new UserBadge($apiToken, function ($apiToken) {
                $user = $this->em->getRepository(User::class)->findOneBy(['apiKey' => $apiToken]);
                if (null === $user) {
                    $this->logger->warning('Invalid API token');
                    throw new CustomUserMessageAuthenticationException('Invalid API token');
                }
                
                $this->logger->info('User found', [
                    'username' => $user->getUsername(),
                ]);
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
        $this->logger->info('Creating token for user', [
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
        ]);
        return new UsernamePasswordToken($user, $firewallName, $user->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->info('Authentication successful', [
            'username' => $token->getUserIdentifier(),
        ]);
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error('Authentication failed', [
            'message' => $exception->getMessage(),
        ]);
        return new JsonResponse(['message' => 'Authentication failed'], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $this->logger->warning('Authentication required', [
            'path' => $request->getPathInfo(),
        ]);
        return new JsonResponse(['message' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
    }

    private function getKey(Request $request): ?string
    {
        $key = $request->headers->get('API-KEY') ?? $request->headers->get('API-TOKEN');
        $this->logger->info('Checking API key in headers', [
            'api_key' => $key !== null ? 'present' : 'missing',
        ]);
        return $key;
    }
}