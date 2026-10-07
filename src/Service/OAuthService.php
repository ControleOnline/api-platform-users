<?php

declare(strict_types=1);

namespace ControleOnline\Service;

use ControleOnline\Entity\User;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\LockFactory;

final class OAuthService
{
    private const AUTHORIZATION_CODE_TTL = 120;
    private const ACCESS_TOKEN_TTL = 900;
    private const CLIENT_ID_TTL = 315360000;
    private string $issuer;

    /**
     * @param list<string> $allowedScopes
     */
    public function __construct(
        private readonly string $secret,
        string $issuer,
        private readonly array $allowedScopes,
        private readonly DomainService $domainService,
        private readonly CacheItemPoolInterface $cache,
        private readonly LockFactory $lockFactory,
        private readonly array $defaultScopes = [],
    ) {
        $issuer = trim($issuer);
        $this->issuer = rtrim(
            preg_match('#^https?://#i', $issuer) === 1 ? $issuer : 'https://' . $issuer,
            '/'
        );
    }

    /**
     * @param array<string, mixed> $registration
     * @return array{client_id: string, client_name: string, redirect_uris: list<string>, token_endpoint_auth_method: string}
     */
    public function registerClient(array $registration): array
    {
        $name = trim((string) ($registration['client_name'] ?? ''));
        $redirectUris = $registration['redirect_uris'] ?? null;

        if ($name === '' || mb_strlen($name) > 120 || !is_array($redirectUris) || $redirectUris === []) {
            throw new \InvalidArgumentException('Invalid client metadata');
        }

        foreach ($redirectUris as $uri) {
            if (!is_string($uri)) {
                throw new \InvalidArgumentException('Invalid redirect URI');
            }
        }

        $redirectUris = array_values(array_unique(array_map('trim', $redirectUris)));

        if (count($redirectUris) > 10) {
            throw new \InvalidArgumentException('Too many redirect URIs');
        }

        foreach ($redirectUris as $uri) {
            if (!$this->isAllowedRedirectUri($uri)) {
                throw new \InvalidArgumentException('Invalid redirect URI');
            }
        }

        $clientId = $this->sign([
            'type' => 'client',
            'client_name' => $name,
            'redirect_uris' => $redirectUris,
            'iat' => time(),
            'exp' => time() + self::CLIENT_ID_TTL,
        ]);

        return [
            'client_id' => $clientId,
            'client_name' => $name,
            'redirect_uris' => $redirectUris,
            'token_endpoint_auth_method' => 'none',
        ];
    }

