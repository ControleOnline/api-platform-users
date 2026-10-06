<?php

declare(strict_types=1);

namespace ControleOnline\Controller;

use ControleOnline\Service\OAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class OAuthMetadataController
{
    public function __construct(
        private readonly OAuthService $oauthService,
    ) {
    }

    #[Route('/.well-known/oauth-authorization-server', name: 'users_oauth_metadata', methods: ['GET'])]
    public function authorizationServer(): JsonResponse
    {
        $issuer = $this->oauthService->getIssuer();

        return new JsonResponse([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer . '/oauth/authorize',
            'token_endpoint' => $issuer . '/oauth/token',
            'registration_endpoint' => $issuer . '/oauth/register',
            'revocation_endpoint' => $issuer . '/oauth/revoke',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'revocation_endpoint_auth_methods_supported' => ['none'],
        ], headers: ['Cache-Control' => 'public, max-age=300']);
    }

    #[Route('/.well-known/oauth-protected-resource', name: 'users_oauth_resource_metadata', methods: ['GET'])]
    public function protectedResource(): JsonResponse
    {
        $issuer = $this->oauthService->getIssuer();

        return new JsonResponse([
            'resource' => $issuer . '/mcp',
            'authorization_servers' => [$issuer],
            'scopes_supported' => ['mcp:read'],
            'bearer_methods_supported' => ['header'],
            'resource_name' => 'ControleOnline MCP',
        ], headers: ['Cache-Control' => 'public, max-age=300']);
    }
}
