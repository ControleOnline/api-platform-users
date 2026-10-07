<?php

namespace ControleOnline\Users\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\User;
use ControleOnline\Entity\Timezone;
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

class UserServiceTest extends TestCase
{
    public function testCreateUserAllowsManagingPeopleFromAdministrativeCompany(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1, [
            $this->link($company),
        ]);
        $targetPeople = $this->people(2, [
            $this->link($company),
        ]);

        $timezone = (new Timezone())->setName('America/Sao_Paulo');
        $timezoneRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $timezoneRepository->method('findOneBy')->willReturn($timezone);
        $existingUserRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $existingUserRepository->method('findOneBy')->willReturn(null);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(static fn(string $class): object => match ($class) {
                User::class => $existingUserRepository,
                Timezone::class => $timezoneRepository,
            });
        $manager->expects(self::once())->method('persist');
        $manager->expects(self::once())->method('flush');

        $service = $this->buildService($manager, $currentPeople, [$company], [$company]);

        $created = $service->createUser($targetPeople, 'manager@example.com', 'secret');

        self::assertSame($targetPeople, $created->getPeople());
        self::assertSame('manager@example.com', $created->getUsername());
        self::assertSame('hashed-secret', $created->getHash());
        self::assertSame($timezone, $created->getTimezone());
    }

    public function testCreateUserRejectsPeopleWhenUserOnlyHasNonAdministrativeLink(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1, [
            $this->link($company),
        ]);
        $targetPeople = $this->people(2, [
            $this->link($company),
        ]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $service = $this->buildService($manager, $currentPeople, [$company], []);

        $this->expectException(AccessDeniedHttpException::class);
        $service->createUser($targetPeople, 'blocked@example.com', 'secret');
    }

    public function testCreateUserRejectsPeopleOutsideAdministrativeCompanies(): void
    {
        $currentPeople = $this->people(1, [
            $this->link($this->people(10)),
        ]);
        $targetPeople = $this->people(2, [
            $this->link($this->people(20)),
        ]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $service = $this->buildService($manager, $currentPeople, [$this->people(10)], [$this->people(10)]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->createUser($targetPeople, 'blocked@example.com', 'secret');
    }

    public function testDeleteUserRejectsPeopleWhoseOnlyLinkIsDisabled(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1, [
            $this->link($company),
        ]);
        $targetPeople = $this->people(2, [
            $this->link($company, false),
        ]);

        $manager = $this->createMock(EntityManagerInterface::class);

        $service = $this->buildService($manager, $currentPeople, [$company], [$company]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->deleteUser($targetPeople, 99);
    }

    public function testDeleteUserRejectsPeopleWhoseCompanyIsDisabled(): void
    {
        $company = $this->people(10, null, 0);
        $currentPeople = $this->people(1, [
            $this->link($this->people(10)),
        ]);
        $targetPeople = $this->people(2, [
            $this->link($company),
        ]);

        $manager = $this->createMock(EntityManagerInterface::class);

        $service = $this->buildService($manager, $currentPeople, [$this->people(10)], [$this->people(10)]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->deleteUser($targetPeople, 99);
    }

    public function testSecurityFilterRestrictsUsersToSelfAndAdministrativeCompanies(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1, [
            $this->link($company),
        ]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $service = $this->buildService($manager, $currentPeople, [$company], [$company]);

        $manager->method('getExpressionBuilder')->willReturn(new \Doctrine\ORM\Query\Expr());
        $queryBuilder = (new QueryBuilder($manager))->select('u')->from(User::class, 'u');

        $service->securityFilter($queryBuilder, User::class, 'collection', 'u');

        $joins = $queryBuilder->getDQLPart('join')['u'];
        self::assertCount(3, $joins);
        self::assertSame('user_people_link.people = user_people.id AND user_people_link.enable = true', $joins[1]->getCondition());
        self::assertSame('user_people_company.enabled = true', $joins[2]->getCondition());
        self::assertSame([10], $queryBuilder->getParameter('managedCompanies')->getValue());
        self::assertSame(1, $queryBuilder->getParameter('myPeopleId')->getValue());
        self::assertStringContainsString('user_people.id = :myPeopleId', (string) $queryBuilder->getDQLPart('where'));
        self::assertStringContainsString('user_people_company.id IN(:managedCompanies)', (string) $queryBuilder->getDQLPart('where'));
    }

    public function testSecurityFilterFallsBackToSelfWhenUserHasNoAdministrativeCompanies(): void
    {
        $company = $this->people(10);
        $currentPeople = $this->people(1, [
            $this->link($company),
        ]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $service = $this->buildService($manager, $currentPeople, [$company], []);

        $manager->method('getExpressionBuilder')->willReturn(new \Doctrine\ORM\Query\Expr());
        $queryBuilder = (new QueryBuilder($manager))->select('u')->from(User::class, 'u');

        $service->securityFilter($queryBuilder, User::class, 'collection', 'u');

        self::assertNull($queryBuilder->getParameter('managedCompanies'));
        self::assertSame(1, $queryBuilder->getParameter('myPeopleId')->getValue());
        self::assertSame('user_people.id = :myPeopleId', (string) $queryBuilder->getDQLPart('where'));
    }

    private function buildService(
        EntityManagerInterface $manager,
        People $currentPeople,
        array $employeeCompanies,
        array $managedCompanies
    ): UserService {
        $currentUser = (new User())->setPeople($currentPeople);

        $token = $this->createStub(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn($currentUser);
        $security = $this->createStub(TokenStorageInterface::class);
        $security->method('getToken')->willReturn($token);
        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturnCallback(
            static fn($user, string $password): string => 'hashed-' . $password
        );
        $roles = $this->createStub(PeopleRoleService::class);
        $roles->method('getAccessibleCompaniesForPeople')->willReturnCallback(
            static fn(?People $people, ?array $types): array =>
                $types === PeopleLink::MANAGER_LINK ? $managedCompanies : $employeeCompanies
        );

        $requestStack = new RequestStack();

        return new UserService(
            $manager,
            $hasher,
            $this->createStub(FileService::class),
            $security,
            $roles,
            $requestStack
        );
    }
    private function people(?int $id = null, ?array $links = null, int $enabled = 1): People
    {
        $people = (new People())->setEnabled($enabled);
        (new \ReflectionProperty(People::class, 'id'))->setValue($people, $id);
        foreach ($links ?? [] as $link) {
            $link->setPeople($people);
            $people->getLink()->add($link);
        }
        return $people;
    }

    private function link(People $company, bool $enabled = true): PeopleLink
    {
        return (new PeopleLink())->setCompany($company)->setEnabled($enabled);
    }
}
