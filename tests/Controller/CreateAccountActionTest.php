<?php

namespace ControleOnline\Users\Tests\Controller;

use ControleOnline\Controller\CreateAccountAction;
use ControleOnline\Service\AccountRegistrationService;
use ControleOnline\Service\HydratorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateAccountActionTest extends TestCase
{
    public function testReturnsValidationErrorWithoutMaskingItAsServerError(): void
    {
        $payload = json_encode([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret',
            'confirmPassword' => 'different',
        ], JSON_THROW_ON_ERROR);

        $service = $this->createMock(AccountRegistrationService::class);
        $service
            ->expects(self::once())
            ->method('registerFromContent')
            ->with($payload)
            ->willThrowException(new BadRequestHttpException('password confirmation does not match'));

        $hydrator = $this->getMockBuilder(HydratorService::class)->disableOriginalConstructor()->onlyMethods(['error'])->getMock();
        $hydrator->method('error')->willReturn(['error' => 'password confirmation does not match']);
        $action = new CreateAccountAction($service, $hydrator);
        $response = $action(new Request(content: $payload));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['error' => 'password confirmation does not match'], json_decode((string) $response->getContent(), true));
    }

    public function testReturnsStructuredSessionPayloadForSuccessfulSignup(): void
    {
        $payload = json_encode([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret',
            'confirmPassword' => 'secret',
        ], JSON_THROW_ON_ERROR);

        $service = $this->createMock(AccountRegistrationService::class);
        $service
            ->expects(self::once())
            ->method('registerFromContent')
            ->with($payload)
            ->willReturn(new \ControleOnline\Entity\People());

        $action = new CreateAccountAction($service, $this->getMockBuilder(HydratorService::class)->disableOriginalConstructor()->getMock());
        $response = $action(new Request(content: $payload));

        self::assertSame(202, $response->getStatusCode());
        self::assertSame(['success' => true, 'message' => 'Cadastro criado com sucesso. Confira seu e-mail para ativar a conta.'], json_decode((string) $response->getContent(), true));
    }
}