    /**
     * Validates all request-controlled OAuth parameters before a browser is
     * sent to the manager consent screen.
     *
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    public function validateAuthorizationRequest(array $params): array
    {
        if (($params['response_type'] ?? null) !== 'code') {
            throw new \InvalidArgumentException('Unsupported response type');
        }

        $clientId = (string) ($params['client_id'] ?? '');
        $client = $this->decode($clientId, 'client');
        $redirectUri = (string) ($params['redirect_uri'] ?? '');
        $state = (string) ($params['state'] ?? '');
        $challenge = (string) ($params['code_challenge'] ?? '');
        $challengeMethod = (string) ($params['code_challenge_method'] ?? '');

        if (!in_array($redirectUri, $client['redirect_uris'] ?? [], true)
            || !$this->isAllowedRedirectUri($redirectUri)) {
            throw new \InvalidArgumentException('Invalid redirect URI');
        }

        if ($state === '' || strlen($state) > 2048) {
            throw new \InvalidArgumentException('Invalid state');
        }

        if ($challengeMethod !== 'S256' || preg_match('/^[A-Za-z0-9_-]{43}$/', $challenge) !== 1) {
            throw new \InvalidArgumentException('PKCE S256 is required');
        }

        $scope = trim((string) ($params['scope'] ?? ''));
        $requestedScopes = $scope === ''
            ? $this->defaultScopes
            : (preg_split('/\\s+/', $scope) ?: []);
        if ($requestedScopes === []) {
            throw new \InvalidArgumentException('At least one scope is required');
        }
        foreach ($requestedScopes as $scope) {
            if ($scope === '' || !in_array($scope, $this->allowedScopes, true)) {
                throw new \InvalidArgumentException('Unsupported scope');
            }
        }

        if (isset($params['resource']) && !is_string($params['resource'])) {
            throw new \InvalidArgumentException('Invalid protected resource');
        }

        $resource = trim((string) ($params['resource'] ?? ($this->issuer . '/mcp')));
        $this->resolveTenantFromResource($resource);

        return [
            'response_type' => 'code',
            'client_id' => $clientId,
            'client_name' => (string) ($client['client_name'] ?? ''),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'scope' => implode(' ', array_values(array_unique($requestedScopes))),
            'resource' => $resource,
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array{redirect_to: string}
     */
    public function authorize(User $user, array $params): array
    {
        $authorization = $this->validateAuthorizationRequest($params);

        if (($params['decision'] ?? null) !== 'approve') {
            return ['redirect_to' => $this->appendQuery($authorization['redirect_uri'], [
                'error' => 'access_denied',
                'state' => $authorization['state'],
            ])];
        }

        if ($user->getId() === null || $user->getPeople() === null || !$user->getPeople()->getEnabled()) {
            throw new \InvalidArgumentException('Active user required');
        }

        $tenant = $this->resolveTenantFromResource($authorization['resource']);
        if (strcasecmp($tenant, $this->normalizeTenantDomain((string) $this->domainService->getDomain())) !== 0) {
            throw new \InvalidArgumentException('Authenticated tenant does not match protected resource');
        }

        $jti = bin2hex(random_bytes(24));
        $code = $this->sign([
            'type' => 'authorization_code',
            'sub' => (int) $user->getId(),
            'tenant' => $tenant,
            'client_id_hash' => hash('sha256', $authorization['client_id']),
            'redirect_uri' => $authorization['redirect_uri'],
            'state' => $authorization['state'],
            'code_challenge' => $authorization['code_challenge'],
            'scope' => $authorization['scope'],
            'jti' => $jti,
            'iat' => time(),
            'exp' => time() + self::AUTHORIZATION_CODE_TTL,
        ]);

        return ['redirect_to' => $this->appendQuery($authorization['redirect_uri'], [
            'code' => $code,
            'state' => $authorization['state'],
        ])];
    }

    /**
     * @param array<string, mixed> $params
     * @return array{access_token: string, token_type: string, expires_in: int, scope: string}
     */
    public function exchangeAuthorizationCode(array $params): array
    {
        if (($params['grant_type'] ?? null) !== 'authorization_code') {
            throw new \InvalidArgumentException('Unsupported grant type');
        }

        $clientId = (string) ($params['client_id'] ?? '');
        $this->decode($clientId, 'client');
        $claims = $this->decode((string) ($params['code'] ?? ''), 'authorization_code');

        if (!hash_equals((string) ($claims['client_id_hash'] ?? ''), hash('sha256', $clientId))
            || !hash_equals((string) ($claims['redirect_uri'] ?? ''), (string) ($params['redirect_uri'] ?? ''))) {
            throw new \InvalidArgumentException('Invalid authorization code');
        }

        $verifier = (string) ($params['code_verifier'] ?? '');
        if (preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier) !== 1) {
            throw new \InvalidArgumentException('Invalid PKCE verifier');
        }

        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        if (!hash_equals((string) $claims['code_challenge'], $expectedChallenge)) {
            throw new \InvalidArgumentException('Invalid PKCE verifier');
        }

