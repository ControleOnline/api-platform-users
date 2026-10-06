<?php

namespace ControleOnline\Users\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\FileService;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceCreateAccountTest extends TestCase
{
    public function testRequiresNameEmailAndPassword(): void
    {
        $service = $this->createService();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('name, email and password are required');

        $service->createAccountSessionFromContent(json_encode([
            'email' => 'maria@example.com',
            'password' => 'secret',
        ], JSON_THROW_ON_ERROR));
    }

    public function testRejectsDifferentPasswordConfirmation(): void
    {
        $service = $this->createService();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('password confirmation does not match');

        $service->createAccountSessionFromContent(json_encode([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret',
            'confirmPassword' => 'different',
        ], JSON_THROW_ON_ERROR));
    }

    public function testAnonymousRegistrationCreatesANewIdentityWithoutAdministrativeAccess(): void
    {
        $persisted = [];
        $manager = $this->createMock(EntityManagerInterface::class);
        $repository = new class {
            public function findOneBy(array $criteria): mixed
            {
                return null;
            }
        };
        $timezone = (new \ControleOnline\Entity\Timezone())->setName('America/Sao_Paulo');
        $timezoneRepository = new class($timezone) {
            public function __construct(private object $timezone) {}
            public function findOneBy(array $criteria): object { return $this->timezone; }
        };
        $languageRepository = new class {
            public function findOneBy(array $criteria): object { return new \ControleOnline\Entity\Language(); }
        };
        $manager->method('getRepository')->willReturnCallback(static fn(string $class) => match ($class) {
            \ControleOnline\Entity\Timezone::class => $timezoneRepository,
            \ControleOnline\Entity\Language::class => $languageRepository,
            default => $repository,
        });
        $manager->expects(self::exactly(3))->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persisted): void { $persisted[] = $entity; }
        );
        $manager->expects(self::once())->method('flush');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hashPassword')->with(self::isInstanceOf(User::class), 'secret')->willReturn('hashed-secret');
        $fileService = $this->createMock(FileService::class);
        $fileService->expects(self::never())->method('getFileUrl');
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->expects(self::never())->method('getGrantedRoles');
        $service = new UserService($manager, $hasher, $fileService, $this->createStub(TokenStorageInterface::class), $roles, new RequestStack());

        $session = $service->createAccountSessionFromContent($this->signupPayload());
        self::assertSame('maria@example.com', $session['username']);
        self::assertSame([], $session['roles']);
        self::assertCount(3, $persisted);
        $users = array_values(array_filter($persisted, static fn(object $entity) => $entity instanceof User));
        self::assertSame('hashed-secret', $users[0]->getHash());
        self::assertSame($timezone, $users[0]->getTimezone());
    }

    public function testDuplicateRegistrationDoesNotMutateOrReturnExistingSession(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $repository = new class {
            public function findOneBy(array $criteria): User { return new User(); }
        };
        $manager->expects(self::once())->method('getRepository')->with(User::class)->willReturn($repository);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $service = new UserService($manager, $hasher, new FileService(), $this->createStub(TokenStorageInterface::class), new PeopleRoleService(), new RequestStack());
        $this->expectException(BadRequestHttpException::class);
        $service->createAccountSessionFromContent($this->signupPayload());
    }

    public function testPublicRegistrationCannotClaimAPersonWithAnExistingEmail(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $emptyUsers = new class {
            public function findOneBy(array $criteria): mixed { return null; }
        };
        $existingEmails = new class {
            public function findOneBy(array $criteria): object { return new \ControleOnline\Entity\Email(); }
        };
        $manager->expects(self::exactly(2))->method('getRepository')->willReturnCallback(
            static fn(string $class): object => match ($class) {
                User::class => $emptyUsers,
                \ControleOnline\Entity\Email::class => $existingEmails,
            }
        );
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $service = new UserService($manager, $hasher, new FileService(), $this->createStub(TokenStorageInterface::class), new PeopleRoleService(), new RequestStack());
        $this->expectException(BadRequestHttpException::class);
        $service->createAccountSessionFromContent($this->signupPayload());
    }

    public function testAnonymousAdministrativeCreationRemainsDeniedBeforeMutation(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('getRepository');
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');
        $service = new UserService($manager, $this->createStub(UserPasswordHasherInterface::class), new FileService(), $this->createStub(TokenStorageInterface::class), new PeopleRoleService(), new RequestStack());
        $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);
        $service->createUser(new People(), 'maria@example.com', 'secret');
    }

    private function signupPayload(): string
    {
        return json_encode(['name' => 'Maria Silva', 'email' => 'maria@example.com', 'password' => 'secret', 'confirmPassword' => 'secret'], JSON_THROW_ON_ERROR);
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
