<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PasswordRecoveryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RequestPasswordRecoveryAction
{
    public function __construct(
        private PasswordRecoveryService $passwordRecoveryService,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->passwordRecoveryService->requestRecoveryFromContent(
                $request->getContent()
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Se o login existir, enviaremos as instrucoes de recuperacao por e-mail.',
            ], 202);
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->hydratorService->error($e),
                500
            );
        }
    }
}
