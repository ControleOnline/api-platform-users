<?php

declare(strict_types=1);

namespace ControleOnline\Tests\Security;

use ControleOnline\Security\OAuthAccessTokenAuthenticator;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\OAuthService;
use ControleOnline\Service\PeopleRoleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class OAuthAccessTokenAuthenticatorTest extends TestCase
{
    private function authenticator(): OAuthAccessTokenAuthenticator
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $domainService = new DomainService($entityManager, new RequestStack());
        $oauthService = new OAuthService(
            'test-secret',
            'https://api.controleonline.com',
            ['mcp:read'],
            $domainService,
            $this->createMock(CacheItemPoolInterface::class),
            new LockFactory(new InMemoryStore()),
        );

        return new OAuthAccessTokenAuthenticator(
            $entityManager,
            $this->createMock(PeopleRoleService::class),
            $oauthService,
        );
    }

    public function testSupportsBareAndTenantScopedMcpPathsWithBearerTokens(): void
    {
        $authenticator = $this->authenticator();

        foreach (['/mcp', '/mcp/app.controleonline.com'] as $path) {
            $request = Request::create($path, 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer signed-token']);
            self::assertTrue($authenticator->supports($request), $path);
        }
    }

    public function testDoesNotSupportOtherPathsOrRequestsWithoutBearerTokens(): void
    {
        $authenticator = $this->authenticator();
        $otherPath = Request::create('/mcp/app.controleonline.com/extra', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer signed-token']);
        $missingBearer = Request::create('/mcp/app.controleonline.com', 'POST');

        self::assertFalse($authenticator->supports($otherPath));
        self::assertFalse($authenticator->supports($missingBearer));
    }
}
