<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\User;
use ControleOnline\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DeleteUserAction
{
    public function __construct(private UserService $service)
    {
    }

    public function __invoke(User $data)
    {
        try {
            $this->service->deleteUser($data->getPeople(), (int) $data->getId());

            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'error' => null,
                    'success' => true,
                ],
            ]);
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
