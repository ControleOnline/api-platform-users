<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PasswordRecoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CompletePasswordRecoveryAction
{
    public function __construct(
        private PasswordRecoveryService $passwordRecoveryService,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->passwordRecoveryService->completeRecoveryFromContent(
                $request->getContent()
            );

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
