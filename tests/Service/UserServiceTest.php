<?php

namespace ControleOnline\Users\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
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

class UserServiceTest extends TestCase
{
    public function testCreateUserAllowsManagingPeopleFromAdministrativeCompany(): void
    {
        $company = new People(10);
        $currentPeople = new People(1, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company),
        ]));
        $targetPeople = new People(2, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company),
        ]));

        $existingUserRepository = new class {
            public function findOneBy(array $criteria): ?User
            {
                return null;
            }
        };

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($existingUserRepository);
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
        $company = new People(10);
        $currentPeople = new People(1, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company),
        ]));
        $targetPeople = new People(2, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company),
        ]));

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $service = $this->buildService($manager, $currentPeople, [$company], []);

        $this->expectException(AccessDeniedHttpException::class);
        $service->createUser($targetPeople, 'blocked@example.com', 'secret');
    }

    public function testCreateUserRejectsPeopleOutsideAdministrativeCompanies(): void
    {
        $currentPeople = new People(1, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink(new People(10)),
        ]));
        $targetPeople = new People(2, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink(new People(20)),
        ]));

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $service = $this->buildService($manager, $currentPeople, [new People(10)], [new People(10)]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->createUser($targetPeople, 'blocked@example.com', 'secret');
    }

    public function testDeleteUserRejectsPeopleWhoseOnlyLinkIsDisabled(): void
    {
        $company = new People(10);
        $currentPeople = new People(1, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company),
        ]));
        $targetPeople = new People(2, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company, false),
        ]));

        $manager = $this->createMock(EntityManagerInterface::class);

        $service = $this->buildService($manager, $currentPeople, [$company], [$company]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->deleteUser($targetPeople, 99);
    }

    public function testDeleteUserRejectsPeopleWhoseCompanyIsDisabled(): void
    {
        $company = new People(10, null, 0);
        $currentPeople = new People(1, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink(new People(10)),
        ]));
        $targetPeople = new People(2, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company),
        ]));

        $manager = $this->createMock(EntityManagerInterface::class);

        $service = $this->buildService($manager, $currentPeople, [new People(10)], [new People(10)]);

        $this->expectException(AccessDeniedHttpException::class);
        $service->deleteUser($targetPeople, 99);
    }

    public function testSecurityFilterRestrictsUsersToSelfAndAdministrativeCompanies(): void
    {
        $company = new People(10);
        $currentPeople = new People(1, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company),
        ]));

        $manager = $this->createMock(EntityManagerInterface::class);
        $service = $this->buildService($manager, $currentPeople, [$company], [$company]);

        $queryBuilder = new class extends QueryBuilder {
            public array $aliases = ['u'];
            public array $joins = [];
            public array $conditions = [];
            public array $parameters = [];

            public function getAllAliases()
            {
                return $this->aliases;
            }

            public function innerJoin($join, $alias, $conditionType = null, $condition = null)
            {
                $this->aliases[] = $alias;
                $this->joins[] = ['inner', $join, $alias, $condition];
                return $this;
            }

            public function leftJoin($join, $alias, $conditionType = null, $condition = null)
            {
                $this->aliases[] = $alias;
                $this->joins[] = ['left', $join, $alias, $condition];
                return $this;
            }

            public function andWhere($condition)
            {
                $this->conditions[] = $condition;
                return $this;
            }

            public function setParameter($key, $value, $type = null)
            {
                $this->parameters[$key] = $value;
                return $this;
            }

            public function expr()
            {
                return new class {
                    public function orX(...$conditions): string
                    {
                        return implode(' OR ', $conditions);
                    }
                };
            }
        };

        $service->securityFilter($queryBuilder, User::class, 'collection', 'u');

        self::assertCount(3, $queryBuilder->joins);
        self::assertSame('user_people_link.people = user_people.id AND user_people_link.enable = true', $queryBuilder->joins[1][3]);
        self::assertSame('user_people_company.enabled = true', $queryBuilder->joins[2][3]);
        self::assertSame([10], $queryBuilder->parameters['managedCompanies']);
        self::assertSame(1, $queryBuilder->parameters['myPeopleId']);
        self::assertStringContainsString('user_people.id = :myPeopleId', $queryBuilder->conditions[0]);
        self::assertStringContainsString('user_people_company.id IN(:managedCompanies)', $queryBuilder->conditions[0]);
    }

    public function testSecurityFilterFallsBackToSelfWhenUserHasNoAdministrativeCompanies(): void
    {
        $company = new People(10);
        $currentPeople = new People(1, new \ControleOnline\Entity\LinkCollection([
            new PeopleLink($company),
        ]));

        $manager = $this->createMock(EntityManagerInterface::class);
        $service = $this->buildService($manager, $currentPeople, [$company], []);

        $queryBuilder = new class extends QueryBuilder {
            public array $aliases = ['u'];
            public array $joins = [];
            public array $conditions = [];
            public array $parameters = [];

            public function getAllAliases()
            {
                return $this->aliases;
            }

            public function innerJoin($join, $alias, $conditionType = null, $condition = null)
            {
                $this->aliases[] = $alias;
                $this->joins[] = ['inner', $join, $alias, $condition];
                return $this;
            }

            public function leftJoin($join, $alias, $conditionType = null, $condition = null)
            {
                $this->aliases[] = $alias;
                $this->joins[] = ['left', $join, $alias, $condition];
                return $this;
            }

            public function andWhere($condition)
            {
                $this->conditions[] = $condition;
                return $this;
            }

            public function setParameter($key, $value, $type = null)
            {
                $this->parameters[$key] = $value;
                return $this;
            }

            public function expr()
            {
                return new class {
                    public function orX(...$conditions): string
                    {
                        return implode(' OR ', $conditions);
                    }
                };
            }
        };

        $service->securityFilter($queryBuilder, User::class, 'collection', 'u');

        self::assertArrayNotHasKey('managedCompanies', $queryBuilder->parameters);
        self::assertSame(1, $queryBuilder->parameters['myPeopleId']);
        self::assertSame('user_people.id = :myPeopleId', $queryBuilder->conditions[0]);
    }

    private function buildService(
        EntityManagerInterface $manager,
        People $currentPeople,
        array $employeeCompanies,
        array $managedCompanies
    ): UserService {
        $currentUser = (new User())->setPeople($currentPeople);

        $token = new class($currentUser) implements \Symfony\Component\Security\Core\Authentication\Token\TokenInterface {
            public function __construct(private User $user)
            {
            }

            public function getUser(): User
            {
                return $this->user;
            }
        };

        $security = new class($token) implements TokenStorageInterface {
            public function __construct(private $token)
            {
            }

            public function getToken(): ?\Symfony\Component\Security\Core\Authentication\Token\TokenInterface
            {
                return $this->token;
            }
        };

        $hasher = new class implements UserPasswordHasherInterface {
            public function hashPassword(object $user, string $plainPassword): string
            {
                return 'hashed-' . $plainPassword;
            }
        };

        $requestStack = new RequestStack();

        return new UserService(
            $manager,
            $hasher,
            new FileService(),
            $security,
            new PeopleRoleService([
                'employee' => $employeeCompanies,
                'manager' => $managedCompanies,
            ]),
            $requestStack
        );
    }
}
