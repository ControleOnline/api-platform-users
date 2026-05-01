<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Email;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;

class UserService
{
    private $request;

    public function __construct(
        private EntityManagerInterface $manager,
        private UserPasswordHasherInterface $passwordHasher,
        private FileService $fileService,
        private Security $security,
        private PeopleRoleService $peopleRoleService,
        private RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function changePassword(User $user, $password)
    {
        $this->denyUnlessCanManagePeople($user->getPeople());

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setHash($hashedPassword);

        $this->manager->persist($user);
        $this->manager->flush();
        return $user;
    }

    public function changePasswordFromContent(User $user, ?string $content)
    {
        $payload = $this->decodePayload($content);
        if (!isset($payload['password'])) {
            throw new BadRequestHttpException('password is required');
        }

        return $this->changePassword($user, $payload['password']);
    }

    public function changeApiKey(User $user)
    {
        $this->denyUnlessCanManagePeople($user->getPeople());

        $user->generateApiKey();

        $this->manager->persist($user);
        $this->manager->flush();
        return $user;
    }

    public function discoveryUser($email, $password, $firstName, $lastName)
    {
        $user = $this->manager->getRepository(User::class)
            ->findOneBy([
                'username' => $email,
            ]);

        $people = $this->discoveryPeople($email, $firstName, $lastName);

        if (!$user) {
            $user = $this->createUser($people, $email, $password);
        }

        return $user;
    }

    public function getUserSession(User $user)
    {
        $resolvedRoles = $this->peopleRoleService->getGrantedRoles($user->getPeople());
        $user->setResolvedRoles($resolvedRoles);

        $email = '';
        $code = '';
        $number = '';

        if ($user->getPeople()->getEmail()->count() > 0) {
            $email = $user->getPeople()->getEmail()->first()->getEmail();
        }

        if ($user->getPeople()->getPhone()->count() > 0) {
            $phone = $user->getPeople()->getPhone()->first();
            $code = $phone->getDdd();
            $number = $phone->getPhone();
        }

        return [
            'id' => $user->getPeople()->getId(),
            'username' => $user->getUsername(),
            'name' => $user->getPeople()->getName(),
            'alias' => $user->getPeople()->getAlias(),
            'nickname' => $user->getPeople()->getAlias(),
            'roles' => $resolvedRoles,
            'api_key' => $user->getApiKey(),
            'people' => $user->getPeople()->getId(),
            'language' => $user->getPeople()->getLanguage()?->getLanguage(),
            'mycompany' => $this->getCompanyId($user),
            'realname' => $this->getUserRealName($user->getPeople()),
            'avatar' => $this->fileService->getFileUrl($user->getPeople()),
            'email' => $email,
            'phone' => sprintf('%s%s', $code, $number),
            'active' => (int) $user->getPeople()->getEnabled(),
        ];
    }

    private function getUserRealName(People $people): string
    {
        $realName = 'John Doe';

        if ($people->getPeopleType() == 'J') {
            $realName = $people->getAlias();
        } else {
            if ($people->getPeopleType() == 'F') {
                $realName = $people->getName() . ' ' . $people->getAlias();
                $realName = trim($realName);
            }
        }

        return $realName;
    }

    public function discoveryPeople($mail, $firstName = '', $lastName = '')
    {
        $people = null;

        $email = $this->manager->getRepository(Email::class)
            ->findOneBy([
                'email' => $mail,
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
            $email->setPeople($people);
            $this->manager->persist($email);
        }

        $this->manager->persist($people);
        $this->manager->flush();
        return $people;
    }

    public function createUser(People $people, $username, $password)
    {
        $this->denyUnlessCanManagePeople($people);

        $user = $this->manager->getRepository(User::class)
            ->findOneBy([
                'username' => $username,
            ]);

        if ($user) {
            throw new Exception("User already exists", 301);
        }

        $user = new User();
        $user->setPeople($people);
        $user->setHash($this->passwordHasher->hashPassword($user, $password));
        $user->setUsername($username);

        $this->manager->persist($user);
        $this->manager->flush();
        return $user;
    }

    public function createUserFromContent(?string $content)
    {
        $payload = $this->decodePayload($content);

        if (
            !isset($payload['people']) ||
            !isset($payload['username']) ||
            !isset($payload['password'])
        ) {
            throw new BadRequestHttpException('people, username and password are required');
        }

        $people = $this->manager->getRepository(People::class)->find($payload['people']);
        if (!$people instanceof People) {
            throw new BadRequestHttpException('people not found');
        }

        return $this->createUser(
            $people,
            $payload['username'],
            $payload['password']
        );
    }

    public function deleteUser(People $person, int $userId): bool
    {
        $this->denyUnlessCanManagePeople($person);

        try {
            $this->manager->getConnection()->beginTransaction();

            if ($userId <= 0) {
                throw new \InvalidArgumentException('Document id is not defined');
            }

            $users = $this->manager->getRepository(User::class)->findBy(['people' => $person]);
            if (count($users) === 1) {
                throw new \InvalidArgumentException('Deve existir pelo menos um usuário');
            }

            $user = $this->manager->getRepository(User::class)->findOneBy([
                'id' => $userId,
                'people' => $person,
            ]);

            if (!$user instanceof User) {
                throw new \InvalidArgumentException('Person user was not found');
            }

            $this->manager->remove($user);
            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }

            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
    }

    public function deleteUserFromContent(People $person, ?string $content): bool
    {
        $payload = $this->decodePayload($content);

        return $this->deleteUser(
            $person,
            (int) ($payload['id'] ?? 0)
        );
    }

    public function getCompany(User $user)
    {
        $peopleLink = $user->getPeople()->getLink()->first();

        if ($peopleLink !== false && $peopleLink->getCompany() instanceof People) {
            return $peopleLink->getCompany();
        }
    }

    public function getCompanyId(User $user)
    {
        $company = $this->getCompany($user);
        return $company ? $company->getId() : null;
    }

    public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $myPeople = $this->getMyPeople();
        $managedCompanyIds = array_map(
            static fn(People $company): int => (int) $company->getId(),
            $this->getManagedCompanies()
        );

        if (!$myPeople instanceof People && $managedCompanyIds === []) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $peopleAlias = 'user_people';
        if (!in_array($peopleAlias, $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->innerJoin(sprintf('%s.people', $rootAlias), $peopleAlias);
        }

        $peopleLinkAlias = 'user_people_link';
        if (!in_array($peopleLinkAlias, $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->leftJoin(
                PeopleLink::class,
                $peopleLinkAlias,
                'WITH',
                sprintf('%s.people = %s.id', $peopleLinkAlias, $peopleAlias)
            );
        }

        $visibilityConditions = [];
        if ($myPeople instanceof People) {
            $visibilityConditions[] = sprintf('%s.id = :myPeopleId', $peopleAlias);
            $queryBuilder->setParameter('myPeopleId', (int) $myPeople->getId());
        }

        if ($managedCompanyIds !== []) {
            $visibilityConditions[] = sprintf('%s.company IN(:managedCompanies)', $peopleLinkAlias);
            $queryBuilder->setParameter('managedCompanies', $managedCompanyIds);
        }

        if ($visibilityConditions === []) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$visibilityConditions));
    }

    public function getMyPeople(): ?People
    {
        $token = $this->security->getToken();
        if (!$token) {
            return null;
        }

        $currentUser = $token->getUser();
        if (!is_object($currentUser) || !method_exists($currentUser, 'getPeople')) {
            return null;
        }

        return $currentUser->getPeople();
    }

    public function getMyCompanies(): array
    {
        return $this->peopleRoleService->getAccessibleCompaniesForPeople(
            $this->getMyPeople(),
            PeopleLink::EMPLOYEE_LINK
        );
    }

    public function getManagedCompanies(): array
    {
        return $this->peopleRoleService->getAccessibleCompaniesForPeople(
            $this->getMyPeople(),
            PeopleLink::MANAGER_LINK
        );
    }

    private function denyUnlessCanManagePeople(People $people): void
    {
        if ($this->canManagePeople($people)) {
            return;
        }

        throw new AccessDeniedHttpException('You should not pass!!!');
    }

    private function canManagePeople(People $people): bool
    {
        $myPeople = $this->getMyPeople();
        if (!$myPeople instanceof People) {
            return false;
        }

        $targetCompanyIds = $this->getCompanyIdsForPeople($people);
        if ($targetCompanyIds === []) {
            return false;
        }

        $managedCompanyIds = array_map(
            static fn(People $company): int => (int) $company->getId(),
            $this->getManagedCompanies()
        );

        if ($managedCompanyIds === []) {
            return false;
        }

        return array_intersect($managedCompanyIds, $targetCompanyIds) !== [];
    }

    private function getCompanyIdsForPeople(People $people): array
    {
        $companyIds = [];

        foreach ($people->getLink() as $link) {
            if (!$link instanceof PeopleLink) {
                continue;
            }

            $company = $link->getCompany();
            if ($company instanceof People) {
                $companyIds[] = (int) $company->getId();
            }
        }

        return array_values(array_unique(array_filter($companyIds)));
    }

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
