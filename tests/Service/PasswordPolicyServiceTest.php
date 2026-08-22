<?php

namespace ControleOnline\Tests\Service;

require_once dirname(__DIR__, 2) . '/src/Service/PasswordPolicyService.php';

use ControleOnline\Service\PasswordPolicyService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PasswordPolicyServiceTest extends TestCase
{
    public function testRejectsEmptyPassword(): void
    {
        $service = new PasswordPolicyService();
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PasswordPolicyService::MSG_REQUIRED);
        $service->assertValid('   ');
    }

    public function testRejectsShortPasswordWithMinLengthMessage(): void
    {
        $service = new PasswordPolicyService();
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            sprintf(PasswordPolicyService::MSG_MIN_LENGTH, PasswordPolicyService::MIN_LENGTH)
        );
        $service->assertValid('123');
    }

    public function testAcceptsValidLengthWhenValidatorAbsent(): void
    {
        $service = new PasswordPolicyService(null);
        $service->assertValid('abcdef');
        $this->assertTrue(true);
    }

    public function testRejectsCompromisedPasswordWithPortugueseMessage(): void
    {
        $violation = new ConstraintViolation(
            'This password has been leaked in a data breach, it must not be used. Please use another password.',
            null,
            [],
            '123456',
            '',
            '123456'
        );
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $service = new PasswordPolicyService($validator);
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PasswordPolicyService::MSG_COMPROMISED);
        $service->assertValid('123456');
    }

    public function testMapErrorMessageTranslatesEnglishBreach(): void
    {
        $service = new PasswordPolicyService();
        $mapped = $service->mapErrorMessage(
            'This password has been leaked in a data breach, it must not be used. Please use another password.'
        );
        $this->assertSame(PasswordPolicyService::MSG_COMPROMISED, $mapped);
    }

    public function testHelpLinesExposeMinLength(): void
    {
        $service = new PasswordPolicyService();
        $lines = $service->getHelpLines();
        $this->assertNotEmpty($lines);
        $this->assertStringContainsString((string) PasswordPolicyService::MIN_LENGTH, $lines[0]);
    }
}
