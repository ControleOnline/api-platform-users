<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\PublicAppUrlResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Compatibility redirect: old recovery emails may point to the API host
 * (/reset-password?hash=...&lost=...). Forward the browser to the public
 * frontend app with the same query string so the ResetPasswordPage can run.
 *
 * task-307 / app-community#307
 */
class RedirectResetPasswordAction
{
    public function __construct(
        private PublicAppUrlResolver $publicAppUrlResolver,
    ) {}

    #[Route(
        path: '/reset-password',
        name: 'controleonline_users_redirect_reset_password',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $baseUrl = rtrim($this->publicAppUrlResolver->resolve(), '/');
        $query = $request->getQueryString();
        $target = $baseUrl . '/reset-password' . ($query ? '?' . $query : '');

        return new RedirectResponse($target, Response::HTTP_FOUND);
    }
}
