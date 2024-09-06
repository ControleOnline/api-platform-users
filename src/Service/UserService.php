<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserService
{
  public function __construct(private EntityManagerInterface $manager, private  UserPasswordEncoderInterface $encoder) {}
  public function changePassword(User $user, $password)
  {
    if (!$this->getPermission())
      throw new Exception("You should not pass!!!", 301);

    $user->setHash(
      $this->encoder->encodePassword($user, $password)
    );

    $this->manager->persist($user);
    $this->manager->flush();
    return $user;
  }

  public function changeApiKey(User $user)
  {
    if (!$this->getPermission())
      throw new Exception("You should not pass!!!", 301);

    $user->generateApiKey();

    $this->manager->persist($user);
    $this->manager->flush();
    return $user;
  }

  public function createUser(People $people, $username, $password)
  {
    if (!$this->getPermission())
      throw new Exception("You should not pass!!!", 301);

    $user = new User();
    $user->setPeople($people);
    $user->setHash($this->encoder->encodePassword($user, $password));
    $user->setUsername($username);

    $this->manager->persist($user);
    $this->manager->flush();
    return $user;
  }

  /**
   * @todo arrumar 
   */
  private function getPermission()
  {
    return true;
  }
}
