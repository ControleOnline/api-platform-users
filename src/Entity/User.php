<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ControleOnline\Controller\ChangeApiKeyAction;
use ControleOnline\Controller\ChangePasswordAction;
use ControleOnline\Controller\CreateAccountAction;
use ControleOnline\Controller\CreateUserAction;
use ControleOnline\Controller\Oauth\MercadolivreReturnController;
use ControleOnline\Controller\SecurityController;
use ControleOnline\Entity\People;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: \ControleOnline\Repository\UserRepository::class)]
#[ORM\EntityListeners([\LogListener::class])]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'user_name', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'api_key', columns: ['api_key'])]
#[ORM\Index(name: 'people_id', columns: ['people_id'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/oauth/mercadolivre/return',
            controller: MercadolivreReturnController::class,
        ),
        new Post(
            uriTemplate: '/users',
            controller: CreateUserAction::class,
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
        ),
        new Post(
            uriTemplate: '/users/create-account',
            controller: CreateAccountAction::class,
            securityPostDenormalize: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(
            uriTemplate: '/users/{id}/change-api-key',
            controller: ChangeApiKeyAction::class,
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
        ),
        new Put(
            uriTemplate: '/users/{id}/change-password',
            controller: ChangePasswordAction::class,
            securityPostDenormalize: 'is_granted(\'ROLE_CLIENT\')',
        ),
        new Post(
            uriTemplate: '/token',
            controller: SecurityController::class,
            securityPostDenormalize: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
        ),
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Groups(['people:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    #[Groups(['people:read', 'user:read'])]
    private string $username;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $hash;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    private ?string $oauthUser = null;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    private ?string $oauthHash = null;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    private ?string $lostPassword = null;

    #[ORM\Column(type: 'string', length: 60, nullable: false)]
    #[Groups(['people:read', 'user:read'])]
    private string $apiKey;

    #[ORM\ManyToOne(targetEntity: People::class, inversedBy: 'user')]
    #[ORM\JoinColumn(name: 'people_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['people:read', 'user:read'])]
    private People $people;

    public function __construct()
    {
        $this->generateApiKey();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getPassword(): ?string
    {
        return $this->hash;
    }

    public function getRoles(): array
    {
        return ['ROLE_CLIENT'];
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // Clear temporary sensitive data if any
    }

    public function generateApiKey(): string
    {
        $this->apiKey = md5($this->getUsername() . microtime());
        return $this->apiKey;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
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

    public function getOauthHash(): ?string
    {
        return $this->oauthHash;
    }

    public function setOauthHash(?string $hash): self
    {
        $this->oauthHash = $hash;
        return $this;
    }

    public function setOauthUser(?string $user): self
    {
        $this->oauthUser = $user;
        return $this;
    }

    public function getOauthUser(): ?string
    {
        return $this->oauthUser;
    }

    public function setPeople(?People $people): self
    {
        $this->people = $people;
        return $this;
    }

    public function getPeople(): People
    {
        return $this->people;
    }

    public function setLostPassword(?string $hash): self
    {
        $this->lostPassword = $hash;
        return $this;
    }

    public function getLostPassword(): ?string
    {
        return $this->lostPassword;
    }
}