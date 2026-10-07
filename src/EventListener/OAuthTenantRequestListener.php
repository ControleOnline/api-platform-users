<?php

declare(strict_types=1);

namespace ControleOnline\EventListener;

use ControleOnline\Service\OAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Resolves OAuth resource tenants before multi-tenancy's priority-512
 * DatabaseSwitchListener selects a tenant database.
 */
final class OAuthTenantRequestListener
{
    public function __construct(
        private readonly OAuthService $oauthService,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if ($path === '/oauth/authorize' && in_array($request->getMethod(), ['GET', 'POST'], true)) {
            $params = $request->isMethod('GET') ? $request->query->all() : $request->getPayload()->all();
            $resource = $params['resource'] ?? null;
            if ($resource !== null && !is_string($resource)) {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(['error' => 'invalid_resource'], 400));

                return;
            }

            try {
                $tenant = $this->oauthService->resolveTenantFromResource($resource);
            } catch (\InvalidArgumentException) {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(['error' => 'invalid_resource'], 400));

                return;
            }

            // The selected resource determines the tenant before login or consent.
            $request->headers->set('app-domain', $tenant);
            $request->attributes->set('app-domain', $tenant);

            return;
        }

        if ($path === '/oauth/token' && $request->isMethod('POST')) {
            $payload = $request->getPayload()->all();
            $code = is_string($payload['code'] ?? null) ? $payload['code'] : '';
            if ($code === '') {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(['error' => 'invalid_grant'], 400));

                return;
            }

            try {
                // Never let the client choose a tenant while exchanging a code.
                $tenant = $this->oauthService->resolveTenantFromAuthorizationCode($code);
            } catch (\InvalidArgumentException) {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(['error' => 'invalid_grant'], 400));

                return;
            }

            $request->headers->set('app-domain', $tenant);
            $request->attributes->set('app-domain', $tenant);

            return;
        }

        if (preg_match('#^/mcp(?:/([^/]+))?/?$#', $path) !== 1) {
            return;
        }

        // Preflight requests carry no bearer token and never switch tenant databases.
        if ($request->isMethod('OPTIONS')) {
            return;
        }

        try {
            $authorization = trim((string) $request->headers->get('Authorization', ''));
            if (!preg_match('/^Bearer\\s+(.+)$/i', $authorization, $matches)) {
                throw new \InvalidArgumentException('Bearer token required');
            }

            $claims = $this->oauthService->resolveAccessToken(trim($matches[1]));
            try {
                $requestedTenant = $this->oauthService->resolveTenantFromResource(
                    $this->oauthService->getIssuer() . $path
                );
            } catch (\InvalidArgumentException) {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(['error' => 'invalid_resource'], 400));

                return;
            }

            if (!in_array('mcp:read', preg_split('/\\s+/', (string) ($claims['scope'] ?? '')) ?: [], true)) {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(
                    ['error' => 'insufficient_scope'],
                    403,
                    ['WWW-Authenticate' => 'Bearer error="insufficient_scope", resource_metadata="' . $this->resourceMetadataUri($path) . '"']
                ));

                return;
            }

            if (strcasecmp($requestedTenant, (string) $claims['tenant']) !== 0) {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(['error' => 'tenant_mismatch'], 403));

                return;
            }

            // A signed OAuth tenant claim outranks client-controlled tenant headers.
            $request->headers->set('app-domain', (string) $claims['tenant']);
            $request->attributes->set('app-domain', (string) $claims['tenant']);
            $request->attributes->set('oauth_user_id', (int) $claims['sub']);
            $request->attributes->set('oauth_scopes', (string) $claims['scope']);
        } catch (\InvalidArgumentException) {
            $event->stopPropagation();
            $event->setResponse(new JsonResponse(
                ['error' => 'invalid_token'],
                401,
                ['WWW-Authenticate' => 'Bearer error="invalid_token", resource_metadata="' . $this->resourceMetadataUri($path) . '"']
            ));
        }
    }

    private function resourceMetadataUri(string $path): string
    {
        $metadataPath = '/.well-known/oauth-protected-resource';
        if (preg_match('#^/mcp(?:/[^/]+)?/?$#', $path) === 1) {
            $metadataPath .= rtrim($path, '/');
        }

        return $this->oauthService->getIssuer() . $metadataPath;
    }
}
