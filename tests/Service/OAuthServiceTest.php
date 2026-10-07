<?php

declare(strict_types=1);

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Controller\OAuthMetadataController;
use ControleOnline\EventListener\OAuthTenantRequestListener;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\OAuthService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\HttpFoundation\Request;

final class OAuthServiceTest extends TestCase
{
    private OAuthService $oauthService;
    private DomainService $domainService;
    private string $authorizedTenant = 'tenant-a.example';
    private string $mainDomain = 'api.controleonline.com';

    protected function setUp(): void
    {
        $domainService = $this->createStub(DomainService::class);
        $domainService->method('getDomain')->willReturnCallback(fn (): string => $this->authorizedTenant);
        $domainService->method('getMainDomain')->willReturnCallback(fn (): string => $this->mainDomain);
        $this->domainService = $domainService;

        $this->oauthService = new OAuthService(
            'unit-test-oauth-signing-secret',
            'api.controleonline.com',
            ['mcp:read'],
            $domainService,
            new ArrayAdapter(),
            new LockFactory(new FlockStore()),
            ['mcp:read']
        );
    }

    public function testRejectsRedirectsThatCouldLeaveTheClientOriginUntrusted(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->oauthService->registerClient([
            'client_name' => 'Untrusted client',
            'redirect_uris' => ['https://attacker.example/callback', 'http://evil.example/callback'],
        ]);
    }

    public function testAuthorizationCodeRequiresPkceAndIsBoundToTenantAndClient(): void
    {
        $client = $this->oauthService->registerClient([
            'client_name' => 'Desktop MCP',
            'redirect_uris' => ['http://localhost:43123/callback'],
        ]);
        $verifier = str_repeat('a', 43);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $authorizationRequest = [
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $client['redirect_uris'][0],
            'resource' => $this->oauthService->protectedResourceIdentifier('tenant-a.example'),
            'state' => bin2hex(random_bytes(16)),
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:read',
        ];
        $authorization = $this->oauthService->authorize($this->activeUser(42), [
            ...$authorizationRequest,
            'decision' => 'approve',
        ]);
        parse_str((string) parse_url($authorization['redirect_to'], PHP_URL_QUERY), $callback);

        $code = $callback['code'];
        self::assertSame($authorizationRequest['state'], $callback['state']);
        self::assertSame('tenant-a.example', $this->oauthService->resolveTenantFromAuthorizationCode($code));

        $tokenResponse = $this->oauthService->exchangeAuthorizationCode([
            'grant_type' => 'authorization_code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $authorizationRequest['redirect_uri'],
            'code' => $code,
            'code_verifier' => $verifier,
        ]);
        self::assertSame('Bearer', $tokenResponse['token_type']);
        self::assertSame(900, $tokenResponse['expires_in']);

        $claims = $this->oauthService->resolveAccessToken($tokenResponse['access_token']);
        self::assertSame(42, $claims['sub']);
        self::assertSame('tenant-a.example', $claims['tenant']);
        self::assertSame('mcp:read', $claims['scope']);

        $this->expectException(\InvalidArgumentException::class);
        $this->oauthService->exchangeAuthorizationCode([
            'grant_type' => 'authorization_code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $authorizationRequest['redirect_uri'],
            'code' => $code,
            'code_verifier' => $verifier,
        ]);
    }

    public function testAuthorizationDefaultsToTheConfiguredLeastPrivilegeScope(): void
    {
        $client = $this->oauthService->registerClient([
            'client_name' => 'MCP client without explicit scope',
            'redirect_uris' => ['http://localhost:43125/callback'],
        ]);

        $authorization = $this->oauthService->validateAuthorizationRequest([
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $client['redirect_uris'][0],
            'state' => 'state-default-scope',
            'code_challenge' => str_repeat('a', 43),
            'code_challenge_method' => 'S256',
        ]);

        self::assertSame('mcp:read', $authorization['scope']);
        self::assertSame($this->oauthService->getIssuer() . '/mcp', $authorization['resource']);
    }

