<?php

namespace ControleOnline\Users\Tests\Service;

use App\Service\EmailService;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\PasswordRecovery;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\PasswordRecoveryService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PasswordRecoveryServiceTest extends TestCase
{
    public function testRequestRecoveryStoresShortLivedTokensAndSendsEmail(): void
    {
        $payload = new PasswordRecovery();
        $payload->username = 'maria@example.com';

        $people = (new People('Maria Silva'))->setEmails([
            new Email('maria@example.com'),
        ]);
        $user = new User('maria@example.com', $people);

        $userRepository = new class($user) {
            public function __construct(private User $user)
            {
            }

            public function findOneBy(array $criteria): ?User
            {
                return ($criteria['username'] ?? null) === 'maria@example.com'
                    ? $this->user
                    : null;
            }
        };

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);
        $manager->expects(self::once())->method('persist')->with($user);
        $manager->expects(self::once())->method('flush');

        $userService = new class extends UserService {
            public function changePassword($user, $password)
            {
                TestCase::fail('requestRecovery must not change the password before the user confirms the flow.');
            }
        };

        $emails = new \ArrayObject();
        $emailService = new class($emails) extends EmailService {
            public function __construct(private \ArrayObject $emails)
            {
            }

            public function sendMessage(string $recipient, string $subject, string $body): void
            {
                $this->emails->append(compact('recipient', 'subject', 'body'));
            }
        };

        $domainService = new class extends DomainService {
            public function getDomain()
            {
                return 'https://admin.controleonline.com';
            }
        };

        $service = new PasswordRecoveryService(
            $manager,
            $emailService,
            $userService,
            $domainService,
        );

        $service->requestRecovery($payload);

        self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/', (string) $user->getOauthHash());
        self::assertMatchesRegularExpression('/^\d{10}\.[a-f0-9]{32}$/', (string) $user->getLostPassword());

        self::assertCount(1, $emails);
        self::assertSame('maria@example.com', $emails[0]['recipient']);
        self::assertSame('Recuperacao de senha', $emails[0]['subject']);
        self::assertStringNotContainsString('senha temporaria', strtolower($emails[0]['body']));
        self::assertStringContainsString(
            sprintf(
                'https://admin.controleonline.com/reset-password?hash=%s&amp;lost=%s',
                htmlspecialchars((string) $user->getOauthHash(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $user->getLostPassword(), ENT_QUOTES, 'UTF-8')
            ),
            $emails[0]['body']
        );
    }

    public function testRequestRecoveryStopsQuietlyWhenNoUserMatches(): void
    {
        $payload = new PasswordRecovery();
        $payload->username = 'ninguem@example.com';

        $userRepository = new class {
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
            ->willReturn($userRepository);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $userService = new class extends UserService {
            public function changePassword($user, $password)
            {
                TestCase::fail('changePassword should not be called without a matching user.');
            }
        };

        $emailService = new class extends EmailService {
            public function sendMessage(string $recipient, string $subject, string $body): void
            {
                TestCase::fail('sendMessage should not be called without a matching user.');
            }
        };

        $service = new PasswordRecoveryService(
            $manager,
            $emailService,
            $userService,
            new DomainService(),
        );

        $service->requestRecovery($payload);

        self::assertTrue(true);
    }

    public function testCompleteRecoveryChangesPasswordAndClearsRecoveryTokens(): void
    {
        $payload = new \ControleOnline\Entity\RecoveryAccess();
        $payload->hash = 'hash-token';
        $payload->lost = (time() + 900) . '.abcdef0123456789abcdef0123456789';
        $payload->password = 'novaSenha123';

        $people = (new People('Maria Silva'))->setEmails([
            new Email('maria@example.com'),
        ]);
        $user = (new User('maria@example.com', $people))
            ->setOauthHash('hash-token')
            ->setLostPassword($payload->lost);

        $userRepository = new class($user) {
            public function __construct(private User $user)
            {
            }

            public function findOneBy(array $criteria): ?User
            {
                if (
                    ($criteria['oauthHash'] ?? null) === $this->user->getOauthHash()
                    && ($criteria['lostPassword'] ?? null) === $this->user->getLostPassword()
                ) {
                    return $this->user;
                }

                return null;
            }
        };

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);
        $manager->expects(self::once())->method('persist')->with($user);
        $manager->expects(self::once())->method('flush');

        $passwordChanges = new \ArrayObject();
        $userService = new class($passwordChanges) extends UserService {
            public function __construct(private \ArrayObject $passwordChanges)
            {
            }

            public function changePassword($user, $password)
            {
                $this->passwordChanges->append([$user, $password]);
                return $user;
            }
        };

        $service = new PasswordRecoveryService(
            $manager,
            new EmailService(),
            $userService,
            new DomainService(),
        );

        $service->completeRecovery($payload);

        self::assertCount(1, $passwordChanges);
        self::assertSame('novaSenha123', $passwordChanges[0][1]);
        self::assertNull($user->getOauthHash());
        self::assertNull($user->getLostPassword());
    }

    public function testCompleteRecoveryRejectsExpiredTokensAndClearsRecoveryState(): void
    {
        $payload = new \ControleOnline\Entity\RecoveryAccess();
        $payload->hash = 'hash-token';
        $payload->lost = (time() - 5) . '.abcdef0123456789abcdef0123456789';
        $payload->password = 'novaSenha123';

        $people = (new People('Maria Silva'))->setEmails([
            new Email('maria@example.com'),
        ]);
        $user = (new User('maria@example.com', $people))
            ->setOauthHash('hash-token')
            ->setLostPassword($payload->lost);

        $userRepository = new class($user) {
            public function __construct(private User $user)
            {
            }

            public function findOneBy(array $criteria): ?User
            {
                if (
                    ($criteria['oauthHash'] ?? null) === $this->user->getOauthHash()
                    && ($criteria['lostPassword'] ?? null) === $this->user->getLostPassword()
                ) {
                    return $this->user;
                }

                return null;
            }
        };

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);
        $manager->expects(self::once())->method('persist')->with($user);
        $manager->expects(self::once())->method('flush');

        $userService = new class extends UserService {
            public function changePassword($user, $password)
            {
                TestCase::fail('completeRecovery must not change the password when the recovery token is expired.');
            }
        };

        $service = new PasswordRecoveryService(
            $manager,
            new EmailService(),
            $userService,
            new DomainService(),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solicitacao de recuperacao invalida ou expirada.');

        try {
            $service->completeRecovery($payload);
        } finally {
            self::assertNull($user->getOauthHash());
            self::assertNull($user->getLostPassword());
        }
    }
}
