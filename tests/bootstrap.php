<?php

namespace Doctrine\ORM {
    interface EntityManagerInterface
    {
        public function getRepository(string $class);

        public function persist(object $object): void;

        public function flush(): void;

        public function remove(object $object): void;

        public function getConnection();
    }

    abstract class QueryBuilder
    {
        abstract public function getAllAliases();

        abstract public function innerJoin($join, $alias, $conditionType = null, $condition = null);

        abstract public function leftJoin($join, $alias, $conditionType = null, $condition = null);

        abstract public function andWhere($condition);

        abstract public function setParameter($key, $value, $type = null);

        abstract public function expr();
    }
}

namespace Symfony\Component\HttpFoundation {
    class JsonResponse
    {
        public function __construct(
            private array $data = [],
            private int $status = 200
        ) {
        }

        public function getStatusCode(): int
        {
            return $this->status;
        }

        public function getData(bool $assoc = false): array
        {
            return $this->data;
        }
    }

    class RequestStack
    {
        public function getCurrentRequest(): mixed
        {
            return null;
        }
    }
}

namespace Symfony\Component\HttpKernel\Exception {
    interface HttpExceptionInterface
    {
        public function getStatusCode(): int;
    }

    class BadRequestHttpException extends \RuntimeException implements HttpExceptionInterface
    {
        public function getStatusCode(): int
        {
            return 400;
        }
    }

    class AccessDeniedHttpException extends \RuntimeException implements HttpExceptionInterface
    {
        public function getStatusCode(): int
        {
            return 403;
        }
    }
}

namespace Symfony\Component\PasswordHasher\Hasher {
    interface UserPasswordHasherInterface
    {
        public function hashPassword(object $user, string $plainPassword): string;
    }
}

namespace Symfony\Component\Security\Core\Authentication\Token {
    interface TokenInterface
    {
        public function getUser();
    }
}

namespace Symfony\Component\Security\Core\Authentication\Token\Storage {
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

    interface TokenStorageInterface
    {
        public function getToken(): ?TokenInterface;
    }
}

namespace ControleOnline\Entity {
    class Email
    {
        public function setEmail(string $email): self
        {
            return $this;
        }

        public function setPeople(People $people): self
        {
            return $this;
        }
    }

    class Language
    {
    }

    class PeopleLink
    {
        public const EMPLOYEE_LINK = ['employee'];
        public const MANAGER_LINK = ['owner', 'director', 'manager'];

        public function __construct(
            private ?People $company = null,
            private bool $enabled = true
        ) {
        }

        public function getCompany(): ?People
        {
            return $this->company;
        }

        public function getEnabled(): bool
        {
            return $this->enabled;
        }
    }

    class LinkCollection implements \IteratorAggregate
    {
        public function __construct(private array $items = [])
        {
        }

        public function first(): mixed
        {
            return $this->items[0] ?? false;
        }

        public function getIterator(): \Traversable
        {
            return new \ArrayIterator($this->items);
        }
    }

    class User
    {
        private ?int $id = null;
        private ?People $people = null;
        private string $username = '';
        private string $hash = '';
        private string $apiKey = 'initial-key';

        public function getId(): ?int
        {
            return $this->id;
        }

        public function setId(int $id): self
        {
            $this->id = $id;
            return $this;
        }

        public function setPeople(People $people): self
        {
            $this->people = $people;
            return $this;
        }

        public function getPeople(): People
        {
            return $this->people;
        }

        public function setUsername(string $username): self
        {
            $this->username = $username;
            return $this;
        }

        public function getUsername(): string
        {
            return $this->username;
        }

        public function setHash(string $hash): self
        {
            $this->hash = $hash;
            return $this;
        }

        public function getHash(): string
        {
            return $this->hash;
        }

        public function generateApiKey(): string
        {
            $this->apiKey = 'rotated-key';
            return $this->apiKey;
        }

        public function getApiKey(): string
        {
            return $this->apiKey;
        }

        public function setResolvedRoles(array $roles): self
        {
            return $this;
        }
    }

    class People
    {
        public function __construct(
            private int $id = 0,
            private ?LinkCollection $link = null,
            private int $enabled = 1
        ) {
            $this->link ??= new LinkCollection();
        }

        public function getId(): int
        {
            return $this->id;
        }

        public function getLink(): LinkCollection
        {
            return $this->link;
        }

        public function getEmail(): object
        {
            return new class {
                public function count(): int
                {
                    return 0;
                }

                public function first(): mixed
                {
                    return null;
                }
            };
        }

        public function getPhone(): object
        {
            return new class {
                public function count(): int
                {
                    return 0;
                }

                public function first(): mixed
                {
                    return null;
                }
            };
        }

        public function getName(): string
        {
            return 'People';
        }

        public function getAlias(): string
        {
            return 'Alias';
        }

        public function getLanguage(): ?object
        {
            return null;
        }

        public function getEnabled(): int
        {
            return $this->enabled;
        }

        public function getPeopleType(): string
        {
            return 'F';
        }

        public function setAlias(string $alias): self
        {
            return $this;
        }

        public function setName(string $name): self
        {
            return $this;
        }

        public function setLanguage(Language $language): self
        {
            return $this;
        }
    }
}

namespace ControleOnline\Service {
    use ControleOnline\Entity\PeopleLink;

    class FileService
    {
        public function getFileUrl($people): string
        {
            return '';
        }
    }

    class PeopleRoleService
    {
        public function __construct(private array $companiesByRoleType = [])
        {
        }

        public function getGrantedRoles($people): array
        {
            return ['ROLE_HUMAN'];
        }

        public function getAccessibleCompaniesForPeople($people, $companyType = null): array
        {
            if ($companyType === PeopleLink::MANAGER_LINK) {
                return $this->companiesByRoleType['manager'] ?? [];
            }

            if ($companyType === PeopleLink::EMPLOYEE_LINK) {
                return $this->companiesByRoleType['employee'] ?? [];
            }

            return $this->companiesByRoleType['default'] ?? [];
        }
    }
}

namespace {
    require_once __DIR__ . '/../src/Service/UserService.php';
    require_once __DIR__ . '/../src/Controller/DeleteUserAction.php';
}
