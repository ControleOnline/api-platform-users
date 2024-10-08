<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\HydratorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ControleOnline\Service\UserService;

class CreateUserAction
{

  public function __construct(
    private EntityManagerInterface $manager,
    private UserService $service,
    private HydratorService $hydratorService

  ) {}

  public function __invoke(Request $request)
  {

    try {
      $payload   = json_decode($request->getContent());
      $people =  $this->manager->getRepository(People::class)->find($payload->people);

      $user = $this->service->createUser(
        $people,
        $payload->username,
        $payload->password
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
