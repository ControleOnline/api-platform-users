<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Email;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserService
{
  public function __construct(
    private EntityManagerInterface $manager,
    private  UserPasswordEncoderInterface $encoder,
    private FileService $fileService
  ) {}
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


  public function discoveryUser($email, $password, $firstName, $lastName)
  {

    $user = $this->manager->getRepository(User::class)
      ->findOneBy([
        'username'       => $email,
      ]);


    $people = $this->discoveryPeople($email, $firstName, $lastName);

    if (!$user)
      $user = $this->createUser($people, $email, $password);

    return   $user;
  }

  public function getUserSession($user)
  {

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

    return [
      'id'   => $user->getPeople()->getId(),
      'username' => $user->getUsername(),
      'roles'    => $user->getRoles(),
      'api_key'  => $user->getApiKey(),
      'people'   => $user->getPeople()->getId(),
      'mycompany'  => $this->getCompanyId($user),
      'realname' => $this->getUserRealName($user->getPeople()),
      'avatar'   => $this->fileService->getFileUrl($user->getPeople()),
      'email'    => $email,
      'phone'    => sprintf('%s%s', $code, $number),
      'active'   => (int) $user->getPeople()->getEnabled(),
    ];
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

  public function discoveryPeople($mail, $firstName = '', $lastName = '')
  {
    $email = $this->manager->getRepository(Email::class)
      ->findOneBy([
        'email'       => $mail,
      ]);
    if ($email) {
      $people = $email->getPeople();
    } else {
      $email = new Email();
      $email->setEmail($mail);
      $this->manager->persist($email);
    }

    if (!$people) {

      $lang = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-BR']);
      $people = new People();
      $people->setAlias($firstName);
      $people->setName($lastName);
      $people->setLanguage($lang);
      //$people->setBilling(0);
      //$people->setBillingDays('daily');
      //$people->setPaymentTerm(1);
      //$people->setIcms(0);
      $email->setPeople($people);
      $this->manager->persist($email);
    }

    $this->manager->persist($people);
    $this->manager->flush();
    return $people;
  }

  public function createUser(People $people, $username, $password)
  {
    if (!$this->getPermission())
      throw new Exception("You should not pass!!!", 301);

    $user = $this->manager->getRepository(User::class)
      ->findOneBy([
        'username'       => $username,
      ]);

    if ($user)
      throw new Exception("User already exists", 301);

    $user = new User();
    $user->setPeople($people);
    $user->setHash($this->encoder->encodePassword($user, $password));
    $user->setUsername($username);

    $this->manager->persist($user);
    $this->manager->flush();
    return $user;
  }


  public function getCompany(User $user)
  {
    $peopleLink = $user->getPeople()->getLink()->first();

    if ($peopleLink !== false && $peopleLink->getCompany() instanceof People)
      return $peopleLink->getCompany();
  }

  public function getCompanyId(User $user)
  {
    $company = $this->getCompany($user);
    return $company ? $company->getId() : null;
  }

  /**
   * @todo arrumar 
   */
  private function getPermission()
  {
    return true;
  }
}
