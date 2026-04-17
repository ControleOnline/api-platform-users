<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\PasswordRecoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ValidateRecoveryTokenController
{
    public function __construct(
        private PasswordRecoveryService $passwordRecoveryService
    ) {}

    #[Route('/password_recoveries/validate/{token}', name: 'password_recovery_validate', methods: ['GET'])]
    public function __invoke(string $token): JsonResponse
    {
        $valid = $this->passwordRecoveryService->validateRecoveryToken($token);

        return new JsonResponse([
            'valid' => $valid,
            'message' => $valid
                ? 'Link de recuperação válido.'
                : 'O link de recuperação é inválido ou expirou.',
        ]);
    }
}
