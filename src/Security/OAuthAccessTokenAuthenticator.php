<?php

declare(strict_types=1);

namespace ControleOnline\Security;

use ControleOnline\Entity\User;
use ControleOnline\Service\OAuthService;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class OAuthAccessTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PeopleRoleService $peopleRoleService,
        private readonly OAuthService $oauthService,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/mcp'
            && preg_match('/^Bearer\\s+\\S+/i', trim((string) $request->headers->get('Authorization', ''))) === 1;
    }

    public function authenticate(Request $request): Passport
    {
        $authorization = trim((string) $request->headers->get('Authorization', ''));
        if (!preg_match('/^Bearer\\s+(\\S+)$/i', $authorization, $matches)) {
            throw new CustomUserMessageAuthenticationException('Invalid access token');
        }

        try {
            $claims = $this->oauthService->resolveAccessToken($matches[1]);
        } catch (\InvalidArgumentException) {
            throw new CustomUserMessageAuthenticationException('Invalid access token');
        }
        if (!in_array('mcp:read', preg_split('/\\s+/', (string) ($claims['scope'] ?? '')) ?: [], true)) {
            throw new CustomUserMessageAuthenticationException('Insufficient scope');
        }

        $userId = (int) $claims['sub'];

        return new Passport(
            new UserBadge('oauth-user:' . $userId, function () use ($userId): User {
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                if (!$user instanceof User
                    || $user->getPeople() === null
                    || !$user->getPeople()->getEnabled()) {
                    throw new CustomUserMessageAuthenticationException('Invalid access token');
                }

                $user->setResolvedRoles($this->peopleRoleService->getGrantedRoles($user->getPeople()));

                return $user;
            }),
            new CustomCredentials(
                static fn (mixed $credentials): bool => true,
                $claims
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
        return new JsonResponse(
            ['error' => 'invalid_token'],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => 'Bearer error=\"invalid_token\"']
        );
    }
}