        $this->consumeAuthorizationCode((string) $claims['jti']);
        $expiresAt = time() + self::ACCESS_TOKEN_TTL;
        $accessToken = $this->sign([
            'type' => 'access_token',
            'iss' => $this->issuer,
            'aud' => $this->issuer . '/mcp',
            'sub' => (int) $claims['sub'],
            'tenant' => (string) $claims['tenant'],
            'scope' => (string) $claims['scope'],
            'client_id_hash' => hash('sha256', $clientId),
            'jti' => bin2hex(random_bytes(24)),
            'iat' => time(),
            'exp' => $expiresAt,
        ]);

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'scope' => (string) $claims['scope'],
        ];
    }

    /** @return array<string, mixed> */
    public function resolveAccessToken(string $token): array
    {
        $claims = $this->decode($token, 'access_token');
        if (($claims['iss'] ?? null) !== $this->issuer
            || ($claims['aud'] ?? null) !== $this->issuer . '/mcp'
            || !is_int($claims['sub'] ?? null)
            || $claims['sub'] < 1
            || !is_string($claims['tenant'] ?? null)
            || trim($claims['tenant']) === '') {
            throw new \InvalidArgumentException('Invalid access token');
        }

        $jti = $claims['jti'] ?? null;
        if (!is_string($jti) || preg_match('/^[a-f0-9]{48}$/', $jti) !== 1
            || $this->cache->getItem($this->revokedAccessTokenKey($jti))->isHit()) {
            throw new \InvalidArgumentException('Invalid access token');
        }

        return $claims;
    }

    /**
     * Revokes an access token presented by its public OAuth client. Invalid,
     * expired, or mismatched tokens are intentionally ignored per RFC 7009.
     */
    public function revokeAccessToken(string $token, string $clientId): void
    {
        try {
            $client = $this->decode($clientId, 'client');
            $claims = $this->decode($token, 'access_token');
        } catch (\InvalidArgumentException) {
            return;
        }

        $jti = $claims['jti'] ?? null;
        $clientIdHash = $claims['client_id_hash'] ?? null;
        $remainingLifetime = (int) ($claims['exp'] ?? 0) - time();
        if (($claims['iss'] ?? null) !== $this->issuer
            || ($claims['aud'] ?? null) !== $this->issuer . '/mcp'
            || !is_string($jti) || preg_match('/^[a-f0-9]{48}$/', $jti) !== 1
            || !is_string($clientIdHash)
            || !hash_equals($clientIdHash, hash('sha256', $clientId))
            || $remainingLifetime < 1
            || !isset($client['client_name'])) {
            return;
        }

        $item = $this->cache->getItem($this->revokedAccessTokenKey($jti));
        $item->set(true);
        $item->expiresAfter($remainingLifetime);
        $this->cache->save($item);
    }

    public function resolveTenantFromAuthorizationCode(string $code): string
    {
        $claims = $this->decode($code, 'authorization_code');
        $tenant = trim((string) ($claims['tenant'] ?? ''));
        if ($tenant === '') {
            throw new \InvalidArgumentException('Invalid authorization code');
        }

        return $tenant;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function resolveTenantFromResource(?string $resource): string
    {
        $mainDomain = (string) $this->domainService->getMainDomain();
        if ($resource === null || trim($resource) === '') {
            return $this->normalizeTenantDomain($mainDomain);
        }

        $resourceParts = parse_url(trim($resource));
        $issuerParts = parse_url($this->issuer);
        if (!is_array($resourceParts)
            || !is_array($issuerParts)
            || strtolower((string) ($resourceParts['scheme'] ?? '')) !== strtolower((string) ($issuerParts['scheme'] ?? ''))
            || strtolower((string) ($resourceParts['host'] ?? '')) !== strtolower((string) ($issuerParts['host'] ?? ''))
            || ($resourceParts['port'] ?? null) !== ($issuerParts['port'] ?? null)
            || isset($resourceParts['user'])
            || isset($resourceParts['pass'])
            || isset($resourceParts['query'])
            || isset($resourceParts['fragment'])) {
            throw new \InvalidArgumentException('Invalid protected resource');
        }

        $path = (string) ($resourceParts['path'] ?? '');
        if (preg_match('#^/mcp(?:/([^/]+))?$#', $path, $matches) !== 1) {
            throw new \InvalidArgumentException('Invalid protected resource');
        }

        $tenantDomain = isset($matches[1]) ? rawurldecode($matches[1]) : $mainDomain;

        return $this->normalizeTenantDomain($tenantDomain);
    }

    public function protectedResourceIdentifier(?string $tenantDomain = null): string
    {
        if ($tenantDomain === null || trim($tenantDomain) === '') {
            return $this->issuer . '/mcp';
        }

        $tenantDomain = $this->normalizeTenantDomain(rawurldecode($tenantDomain));

        return $this->issuer . '/mcp/' . rawurlencode($tenantDomain);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function sign(array $claims): string
    {
        $header = $this->base64UrlEncode(json_encode(
            ['alg' => 'HS256', 'typ' => 'JWT'],
            JSON_THROW_ON_ERROR
        ));
        $payload = $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, $this->secret, true));

        return $header . '.' . $payload . '.' . $signature;
    }

    /** @return array<string, mixed> */
    private function decode(string $token, string $expectedType): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid OAuth token');
        }

        [$header, $payload, $signature] = $parts;
        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, $this->secret, true));
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \InvalidArgumentException('Invalid OAuth token');
        }

        $headerData = json_decode($this->base64UrlDecode($header), true);
        $claims = json_decode($this->base64UrlDecode($payload), true);
        if (!is_array($headerData) || ($headerData['alg'] ?? null) !== 'HS256'
            || !is_array($claims) || ($claims['type'] ?? null) !== $expectedType
            || !is_int($claims['exp'] ?? null) || $claims['exp'] < time()) {
            throw new \InvalidArgumentException('Expired or invalid OAuth token');
        }

        return $claims;
    }

    private function consumeAuthorizationCode(string $jti): void
    {
        if (preg_match('/^[a-f0-9]{48}$/', $jti) !== 1) {
            throw new \InvalidArgumentException('Invalid authorization code');
        }

        $cacheKey = 'users.oauth.authorization_code.' . hash('sha256', $jti);
        $lock = $this->lockFactory->createLock($cacheKey, 5);
        if (!$lock->acquire(true)) {
            throw new \InvalidArgumentException('Authorization code already used');
        }

        try {
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                throw new \InvalidArgumentException('Authorization code already used');
            }

            $item->set(true);
            $item->expiresAfter(self::AUTHORIZATION_CODE_TTL);
            $this->cache->save($item);
        } finally {
            $lock->release();
        }
    }

    private function revokedAccessTokenKey(string $jti): string
    {
        return 'users.oauth.revoked_access_token.' . hash('sha256', $jti);
    }

    private function isAllowedRedirectUri(string $uri): bool
    {
        if ($uri === '' || strlen($uri) > 2048 || preg_match('/[\\r\\n]/', $uri) === 1) {
            return false;
        }

        $parts = parse_url($uri);
        if (!is_array($parts) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme === 'https') {
            return $host !== '';
        }

        return $scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true);
    }

    private function normalizeTenantDomain(string $domain): string
    {
        $domain = trim($domain);
        if (preg_match('/^[a-z0-9.-]+(?::[0-9]{1,5})?$/iD', $domain) !== 1) {
            throw new \InvalidArgumentException('Invalid protected resource');
        }

        $host = preg_replace('/:[0-9]{1,5}$/', '', $domain);
        if (!is_string($host) || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new \InvalidArgumentException('Invalid protected resource');
        }

        return strtolower($domain);
    }

    /** @param array<string, string> $query */
    private function appendQuery(string $uri, array $query): string
    {
        return $uri . (str_contains($uri, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid OAuth token');
        }

        return $decoded;
    }
}
