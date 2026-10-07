<?php

declare(strict_types=1);

namespace ControleOnline\Controller;

use ControleOnline\Service\PublicAppUrlResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class McpOAuthConsentRedirectController
{
    public function __construct(
        private readonly PublicAppUrlResolver $publicAppUrlResolver,
    ) {
    }

    #[Route('/mcp/oauth/consent', name: 'users_mcp_oauth_consent_redirect', methods: ['GET'])]
    public function redirect(Request $request): RedirectResponse
    {
        $target = $this->publicAppUrlResolver->resolve() . '/mcp/oauth/consent';
        $query = $request->getQueryString();

        if ($query !== null && $query !== '') {
            $target .= '?' . $query;
        }

        return new RedirectResponse($target, RedirectResponse::HTTP_FOUND, ['Cache-Control' => 'no-store']);
    }
}
