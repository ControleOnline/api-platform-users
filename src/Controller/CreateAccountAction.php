<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CreateAccountAction
{
    public function __construct(
        private UserService $service
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $this->decodePayload($request->getContent());

            foreach (['name', 'email', 'password'] as $field) {
                if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                    throw new BadRequestHttpException('name, email and password are required');
                }
            }

            if (
                isset($payload['confirmPassword']) &&
                (string) $payload['confirmPassword'] !== (string) $payload['password']
            ) {
                throw new BadRequestHttpException('password confirmation does not match');
            }

            [$firstName, $lastName] = $this->splitName((string) $payload['name']);

            $people = $this->service->discoveryPeople(
                (string) $payload['email'],
                $firstName,
                $lastName
            );

            $user = $this->service->getUserSession($this->service->createUser(
                $people,
                (string) $payload['email'],
                (string) $payload['password']
            ));

            return new JsonResponse([
                'response' => [
                    'data' => $user,
                    'count' => 1,
                    'error' => '',
                    'success' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'error' => $e->getMessage(),
                    'success' => false,
                ],
            ], $this->resolveStatusCode($e));
        }
    }

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('invalid json payload');
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        if ($name === '') {
            return ['', ''];
        }

        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    private function resolveStatusCode(\Throwable $exception): int
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
