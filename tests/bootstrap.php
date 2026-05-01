<?php

namespace App\Service {
    class EmailService
    {
        public function sendMessage(string $recipient, string $subject, string $body): void
        {
        }
    }
}

namespace Doctrine\ORM {
    interface EntityManagerInterface
    {
        public function getRepository(string $class);

        public function persist(object $object): void;

        public function flush(): void;
    }
}

namespace Symfony\Component\Validator\Validator {
    interface ValidatorInterface
    {
        public function validate(mixed $value, mixed $constraints = null, mixed $groups = null);
    }
}

namespace ControleOnline\Service {
    class UserService
    {
        public function changePassword($user, $password)
        {
            return $user;
        }
    }

    class DomainService
    {
        public function getDomain()
        {
            return 'admin.controleonline.com';
        }
    }
}

namespace ControleOnline\Entity {
    class PasswordRecovery
    {
        public ?string $username = null;
        public ?string $email = null;
    }

    class RecoveryAccess
    {
        public ?string $hash = null;
        public ?string $lost = null;
        public ?string $password = null;
        public ?string $confirm = null;
    }

    class Email
    {
        public function __construct(
            private string $email = '',
            private mixed $people = null
        ) {
        }

        public function getEmail(): string
        {
            return $this->email;
        }

        public function getPeople(): mixed
        {
            return $this->people;
        }
    }

    class People
    {
        private array $emails = [];

        public function __construct(
            private string $fullName = ''
        ) {
        }

        public function setEmails(array $emails): self
        {
            $this->emails = $emails;
            return $this;
        }

        public function getFullName(): string
        {
            return $this->fullName;
        }

        public function getEmail(): array
        {
            return $this->emails;
        }

        public function getOneEmail(): ?Email
        {
            return $this->emails[0] ?? null;
        }
    }

    class User
    {
        private ?string $oauthHash = null;
        private ?string $lostPassword = null;

        public function __construct(
            private string $username = '',
            private ?People $people = null
        ) {
            $this->people ??= new People($username);
        }

        public function getUsername(): string
        {
            return $this->username;
        }

        public function setOauthHash(?string $hash): self
        {
            $this->oauthHash = $hash;
            return $this;
        }

        public function getOauthHash(): ?string
        {
            return $this->oauthHash;
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

        public function getPeople(): People
        {
            return $this->people;
        }
    }
}

namespace {
    require_once __DIR__ . '/../src/Service/PasswordRecoveryService.php';
}
