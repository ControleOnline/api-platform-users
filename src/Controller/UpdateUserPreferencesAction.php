<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\User;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UpdateUserPreferencesAction
{
    public function __construct(
        private TokenStorageInterface $security,
        private UserService $service,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $this->security->getToken()?->getUser();

            if (!$user instanceof User) {
                throw new AccessDeniedHttpException('User not authenticated');
            }

            $updatedUser = $this->service->updatePreferencesFromContent(
                $user,
                $request->getContent()
            );

            return new JsonResponse($this->service->getUserSession($updatedUser));
        } catch (\Exception $e) {
            $statusCode = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            return new JsonResponse(
                $this->hydratorService->error($e),
                $statusCode
            );
        }
    }
}
