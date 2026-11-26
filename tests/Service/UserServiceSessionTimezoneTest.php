<?php

namespace ControleOnline\Users\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Timezone;
use ControleOnline\Entity\User;
use ControleOnline\Service\FileService;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserServiceSessionTimezoneTest extends TestCase
{
    public function testSessionExposesTheUserTimezone(): void
    {
        $timezone = (new Timezone())->setName('America/Sao_Paulo');
        $property = new \ReflectionProperty(Timezone::class, 'id');
        $property->setAccessible(true);
        $property->setValue($timezone, 8);

        $user = (new User())->setUsername('maria@example.com');
        $user->setPeople(new People());
        $user->setTimezone($timezone);

        $session = $this->createService()->getUserSession($user);

        self::assertSame(8, $session['timezone_id']);
        self::assertSame(['id' => 8, 'name' => 'America/Sao_Paulo'], $session['timezone']);
    }

    public function testSessionKeepsTimezoneEmptyWhenUserHasNone(): void
    {
        $user = (new User())->setUsername('maria@example.com');
        $user->setPeople(new People());

        $session = $this->createService()->getUserSession($user);

        self::assertNull($session['timezone_id']);
        self::assertNull($session['timezone']);
    }

    private function createService(): UserService
    {
        return new UserService(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(UserPasswordHasherInterface::class),
            $this->createStub(FileService::class),
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(PeopleRoleService::class),
            new RequestStack()
        );
    }
}
