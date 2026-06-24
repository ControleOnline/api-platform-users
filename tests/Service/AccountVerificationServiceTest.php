<?php

namespace ControleOnline\Tests\Service;

use App\Service\EmailService;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\AccountVerificationService;
use ControleOnline\Service\DomainService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class AccountVerificationServiceTest extends TestCase
{
    public function testSendVerificationEmailAndActivatePeopleFromCapturedLink(): void
    {
        $people = new People();
        $people->setName('Alexandre');
        $people->setAlias('Mac');

        $email = new Email();
        $email->setEmail('alemac@mac.com');
        $email->setPeople($people);
        $people->getEmail()->add($email);

        $user = new User();
        $user->setUsername('login-teste@example.com');
        $user->setPeople($people);

        $capturedMessage = null;

        $emailService = $this->getMockBuilder(EmailService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendMessage'])
            ->getMock();
        $emailService
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                'alemac@mac.com',
                'Confirme seu cadastro',
                self::callback(function (string $html) use (&$capturedMessage): bool {
                    $capturedMessage = $html;

                    return str_contains($html, 'confirm-account?hash=')
                        && str_contains($html, 'Olá')
                        && str_contains($html, 'ativação')
                        && str_contains($html, 'Se você não reconhece este cadastro');
                })
            );

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(self::callback(function (array $criteria) use ($user): bool {
                return ($criteria['oauthHash'] ?? null) === $user->getOauthHash()
                    && ($criteria['lostPassword'] ?? null) === $user->getLostPassword();
            }))
            ->willReturn($user);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);
        $manager->expects(self::exactly(3))->method('persist');
        $manager->expects(self::exactly(2))->method('flush');

        $domainService = $this->createMock(DomainService::class);
        $domainService
            ->method('getDomain')
            ->willReturn('https://app.lave-go.com');

        $service = new AccountVerificationService(
            $manager,
            $emailService,
            $domainService,
        );

        $service->sendVerification($user);

        self::assertNotNull($capturedMessage);
        self::assertStringContainsString('https://app.lave-go.com/confirm-account?', $capturedMessage);
        self::assertStringContainsString('Confirmar cadastro', $capturedMessage);
        preg_match('/href="([^"]+)"/', $capturedMessage, $matches);
        self::assertNotEmpty($matches[1] ?? null);

        $verificationUrl = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        $query = [];
        parse_str((string) parse_url($verificationUrl, PHP_URL_QUERY), $query);

        $service->completeVerificationFromContent(json_encode([
            'hash' => $query['hash'] ?? '',
            'token' => $query['token'] ?? '',
        ]));

        self::assertTrue((bool) $people->getEnabled());
        self::assertNull($user->getOauthHash());
        self::assertNull($user->getLostPassword());
    }
}
