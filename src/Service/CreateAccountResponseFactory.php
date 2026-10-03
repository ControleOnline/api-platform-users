<?php

namespace ControleOnline\Service;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CreateAccountResponseFactory
{
    public function success(array $session): array
    {
        return [
            'response' => [
                'data' => $session,
                'count' => 1,
                'error' => '',
                'success' => true,
            ],
        ];
    }

    public function error(\Throwable $exception): array
    {
        return [
            'response' => [
                'data' => [],
                'count' => 0,
                'error' => $exception->getMessage(),
                'success' => false,
            ],
        ];
    }

    public function statusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception->getCode() >= 300 && $exception->getCode() < 500) {
            return 400;
        }

        return 500;
    }
}
