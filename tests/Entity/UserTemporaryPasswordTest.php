<?php

namespace ControleOnline\Tests\Entity;

require_once dirname(__DIR__, 2) . '/src/Entity/User.php';

use ControleOnline\Entity\User;
use ControleOnline\Service\PasswordRecoveryService;
use PHPUnit\Framework\TestCase;

class UserTemporaryPasswordTest extends TestCase
{
    public function testClearPasswordChangeRequirementResetsFlags(): void
    {
        $user = (new User())
            ->setMustChangePassword(true)
            ->setPasswordChangeDeadline(new \DateTimeImmutable('+15 minutes'));

        $user->clearPasswordChangeRequirement();

        self::assertFalse($user->isMustChangePassword());
        self::assertNull($user->getPasswordChangeDeadline());
        self::assertFalse($user->hasExpiredPasswordChangeDeadline());
    }

    public function testHasExpiredPasswordChangeDeadlineWhenPast(): void
    {
        $user = (new User())
            ->setMustChangePassword(true)
            ->setPasswordChangeDeadline(new \DateTimeImmutable('-1 minute'));

        self::assertTrue($user->hasExpiredPasswordChangeDeadline());
    }

    public function testTemporaryPasswordTtlIsFifteenMinutes(): void
    {
        self::assertSame(15, PasswordRecoveryService::TEMPORARY_PASSWORD_TTL_MINUTES);
    }
}
