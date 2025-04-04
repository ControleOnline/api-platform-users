<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ControleOnline\Controller\ChangeApiKeyAction;
use ControleOnline\Controller\ChangePasswordAction;
use ControleOnline\Controller\CreateAccountAction;
use ControleOnline\Controller\CreateUserAction;
use ControleOnline\Controller\Oauth\GoogleConnectController;
use ControleOnline\Controller\Oauth\GoogleReturnController;
use ControleOnline\Controller\Oauth\MercadolivreReturnController;
use ControleOnline\Controller\SecurityController;
use ControleOnline\Entity\People;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * User
 *
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})  
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\UserRepository")
 * @ORM\Table (name="users", uniqueConstraints={@ORM\UniqueConstraint (name="user_name", columns={"username"}), @ORM\UniqueConstraint(name="api_key", columns={"api_key"})}, indexes={@ORM\Index (name="people_id", columns={"people_id"})})
 * @ORM\HasLifecycleCallbacks
 */



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
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     * @Groups({"people:read", "user:read"})
     */
    private $id;
    /**
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Groups({"people:read", "user:read"})
     */
    private $username;
    /**
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $hash;
    /**
     *
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $oauthUser;
    /**
     *
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $oauthHash;
    /**
     *
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    private $lostPassword;
    /**
     * @ORM\Column(type="string", length=60, nullable=false)
     * @Groups({"people:read", "user:read"})
     */
    private $apiKey;
    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People", inversedBy="user")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id", nullable=false)
     * })
     * @Groups({"people:read", "user:read"})
     */
    private $people;
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
    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string|null The encoded password if any
     */
    public function getPassword()
    {
        return $this->hash;
    }
    /**
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return string[] The user roles
     */
    public function getRoles()
    {
        return ['ROLE_CLIENT'];
    }
    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }
    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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
    public function setHash($hash): self
    {
        $this->hash = $hash;
        return $this;
    }
    public function getHash(): string
    {
        return $this->hash;
    }
    public function getOauthHash(): string
    {
        return $this->oauthHash;
    }
    public function setOauthHash($hash): self
    {
        $this->oauthHash = $hash;
        return $this;
    }
    public function setOauthUser($user): self
    {
        $this->oauthUser = $user;
        return $this;
    }
    public function getOauthUser(): string
    {
        return $this->oauthUser;
    }
    public function setPeople(People $people = null): self
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
