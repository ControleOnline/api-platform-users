<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\RecoveryAccess;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PasswordRecoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;

class CompletePasswordRecoveryAction
{
    public function __construct(
        private PasswordRecoveryService $passwordRecoveryService,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(RecoveryAccess $data): JsonResponse
    {
        try {
            $this->passwordRecoveryService->completeRecovery($data);

            return new JsonResponse([
                'success' => true,
                'message' => 'Senha redefinida com sucesso.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->hydratorService->error($e),
                400
            );
        }
    }
}
