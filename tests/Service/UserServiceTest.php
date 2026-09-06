<?php

namespace ControleOnline\Users\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\Timezone;
use ControleOnline\Entity\User;
use ControleOnline\Service\FileService;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserServiceTest extends TestCase
{
    public function testCreateUserAllowsManagingPeopleFromAdministrativeCompany(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1);
        $targetPeople = $this->people(2);
        $this->link($targetPeople, $company);

        $existingUserRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $existingUserRepository->method('findOneBy')->willReturn(null);
        $timezoneRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $timezoneRepository->method('findOneBy')->willReturn(new Timezone());

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->willReturnCallback(fn (string $class) => $class === User::class ? $existingUserRepository : $timezoneRepository);
        $manager->expects(self::once())->method('persist');
        $manager->expects(self::once())->method('flush');

        $service = $this->buildService($manager, $currentPeople, [$company], [$company]);

        $created = $service->createUser($targetPeople, 'manager@example.com', 'secret');

        self::assertSame($targetPeople, $created->getPeople());
        self::assertSame('manager@example.com', $created->getUsername());
        self::assertSame('hashed-secret', $created->getHash());
    }

    public function testCreateUserRejectsPeopleWhenUserOnlyHasNonAdministrativeLink(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1);
        $targetPeople = $this->people(2);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $service = $this->buildService($manager, $currentPeople, [$company], []);

        $this->expectException(AccessDeniedHttpException::class);
        $service->createUser($targetPeople, 'blocked@example.com', 'secret');
    }

    public function testCreateUserRejectsPeopleOutsideAdministrativeCompanies(): void
    {
        $currentPeople = $this->people(1);
        $targetPeople = $this->people(2);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $service = $this->buildService($manager, $currentPeople, [new People(10)], [new People(10)]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->createUser($targetPeople, 'blocked@example.com', 'secret');
    }

    public function testDeleteUserRejectsPeopleWhoseOnlyLinkIsDisabled(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1);
        $targetPeople = $this->people(2);

        $manager = $this->createMock(EntityManagerInterface::class);

        $service = $this->buildService($manager, $currentPeople, [$company], [$company]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->deleteUser($targetPeople, 99);
    }

    public function testDeleteUserRejectsPeopleWhoseCompanyIsDisabled(): void
    {
        $company = $this->people(10, false);
        $currentPeople = $this->people(1);
        $targetPeople = $this->people(2);

        $manager = $this->createMock(EntityManagerInterface::class);

        $service = $this->buildService($manager, $currentPeople, [new People(10)], [new People(10)]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->deleteUser($targetPeople, 99);
    }

    public function testSecurityFilterRestrictsUsersToSelfAndAdministrativeCompanies(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1);

        $manager = $this->createMock(EntityManagerInterface::class);
        $service = $this->buildService($manager, $currentPeople, [$company], [$company]);

        $queryBuilder = new QueryBuilder($manager);
        $queryBuilder->select('u')->from(User::class, 'u');

        $service->securityFilter($queryBuilder, User::class, 'collection', 'u');

        self::assertSame([10], $queryBuilder->getParameter('managedCompanies')->getValue());
        self::assertSame(1, $queryBuilder->getParameter('myPeopleId')->getValue());
        self::assertNotNull($queryBuilder->getDQLPart('where'));
    }

    public function testSecurityFilterFallsBackToSelfWhenUserHasNoAdministrativeCompanies(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1);

        $manager = $this->createMock(EntityManagerInterface::class);
        $service = $this->buildService($manager, $currentPeople, [$company], []);

        $queryBuilder = new QueryBuilder($manager);
        $queryBuilder->select('u')->from(User::class, 'u');

        $service->securityFilter($queryBuilder, User::class, 'collection', 'u');

        self::assertNull($queryBuilder->getParameter('managedCompanies'));
        self::assertSame(1, $queryBuilder->getParameter('myPeopleId')->getValue());
        self::assertNotNull($queryBuilder->getDQLPart('where'));
    }

    private function buildService(
        EntityManagerInterface $manager,
        People $currentPeople,
        array $employeeCompanies,
        array $managedCompanies
    ): UserService {
        $currentUser = (new User())->setPeople($currentPeople);

        $token = new UsernamePasswordToken($currentUser, 'test', []);

        $security = new class($token) implements TokenStorageInterface {
            public function __construct(private $token)
            {
            }

            public function getToken(): ?\Symfony\Component\Security\Core\Authentication\Token\TokenInterface
            {
                return $this->token;
            }

            public function setToken(?\Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token): void
            {
                $this->token = $token;
            }
        };

        $hasher = new class implements UserPasswordHasherInterface {
            public function hashPassword(object $user, string $plainPassword): string
            {
                return 'hashed-' . $plainPassword;
            }

            public function isPasswordValid(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface $user, string $plainPassword): bool
            {
                return false;
            }

            public function needsRehash(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface $user): bool
            {
                return false;
            }
        };

        $requestStack = new RequestStack();

        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('getAccessibleCompaniesForPeople')
            ->willReturnCallback(function ($people, $types = null) use ($employeeCompanies, $managedCompanies): array {
                return $types === PeopleLink::MANAGER_LINK ? $managedCompanies : $employeeCompanies;
            });
        $roles->method('getGrantedRoles')->willReturn(['ROLE_HUMAN']);

        return new UserService(
            $manager,
            $hasher,
            $this->createMock(FileService::class),
            $security,
            $roles,
            $requestStack
        );
    }

    private function people(int $id, bool $enabled = true): People
    {
        $people = new People();
        $property = new \ReflectionProperty(People::class, 'id');
        $property->setValue($people, $id);
        $people->setEnabled($enabled);

        return $people;
    }

    private function link(People $people, People $company): void
    {
        $property = new \ReflectionProperty(People::class, 'link');
        $property->setValue($people, new \Doctrine\Common\Collections\ArrayCollection([
            (new PeopleLink())->setCompany($company),
        ]));
    }
}
