<?php

namespace ControleOnline\Tests\Service;

require_once dirname(__DIR__, 2) . '/src/Entity/User.php';
require_once dirname(__DIR__, 3) . '/common/src/Entity/Timezone.php';
require_once dirname(__DIR__, 2) . '/src/Service/UserService.php';
require_once dirname(__DIR__, 3) . '/common/src/Service/FileService.php';
require_once dirname(__DIR__, 3) . '/people/src/Service/PeopleRoleService.php';

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

    private function buildService(EntityManagerInterface $manager): UserService
    {
        return new UserService(
            $manager,
            $this->createMock(UserPasswordHasherInterface::class),
            $this->createMock(FileService::class),
            $this->createMock(PeopleRoleService::class),
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
