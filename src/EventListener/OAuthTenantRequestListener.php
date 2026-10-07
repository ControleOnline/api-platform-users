<?php

declare(strict_types=1);

namespace ControleOnline\EventListener;

use ControleOnline\Service\DomainService;
use ControleOnline\Service\OAuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Resolves signed OAuth tenant claims before multi-tenancy's priority-512
 * DatabaseSwitchListener selects a tenant database.
 */
final class OAuthTenantRequestListener
{
    public function __construct(
        private readonly OAuthService $oauthService,
        private readonly DomainService $domainService,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        try {
            if ($path === '/oauth/token' && $request->isMethod('POST')) {
                $payload = $request->getPayload()->all();
                $code = is_string($payload['code'] ?? null) ? $payload['code'] : '';
                if ($code === '') {
                    throw new \InvalidArgumentException('Invalid authorization code');
                }

                // Never let the client choose a tenant while exchanging a code.
                $request->headers->set(
                    'app-domain',
                    $this->oauthService->resolveTenantFromAuthorizationCode($code)
                );

                return;
            }

            if (preg_match('#^/mcp(?:/([^/]+))?/?$#', $path, $pathMatches) !== 1) {
                return;
            }

            // Preflight requests carry no bearer token and never switch tenant databases.
            if ($request->isMethod('OPTIONS')) {
                return;
            }

            $authorization = trim((string) $request->headers->get('Authorization', ''));
            if (!preg_match('/^Bearer\\s+(.+)$/i', $authorization, $matches)) {
                throw new \InvalidArgumentException('Bearer token required');
            }

            $claims = $this->oauthService->resolveAccessToken(trim($matches[1]));
            if (!in_array('mcp:read', preg_split('/\\s+/', (string) ($claims['scope'] ?? '')) ?: [], true)) {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(
                    ['error' => 'insufficient_scope'],
                    403,
                    ['WWW-Authenticate' => 'Bearer error=\"insufficient_scope\"']
                ));

                return;
            }

            $requestedTenant = isset($pathMatches[1])
                ? rawurldecode($pathMatches[1])
                : (string) $this->domainService->getMainDomain();
            if (!$this->isValidTenantDomain($requestedTenant)) {
                throw new BadRequestHttpException('Invalid MCP tenant domain.');
            }

            if (strcasecmp($requestedTenant, (string) $claims['tenant']) !== 0) {
                $event->stopPropagation();
                $event->setResponse(new JsonResponse(['error' => 'tenant_mismatch'], 403));

                return;
            }

            // A signed OAuth tenant claim outranks client-controlled app-domain,
            // Origin and Referer headers before the database switch runs.
            $request->headers->set('app-domain', (string) $claims['tenant']);
            $request->attributes->set('app-domain', (string) $claims['tenant']);
            $request->attributes->set('oauth_user_id', (int) $claims['sub']);
            $request->attributes->set('oauth_scopes', (string) $claims['scope']);
        } catch (\InvalidArgumentException) {
            // Prevent database selection from an untrusted app-domain.
            $event->stopPropagation();
            $event->setResponse(new JsonResponse(
                ['error' => 'invalid_token'],
                401,
                ['WWW-Authenticate' => 'Bearer error=\"invalid_token\", resource_metadata=\"' . $this->oauthService->getIssuer() . '/.well-known/oauth-protected-resource\"']
            ));
        }
    }

    private function isValidTenantDomain(string $domain): bool
    {
        if (preg_match('/^[a-z0-9.-]+(?::[0-9]{1,5})?$/iD', $domain) !== 1) {
            return false;
        }

        $host = preg_replace('/:[0-9]{1,5}$/', '', $domain);

        return is_string($host)
            && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
