<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\RecoveryAccess;
use ControleOnline\Service\PasswordRecoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;

class RecoveryAccessAction
{
    public function __construct(
        private PasswordRecoveryService $passwordRecoveryService
    ) {}

    public function __invoke(RecoveryAccess $data): JsonResponse
    {
        $token = trim((string) ($data->token ?: $data->lost));

        if ($token === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'O token de recuperação é obrigatório.',
            ]);
        }

        try {
            $success = $this->passwordRecoveryService->resetPassword($token, (string) $data->password);

            if (!$success) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'O link de recuperação é inválido ou expirou.',
                ]);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Senha alterada com sucesso.',
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Não foi possível alterar a senha.',
            ]);
        }
    }
}
