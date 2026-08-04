<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\AccountVerificationService;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CompleteAccountVerificationAction
{
    public function __construct(
        private AccountVerificationService $accountVerificationService,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->accountVerificationService->completeVerificationFromContent(
                $request->getContent()
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Cadastro confirmado com sucesso. Voce ja pode entrar.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->hydratorService->error($e),
                400
            );
        }
    }
}