    public function testPublicClientCanRevokeItsOwnAccessToken(): void
    {
        $client = $this->oauthService->registerClient([
            'client_name' => 'Desktop MCP',
            'redirect_uris' => ['http://localhost:43123/callback'],
        ]);
        $verifier = str_repeat('r', 43);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $redirectUri = $client['redirect_uris'][0];
        $authorization = $this->oauthService->authorize($this->activeUser(53), [
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $redirectUri,
            'resource' => $this->oauthService->protectedResourceIdentifier('tenant-a.example'),
            'state' => 'state-revoke',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:read',
            'decision' => 'approve',
        ]);
        parse_str((string) parse_url($authorization['redirect_to'], PHP_URL_QUERY), $callback);
        $token = $this->oauthService->exchangeAuthorizationCode([
            'grant_type' => 'authorization_code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $redirectUri,
            'code' => $callback['code'],
            'code_verifier' => $verifier,
        ])['access_token'];

        $otherClient = $this->oauthService->registerClient([
            'client_name' => 'Other MCP client',
            'redirect_uris' => ['http://localhost:43124/callback'],
        ]);
        $this->oauthService->revokeAccessToken($token, $otherClient['client_id']);
        self::assertSame(53, $this->oauthService->resolveAccessToken($token)['sub']);

        $this->oauthService->revokeAccessToken($token, $client['client_id']);

        $this->expectException(\InvalidArgumentException::class);
        $this->oauthService->resolveAccessToken($token);
    }

    public function testMultiTenancyListenerOverridesClientTenantHeaderWithSignedCodeClaim(): void
    {
        $client = $this->oauthService->registerClient([
            'client_name' => 'Desktop MCP',
            'redirect_uris' => ['https://client.example/callback'],
        ]);
        $verifier = str_repeat('b', 43);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $authorization = $this->oauthService->authorize($this->activeUser(7), [
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $client['redirect_uris'][0],
            'resource' => $this->oauthService->protectedResourceIdentifier('tenant-a.example'),
            'state' => 'state-123',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:read',
            'decision' => 'approve',
        ]);
        parse_str((string) parse_url($authorization['redirect_to'], PHP_URL_QUERY), $callback);

        $request = Request::create('/oauth/token', 'POST', ['code' => $callback['code']]);
        $request->headers->set('app-domain', 'attacker.example');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener()->onKernelRequest($event);

        self::assertSame('tenant-a.example', $request->headers->get('app-domain'));
        self::assertNull($event->getResponse());
    }

    public function testMcpTenantComesFromBearerAndMissingBearerIsRejectedBeforeDatabaseSwitch(): void
    {
        $token = $this->issueAccessToken(9);

        $request = Request::create('/mcp/tenant-a.example', 'POST');
        $request->headers->set('app-domain', 'attacker.example');
        $request->headers->set('Origin', 'https://attacker.example');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener()->onKernelRequest($event);

        self::assertSame('tenant-a.example', $request->headers->get('app-domain'));
        self::assertSame(9, $request->attributes->get('oauth_user_id'));
        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());

        $requestWithoutBearer = Request::create('/mcp/tenant-a.example', 'POST');
        $requestWithoutBearer->headers->set('app-domain', 'attacker.example');
        $missingBearerEvent = new RequestEvent($this->createStub(HttpKernelInterface::class), $requestWithoutBearer, HttpKernelInterface::MAIN_REQUEST);
        $this->listener()->onKernelRequest($missingBearerEvent);

