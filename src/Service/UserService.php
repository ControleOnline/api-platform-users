<?php

namespace App\Service;

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

  public function createUser($people_id, $username, $password)
  {
    if (!$this->getPermission())
      throw new Exception("You should not pass!!!", 301);
    
    $user = new User();
    $user->setPeople($this->manager->getRepository(User::class)->find($people_id));
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
