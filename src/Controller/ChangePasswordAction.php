<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ControleOnline\Entity\User;
use ControleOnline\Service\UserService;

class ChangePasswordAction
{

  public function __construct(
    private UserService $service,
    private HydratorService $hydratorService

  ) {}

  public function __invoke(User $data, Request $request)
  {

    try {
      $user = $this->service->changePasswordFromContent(
        $data,
        $request->getContent()
      );

      return new JsonResponse(
        $this->hydratorService->item(
          User::class,
          $user->getId(),
          "user:read"
        )
      );
    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ], 500);
    }
  }
}
