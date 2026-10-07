<?php

declare(strict_types=1);

namespace ControleOnline\Tests\Controller;

use ControleOnline\Controller\McpOAuthConsentRedirectController;
use ControleOnline\Service\PublicAppUrlResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class McpOAuthConsentRedirectControllerTest extends TestCase
{
    public function testRedirectsApiConsentCompatibilityPathToConfiguredManagerAndPreservesOAuthQuery(): void
    {
        $urlResolver = $this->createMock(PublicAppUrlResolver::class);
        $urlResolver->expects(self::once())
            ->method('resolve')
            ->willReturn('https://app.controleonline.com');

        $request = Request::create(
            '/mcp/oauth/consent?response_type=code&scope=mcp%3Aread&resource=https%3A%2F%2Fapi.controleonline.com%2Fmcp'
        );
        $response = (new McpOAuthConsentRedirectController($urlResolver))->redirect($request);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame(
            'https://app.controleonline.com/mcp/oauth/consent?resource=https%3A%2F%2Fapi.controleonline.com%2Fmcp&response_type=code&scope=mcp%3Aread',
            $response->headers->get('Location')
        );
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
