<?php

namespace ControleOnline\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\UserService;

class SecurityController extends AbstractController
{


  public function __construct(
    private PeopleRoleService $roleService,
    private EntityManagerInterface $manager,
    protected UserService $userService
  ) {}

  public function __invoke(Request $request)
  {
    /**
     * @var \ControleOnline\Entity\User
     */
    $user = $this->getUser();

    if ($user === null) {
      return $this->json([
        'error' => 'User not found'
      ], Response::HTTP_UNAUTHORIZED);
    }

    $people = $user->getPeople();
    if ($people === null || !((int) $people->getEnabled() === 1)) {
      return $this->json([
        'error' => 'Usuário desativado',
        'code'  => 'USER_DISABLED',
      ], Response::HTTP_FORBIDDEN);
    }

    return $this->json($this->userService->getUserSession($user));

  }
}
