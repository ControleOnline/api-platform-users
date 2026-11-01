<?php

namespace ControleOnline\Tests\Service;

require_once dirname(__DIR__, 2) . '/src/Service/PublicAppUrlResolver.php';

use ControleOnline\Service\DomainService;
use ControleOnline\Service\PublicAppUrlResolver;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for multi-tenant public app URL resolution
 * (password-recovery, account-verification, MCP OAuth consent).
 */
class PublicAppUrlResolverTest extends TestCase
{
    private function resolver(?string $requestDomain, ?string $mainDomain = null): PublicAppUrlResolver
    {
        $domainService = $this->createMock(DomainService::class);
        if ($requestDomain === null) {
            $domainService->method('getDomain')->willThrowException(new \RuntimeException('no domain'));
        } else {
            $domainService->method('getDomain')->willReturn($requestDomain);
        }
        if ($mainDomain === null) {
            $domainService->method('getMainDomain')->willReturn('');
        } else {
            $domainService->method('getMainDomain')->willReturn($mainDomain);
        }

        return new PublicAppUrlResolver($domainService);
    }

    public function testRequestDomainPresentUsesRequest(): void
    {
        $resolver = $this->resolver('app.example-front.test', 'api.example-api.test');
        $this->assertSame(
            'https://app.example-front.test',
            $resolver->resolveFrom('app.example-front.test', 'https://app.example-front.test'),
        );
    }

    public function testRequestEmptyUsesConfiguredEnv(): void
    {
        $resolver = $this->resolver('', 'api.example-api.test');
        $this->assertSame(
            'https://manager.example.com',
            $resolver->resolveFrom('', 'manager.example.com'),
        );
    }

    public function testRequestApiHostPrefersConfiguredFrontend(): void
    {
        $resolver = $this->resolver('api.example-api.test', 'api.example-api.test');
        $this->assertSame(
            'https://app.example-front.test',
            $resolver->resolveFrom('api.example-api.test', 'app.example-front.test'),
        );
    }

    public function testRequestApiHostWithSchemePrefersConfiguredFrontend(): void
    {
        $resolver = $this->resolver('api.example-api.test', 'api.example-api.test');
        $this->assertSame(
            'https://app.example-front.test',
            $resolver->resolveFrom('https://api.example-api.test', 'https://app.example-front.test'),
        );
    }

    public function testBothEmptyReturnsEmpty(): void
    {
        $resolver = $this->resolver('', '');
        $this->assertSame('', $resolver->resolveFrom('', ''));
    }

    public function testAddsHttpsWhenMissingScheme(): void
    {
        $resolver = $this->resolver('app.example-front.test', 'api.example-api.test');
        $this->assertSame(
            'https://app.example-front.test',
            $resolver->resolveFrom('app.example-front.test', null),
        );
    }

    public function testPreservesExistingHttpsAndTrimsSlash(): void
    {
        $resolver = $this->resolver('https://app.example-front.test/', 'api.example-api.test');
        $this->assertSame(
            'https://app.example-front.test',
            $resolver->resolveFrom('https://app.example-front.test/', ''),
        );
    }

    public function testLooksLikeApiHostMatchesMainDomainOnly(): void
    {
        $resolver = $this->resolver('', 'api.example-api.test');
        $this->assertTrue($resolver->looksLikeApiHost('api.example-api.test'));
        $this->assertTrue($resolver->looksLikeApiHost('https://api.example-api.test'));
        $this->assertFalse($resolver->looksLikeApiHost('app.example-front.test'));
        $this->assertFalse($resolver->looksLikeApiHost('https://other.example.test'));
    }

    public function testLooksLikeApiHostFalseWhenMainDomainEmpty(): void
    {
        $resolver = $this->resolver('', '');
        $this->assertFalse($resolver->looksLikeApiHost('api.example-api.test'));
    }

    public function testResolveUsesDomainService(): void
    {
        $resolver = $this->resolver('app.example-front.test', 'api.example-api.test');
        $url = $resolver->resolve();
        $this->assertSame('https://app.example-front.test', $url);
    }
}
