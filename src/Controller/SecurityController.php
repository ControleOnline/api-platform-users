<?php

namespace ControleOnline\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use ControleOnline\Entity\User;
use ControleOnline\Entity\People;
use ControleOnline\Service\PeopleRoleService;

class SecurityController extends AbstractController
{


  public function __construct(
    private PeopleRoleService $roleService,
    private EntityManagerInterface $manager,
  ) {
  }

  public function __invoke(Request $request)
  {
    /**
     * @var \ControleOnline\Entity\User
     */
    $user = $this->getUser();

    if ($user === null)
      return $this->json([
        'error' => 'User not found'
      ]);

    // get contact data from user

    $email  = '';
    $code   = '';
    $number = '';

    if ($user->getPeople()->getEmail()->count() > 0)
      $email = $user->getPeople()->getEmail()->first()->getEmail();

    if ($user->getPeople()->getPhone()->count() > 0) {
      $phone  = $user->getPeople()->getPhone()->first();
      $code   = $phone->getDdd();
      $number = $phone->getPhone();
    }

    return $this->json([
      'id'   => $user->getPeople()->getId(),
      'username' => $user->getUsername(),
      'roles'    => $user->getRoles(),
      'api_key'  => $user->getApiKey(),
      'people'   => $user->getPeople()->getId(),
      'mycompany'  => $this->getCompanyId($user),
      'realname' => $this->getUserRealName($user->getPeople()),
      'avatar'   => $user->getPeople()->getFile() ? '/files/download/' . $user->getPeople()->getFile()->getId() : null,
      'email'    => $email,
      'phone'    => sprintf('%s%s', $code, $number),
      'active'   => (int) $user->getPeople()->getEnabled(),
    ]);
  }

  private function getUserRealName(People $people): string
  {
    $realName = 'John Doe';

    if ($people->getPeopleType() == 'J')
      $realName = $people->getAlias();

    else {
      if ($people->getPeopleType() == 'F') {
        $realName  = $people->getName();
        $realName .= ' ' . $people->getAlias();
        $realName  = trim($realName);
      }
    }

    return $realName;
  }

  private function getCompany(User $user): ?People
  {
    $peopleLink = $this->manager->getRepository(People::class)->getPeopleLinks($user->getPeople(), 'employee', 1);
    if ($peopleLink !== false && $peopleLink->getCompany() instanceof People)
      return $peopleLink->getCompany();
  }

  private function getCompanyId(User $user): ?int
  {
    $company = $this->getCompany($user);
    return $company ? $company->getId() : null;
  }
}
