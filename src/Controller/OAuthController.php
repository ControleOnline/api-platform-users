<?php

declare(strict_types=1);

namespace ControleOnline\Controller;

use ControleOnline\Entity\User;
use ControleOnline\Service\OAuthService;
use ControleOnline\Service\PublicAppUrlResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/oauth')]
final class OAuthController
{
    public function __construct(
        private readonly OAuthService $oauthService,
        private readonly PublicAppUrlResolver $publicAppUrlResolver,
        private readonly Security $security,
    ) {
    }

    #[Route('/register', name: 'users_oauth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            return new JsonResponse(
                $this->oauthService->registerClient($request->toArray()),
                Response::HTTP_CREATED,
                $this->noStoreHeaders()
            );
        } catch (\InvalidArgumentException) {
            return $this->oauthError('invalid_client_metadata', Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/authorize', name: 'users_oauth_authorize', methods: ['GET'])]
    public function authorize(Request $request): Response
    {
        try {
            $params = $this->oauthService->validateAuthorizationRequest($request->query->all());
        } catch (\InvalidArgumentException $exception) {
            return $this->oauthError(
                'invalid_request',
                Response::HTTP_BAD_REQUEST,
                $exception->getMessage()
            );
        }

        // Resolve the consent origin from tenant-aware configuration, never from a request redirect.
        $consentUrl = $this->publicAppUrlResolver->resolve() . '/mcp/oauth/consent';

        return new RedirectResponse(
            $consentUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986),
            Response::HTTP_FOUND,
            ['Cache-Control' => 'no-store']
        );
    }

    #[Route('/authorize', name: 'users_oauth_decision', methods: ['POST'])]
    public function decide(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->oauthError('login_required', Response::HTTP_UNAUTHORIZED);
        }

        try {
            return new JsonResponse(
                $this->oauthService->authorize($user, $request->toArray()),
                Response::HTTP_OK,
                $this->noStoreHeaders()
            );
        } catch (\InvalidArgumentException) {
            return $this->oauthError('invalid_request', Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/token', name: 'users_oauth_token', methods: ['POST'])]
    public function token(Request $request): JsonResponse
    {
        try {
            return new JsonResponse(
                $this->oauthService->exchangeAuthorizationCode($request->getPayload()->all()),
                Response::HTTP_OK,
                $this->noStoreHeaders()
            );
        } catch (\InvalidArgumentException) {
            return $this->oauthError('invalid_grant', Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/revoke', name: 'users_oauth_revoke', methods: ['POST'])]
    public function revoke(Request $request): Response
    {
        $params = $request->getPayload()->all();
        $token = is_string($params['token'] ?? null) ? $params['token'] : '';
        $clientId = is_string($params['client_id'] ?? null) ? $params['client_id'] : '';
        $this->oauthService->revokeAccessToken($token, $clientId);

        return new Response('', Response::HTTP_OK, $this->noStoreHeaders());
    }

    private function oauthError(string $error, int $status, ?string $description = null): JsonResponse
    {
        $body = ['error' => $error];
        if ($description !== null) {
            $body['error_description'] = $description;
        }

        return new JsonResponse($body, $status, $this->noStoreHeaders());
    }

    /** @return array<string, string> */
    private function noStoreHeaders(): array
    {
        return ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache'];
    }
}
