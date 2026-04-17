<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\PasswordRecovery;
use ControleOnline\Service\PasswordRecoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;

class PasswordRecoveryAction
{
    public function __construct(
        private PasswordRecoveryService $passwordRecoveryService
    ) {}

    public function __invoke(PasswordRecovery $data): JsonResponse
    {
        try {
            $this->passwordRecoveryService->requestPasswordRecovery(
                (string) $data->username,
                (string) $data->email
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Se o login existir, o link de recuperação será enviado para o e-mail informado.',
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Não foi possível enviar o link de recuperação.',
            ]);
        }
    }
}
