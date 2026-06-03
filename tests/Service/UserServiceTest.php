<?php

namespace ControleOnline\Tests\Service;

require_once dirname(__DIR__, 2) . '/src/Entity/User.php';
require_once dirname(__DIR__, 3) . '/common/src/Entity/Timezone.php';
require_once dirname(__DIR__, 3) . '/people/src/Entity/People.php';
require_once dirname(__DIR__, 2) . '/src/Service/UserService.php';
require_once dirname(__DIR__, 3) . '/common/src/Service/FileService.php';
require_once dirname(__DIR__, 3) . '/people/src/Service/PeopleRoleService.php';

use ControleOnline\Entity\People;
use ControleOnline\Entity\Timezone;
use ControleOnline\Entity\User;
use ControleOnline\Service\FileService;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    public function testGetUserSessionResolvesRuntimeRolesBeforeBuildingPayload(): void
    {
        $emailCollection = new class {
            public function count(): int
            {
                return 0;
            }

            public function first()
            {
                return null;
            }
        };

        $phoneCollection = new class {
            public function count(): int
            {
                return 0;
            }

            public function first()
            {
                return null;
            }
        };

        $people = $this->createMock(People::class);
        $people->method('getId')->willReturn(6);
        $people->method('getName')->willReturn('Alexandre');
        $people->method('getAlias')->willReturn('Alemac');
        $people->method('getEmail')->willReturn($emailCollection);
        $people->method('getPhone')->willReturn($phoneCollection);
        $people->method('getLanguage')->willReturn(null);
        $people->method('getEnabled')->willReturn(true);

        $roleService = $this->createMock(PeopleRoleService::class);
        $roleService
            ->expects(self::once())
            ->method('getGrantedRoles')
            ->with($people)
            ->willReturn(['ROLE_OWNER', 'ROLE_SUPER']);

        $service = $this->buildService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(FileService::class),
            $roleService,
        );

        $user = new User();
        $user->setUsername('alemac@mac.com');
        $user->setPeople($people);

        $session = $service->getUserSession($user);

        self::assertSame(['ROLE_OWNER', 'ROLE_SUPER'], $session['roles']);
        self::assertSame(['ROLE_OWNER', 'ROLE_SUPER'], $user->getRoles());
    }

    public function testUpdatePreferencesFromContentAcceptsTimezoneIri(): void
    {
        $timezone = $this->createTimezone(12, 'America/Sao_Paulo');
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(12)
            ->willReturn($timezone);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Timezone::class)
            ->willReturn($repository);
        $manager->expects(self::once())->method('persist');
        $manager->expects(self::once())->method('flush');

        $service = $this->buildService($manager);
        $user = new User();

        $updatedUser = $service->updatePreferencesFromContent(
            $user,
            json_encode(['timezone' => '/timezones/12'])
        );

        self::assertSame($timezone, $updatedUser->getTimezone());
    }

    public function testUpdatePreferencesFromContentClearsTimezone(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('getRepository');
        $manager->expects(self::once())->method('persist');
        $manager->expects(self::once())->method('flush');

        $service = $this->buildService($manager);
        $user = new User();
        $user->setTimezone($this->createTimezone(5, 'UTC'));

        $updatedUser = $service->updatePreferencesFromContent(
            $user,
            json_encode(['timezone' => null])
        );

        self::assertNull($updatedUser->getTimezone());
    }

    private function buildService(
        EntityManagerInterface $manager,
        ?FileService $fileService = null,
        ?PeopleRoleService $peopleRoleService = null,
    ): UserService
    {
        return new UserService(
            $manager,
            $this->createMock(UserPasswordHasherInterface::class),
            $fileService ?? $this->createMock(FileService::class),
            $peopleRoleService ?? $this->createMock(PeopleRoleService::class),
        );
    }

    private function createTimezone(int $id, string $name): Timezone
    {
        $timezone = new Timezone();
        $timezone->setName($name);

        $reflection = new \ReflectionClass(Timezone::class);
        $property = $reflection->getProperty('id');
        $property->setValue($timezone, $id);

        return $timezone;
    }
}
