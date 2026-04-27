<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
                    'success' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data' => [],
                    'count' => 0,
                    'error' => $e->getMessage(),
                    'success' => false,
                ],
            ], 500);
        }
    }

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

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
}
