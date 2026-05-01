<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\User;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ChangeApiKeyAction
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UserService $service,
        private HydratorService $hydratorService

    ) {
    }

    public function __invoke(User $data)
    {
        try {
            $user = $this->service->changeApiKey($data);

            return new JsonResponse(
                $this->hydratorService->item(
                    User::class,
                    $user->getId(),
                    "user:read"
                )
            );
        } catch (\Throwable $e) {
            $statusCode = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'error' => $e->getMessage(),
                    'success' => false,
                ],
            ], $statusCode);
        }
    }
}