        self::assertSame(401, $missingBearerEvent->getResponse()?->getStatusCode());
        self::assertSame(
            'Bearer error="invalid_token", resource_metadata="https://api.controleonline.com/.well-known/oauth-protected-resource/mcp/tenant-a.example"',
            $missingBearerEvent->getResponse()?->headers->get('WWW-Authenticate')
        );
        self::assertTrue($missingBearerEvent->isPropagationStopped());
        self::assertSame('attacker.example', $requestWithoutBearer->headers->get('app-domain'));
    }

    public function testMcpPathTenantMustMatchSignedBearerTenant(): void
    {
        $request = Request::create('/mcp/other.example', 'POST');
        $request->headers->set('app-domain', 'attacker.example');
        $request->headers->set('Authorization', 'Bearer ' . $this->issueAccessToken(9));
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener()->onKernelRequest($event);

        self::assertSame(403, $event->getResponse()?->getStatusCode());
        self::assertSame(['error' => 'tenant_mismatch'], json_decode((string) $event->getResponse()?->getContent(), true));
        self::assertSame('attacker.example', $request->headers->get('app-domain'));
        self::assertTrue($event->isPropagationStopped());
    }

    public function testMcpWithoutPathTenantUsesMainDomainAndIgnoresOrigin(): void
    {
        $this->authorizedTenant = $this->mainDomain;
        $request = Request::create('/mcp', 'POST');
        $request->headers->set('app-domain', 'forged.example');
        $request->headers->set('Origin', 'https://forged.example');
        $request->headers->set('Authorization', 'Bearer ' . $this->issueAccessToken(10, false));
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener()->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertSame($this->mainDomain, $request->headers->get('app-domain'));
        self::assertSame($this->mainDomain, $request->attributes->get('app-domain'));
    }

    public function testAuthorizationRequestSelectsTheResourceTenantBeforeDatabaseSwitch(): void
    {
        $request = Request::create('/oauth/authorize', 'GET', [
            'resource' => $this->oauthService->protectedResourceIdentifier('tenant-b.example'),
        ]);
        $request->headers->set('app-domain', 'forged.example');
        $request->headers->set('Origin', 'https://forged.example');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener()->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertSame('tenant-b.example', $request->headers->get('app-domain'));
        self::assertSame('tenant-b.example', $request->attributes->get('app-domain'));
    }

    public function testConsentPostSelectsTheResourceTenantBeforeDatabaseSwitch(): void
    {
        $resource = $this->oauthService->protectedResourceIdentifier('tenant-c.example');
        $request = Request::create(
            '/oauth/authorize',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['resource' => $resource], JSON_THROW_ON_ERROR),
        );
        $request->headers->set('app-domain', 'forged.example');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener()->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertSame('tenant-c.example', $request->headers->get('app-domain'));
        self::assertSame('tenant-c.example', $request->attributes->get('app-domain'));
    }

    public function testAuthorizationWithoutResourceDefaultsToMainDomain(): void
    {
        $this->authorizedTenant = $this->mainDomain;
        $token = $this->issueAccessToken(11, false);

        self::assertSame($this->mainDomain, $this->oauthService->resolveAccessToken($token)['tenant']);
    }

    public function testAuthorizationCannotIssueTokenForDifferentDatabaseTenant(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->issueAccessToken(12, true, 'other.example');
    }

    public function testRejectsProtectedResourceFromAnotherOrigin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->oauthService->resolveTenantFromResource('https://attacker.example/mcp/tenant-a.example');
    }

    public function testProtectedResourceMetadataReflectsTheTenantPath(): void
    {
        $response = (new OAuthMetadataController($this->oauthService))
            ->protectedResource('tenant-a.example');

        self::assertSame(
            'https://api.controleonline.com/mcp/tenant-a.example',
            json_decode((string) $response->getContent(), true)['resource']
        );
    }

    public function testMcpPreflightDoesNotRequireBearerOrSwitchTenant(): void
    {
        $request = Request::create('/mcp', 'OPTIONS');
        $request->headers->set('app-domain', 'attacker.example');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener()->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
        self::assertSame('attacker.example', $request->headers->get('app-domain'));
    }

    public function testInvalidMcpBearerStopsBeforeMultiTenantDatabaseSelection(): void
    {
        $request = Request::create('/mcp', 'POST');
        $request->headers->set('app-domain', 'attacker.example');
        $request->headers->set('Authorization', 'Bearer not-a-signed-token');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener()->onKernelRequest($event);

        self::assertSame(401, $event->getResponse()?->getStatusCode());
        self::assertTrue($event->isPropagationStopped());
        self::assertSame('attacker.example', $request->headers->get('app-domain'));
    }

    private function activeUser(int $id): User
    {
        $user = new User();
        $idProperty = new \ReflectionProperty(User::class, 'id');
        $idProperty->setValue($user, $id);
        $user->setPeople((new People())->setEnabled(1));

        return $user;
    }

    private function issueAccessToken(int $userId, bool $includeResource = true, ?string $resourceTenant = null): string
    {
        $client = $this->oauthService->registerClient([
            'client_name' => 'Desktop MCP',
            'redirect_uris' => ['https://client.example/callback'],
        ]);
        $verifier = str_repeat('c', 43);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $authorizationParams = [
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $client['redirect_uris'][0],
            'state' => 'state-tenant-check',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:read',
            'decision' => 'approve',
        ];
        if ($includeResource) {
            $authorizationParams['resource'] = $this->oauthService->protectedResourceIdentifier(
                $resourceTenant ?? $this->authorizedTenant
            );
        }
        $authorization = $this->oauthService->authorize($this->activeUser($userId), $authorizationParams);
        parse_str((string) parse_url($authorization['redirect_to'], PHP_URL_QUERY), $callback);

        return $this->oauthService->exchangeAuthorizationCode([
            'grant_type' => 'authorization_code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $client['redirect_uris'][0],
            'code' => $callback['code'],
            'code_verifier' => $verifier,
        ])['access_token'];
    }

    private function listener(): OAuthTenantRequestListener
    {
        return new OAuthTenantRequestListener($this->oauthService);
    }
}
