<?php

namespace ControleOnline\Tests\Service;

require_once dirname(__DIR__, 2) . '/src/Service/PublicAppUrlResolver.php';

use ControleOnline\Service\DomainService;
use ControleOnline\Service\PublicAppUrlResolver;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for multi-tenant public app URL resolution used in
 * password-recovery and account-verification email links (task-323/324).
 */
class PublicAppUrlResolverTest extends TestCase
{
    private function resolverWithRequestDomain(?string $domain): PublicAppUrlResolver
    {
        $domainService = $this->createMock(DomainService::class);
        if ($domain === null) {
            $domainService->method('getDomain')->willThrowException(new \RuntimeException('no domain'));
        } else {
            $domainService->method('getDomain')->willReturn($domain);
        }

        return new PublicAppUrlResolver($domainService);
    }

    public function testRequestDomainPresentUsesRequest(): void
    {
        $resolver = $this->resolverWithRequestDomain('app.lave-go.com');
        $this->assertSame(
            'https://app.lave-go.com',
            $resolver->resolveFrom('app.lave-go.com', 'admin.controleonline.com'),
        );
    }

    public function testRequestEmptyUsesConfiguredEnv(): void
    {
        $resolver = $this->resolverWithRequestDomain('');
        $this->assertSame(
            'https://manager.example.com',
            $resolver->resolveFrom('', 'manager.example.com'),
        );
    }

    public function testRequestApiHostPrefersNonApiConfiguredFrontend(): void
    {
        $resolver = $this->resolverWithRequestDomain('api.controleonline.com');
        $this->assertSame(
            'https://app.lave-go.com',
            $resolver->resolveFrom('api.controleonline.com', 'app.lave-go.com'),
        );
    }

    public function testRequestApiSubdomainPrefersConfiguredFrontend(): void
    {
        $resolver = $this->resolverWithRequestDomain('api.staging.example.com');
        $this->assertSame(
            'https://app.example.com',
            $resolver->resolveFrom('https://api.staging.example.com', 'https://app.example.com'),
        );
    }

    public function testBothEmptyFallsBackToAdmin(): void
    {
        $resolver = $this->resolverWithRequestDomain('');
        $this->assertSame(
            'https://admin.controleonline.com',
            $resolver->resolveFrom('', ''),
        );
    }

    public function testAddsHttpsWhenMissingScheme(): void
    {
        $resolver = $this->resolverWithRequestDomain('app.lave-go.com');
        $this->assertSame(
            'https://app.lave-go.com',
            $resolver->resolveFrom('app.lave-go.com', null),
        );
    }

    public function testPreservesExistingHttpsAndTrimsSlash(): void
    {
        $resolver = $this->resolverWithRequestDomain('https://app.lave-go.com/');
        $this->assertSame(
            'https://app.lave-go.com',
            $resolver->resolveFrom('https://app.lave-go.com/', ''),
        );
    }

    public function testLooksLikeApiHost(): void
    {
        $resolver = $this->resolverWithRequestDomain('');
        $this->assertTrue($resolver->looksLikeApiHost('api.controleonline.com'));
        $this->assertTrue($resolver->looksLikeApiHost('https://api.foo.com'));
        $this->assertFalse($resolver->looksLikeApiHost('app.lave-go.com'));
        $this->assertFalse($resolver->looksLikeApiHost('https://admin.controleonline.com'));
    }

    public function testResolveUsesDomainService(): void
    {
        $resolver = $this->resolverWithRequestDomain('app.lave-go.com');
        // ENV may be empty in test process → request domain wins
        $url = $resolver->resolve();
        $this->assertSame('https://app.lave-go.com', $url);
    }
}
