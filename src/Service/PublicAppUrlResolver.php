<?php

namespace ControleOnline\Service;

/**
 * Resolves the public frontend base URL used in multi-tenant email links
 * (password recovery, account verification) and MCP OAuth consent redirects.
 *
 * Prefer the tenant request domain over global ENV so white-label apps
 * receive correct links instead of the API host.
 *
 * API host detection uses DomainService::getMainDomain() only — no hardcoded
 * domain names and no guessing among multiple env keys.
 */
class PublicAppUrlResolver
{
    public function __construct(
        private DomainService $domainService,
    ) {}

    /**
     * Resolve frontend base URL for email links / consent.
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
     * Pure resolution logic (testable with DomainService mock).
     *
     * @param string|null $requestDomain   Domain from current request (app-domain / Origin / Referer)
     * @param string|null $configuredDomain Fallback from ENV (front / manager app)
     */
    public function resolveFrom(?string $requestDomain, ?string $configuredDomain = null): string
    {
        $requestDomain = trim((string) ($requestDomain ?? ''));
        $configuredDomain = trim((string) ($configuredDomain ?? ''));

        // Tenant/request domain first; ENV is fallback only.
        $domain = $requestDomain !== ''
            ? $requestDomain
            : $configuredDomain;

        // If request resolved to the API host, prefer the configured frontend.
        if ($this->looksLikeApiHost($domain) && $configuredDomain !== '' && !$this->looksLikeApiHost($configuredDomain)) {
            $domain = $configuredDomain;
        }

        if ($domain === '') {
            $domain = $configuredDomain !== '' ? $configuredDomain : '';
        }

        if ($domain === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $domain)) {
            $domain = 'https://' . ltrim($domain, '/');
        }

        return rtrim($domain, '/');
    }

    /**
     * True when $domain is the API host resolved by DomainService (main domain).
     */
    public function looksLikeApiHost(string $domain): bool
    {
        $host = $this->normalizeHost($domain);
        if ($host === '') {
            return false;
        }

        try {
            $mainHost = $this->normalizeHost((string) $this->domainService->getMainDomain());

            return $mainHost !== '' && $host === $mainHost;
        } catch (\Throwable) {
            return false;
        }
    }

    private function normalizeHost(string $domain): string
    {
        $host = preg_replace('#^https?://#i', '', trim($domain)) ?? '';
        $host = strtolower((string) explode('/', $host)[0]);

        return explode(':', $host)[0];
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
