<?php

namespace ControleOnline\Controller;

use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use ControleOnline\Entity\User;
use ControleOnline\Service\UserService;

class ChangeApiKeyAction
{

  public function __construct(
    private EntityManagerInterface $manager,
    private UserService $service,
    private HydratorService $hydratorService

  ) {}

  public function __invoke(User $data)
  {

    try {

      $user = $this->service->changeApiKey($data);

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
