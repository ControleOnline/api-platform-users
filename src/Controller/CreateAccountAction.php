<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\CreateAccountResponseFactory;
use ControleOnline\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CreateAccountAction
{
    public function __construct(
        private UserService $service,
        private CreateAccountResponseFactory $responseFactory
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            return new JsonResponse(
                $this->responseFactory->success(
                    $this->service->createAccountSessionFromContent(
                        $request->getContent()
                    )
                )
            );
        } catch (\Throwable $exception) {
            return new JsonResponse(
                $this->responseFactory->error($exception),
                $this->responseFactory->statusCode($exception)
            );
        }
    }
}
