<?php

namespace ControleOnline\Service;

/**
 * Resolves the public frontend base URL used in multi-tenant email links
 * (password recovery, account verification).
 *
 * Prefer the tenant request domain over global ENV so white-label apps
 * (e.g. app.lave-go.com) receive correct links instead of the API host.
 */
class PublicAppUrlResolver
{
    public function __construct(
        private DomainService $domainService,
    ) {}

    /**
     * Resolve frontend base URL for email links.
     */
    public function resolve(): string
    {
        $requestDomain = '';
        try {
            $requestDomain = (string) $this->domainService->getDomain();
        } catch (\Throwable) {
            $requestDomain = '';
        }

        return $this->resolveFrom(
            $requestDomain,
            $this->readConfiguredDomain(),
        );
    }

    /**
     * Pure resolution logic (testable without DomainService / ENV).
     *
     * @param string|null $requestDomain   Domain from current request (app-domain / Origin / Referer)
     * @param string|null $configuredDomain Fallback from ENV (PUBLIC_APP_DOMAIN / APP_DOMAIN / …)
     */
    public function resolveFrom(?string $requestDomain, ?string $configuredDomain = null): string
    {
        $requestDomain = trim((string) ($requestDomain ?? ''));
        $configuredDomain = trim((string) ($configuredDomain ?? ''));

        // Tenant/request domain first; ENV is fallback only.
        $domain = $requestDomain !== ''
            ? $requestDomain
            : $configuredDomain;

        // If request resolved to an API host, prefer a non-API configured frontend.
        if ($this->looksLikeApiHost($domain) && $configuredDomain !== '' && !$this->looksLikeApiHost($configuredDomain)) {
            $domain = $configuredDomain;
        }

        if ($domain === '') {
            $domain = 'admin.controleonline.com';
        }

        if (!preg_match('#^https?://#i', $domain)) {
            $domain = 'https://' . ltrim($domain, '/');
        }

        return rtrim($domain, '/');
    }

    public function looksLikeApiHost(string $domain): bool
    {
        $host = preg_replace('#^https?://#i', '', $domain) ?? $domain;
        $host = strtolower((string) explode('/', $host)[0]);
        $host = explode(':', $host)[0];

        return $host === 'api.controleonline.com'
            || str_starts_with($host, 'api.');
    }

    private function readConfiguredDomain(): string
    {
        $configuredDomain = $_ENV['PUBLIC_APP_DOMAIN']
            ?? $_ENV['MANAGER_APP']
            ?? $_ENV['APP_DOMAIN']
            ?? $_ENV['ADMIN_APP_DOMAIN']
            ?? $_SERVER['PUBLIC_APP_DOMAIN']
            ?? $_SERVER['MANAGER_APP']
            ?? $_SERVER['APP_DOMAIN']
            ?? $_SERVER['ADMIN_APP_DOMAIN']
            ?? getenv('PUBLIC_APP_DOMAIN')
            ?? getenv('MANAGER_APP')
            ?? getenv('APP_DOMAIN')
            ?? getenv('ADMIN_APP_DOMAIN')
            ?? '';

        return trim((string) $configuredDomain);
    }
}
