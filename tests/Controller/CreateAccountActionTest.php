<?php

namespace ControleOnline\Users\Tests\Controller;

use ControleOnline\Controller\CreateAccountAction;
use ControleOnline\Service\CreateAccountResponseFactory;
use ControleOnline\Service\UserService;
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

        $service = $this->createMock(UserService::class);
        $service
            ->expects(self::once())
            ->method('createAccountSessionFromContent')
            ->with($payload)
            ->willThrowException(new BadRequestHttpException('password confirmation does not match'));

        $action = new CreateAccountAction($service, new CreateAccountResponseFactory());
        $response = $action(new Request(content: $payload));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame([
            'response' => [
                'data' => [],
                'count' => 0,
                'error' => 'password confirmation does not match',
                'success' => false,
            ],
        ], json_decode((string) $response->getContent(), true));
    }

    public function testReturnsStructuredSessionPayloadForSuccessfulSignup(): void
    {
        $payload = json_encode([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret',
            'confirmPassword' => 'secret',
        ], JSON_THROW_ON_ERROR);

        $service = $this->createMock(UserService::class);
        $service
            ->expects(self::once())
            ->method('createAccountSessionFromContent')
            ->with($payload)
            ->willReturn([
                'id' => 15,
                'username' => 'maria@example.com',
                'api_key' => 'abc123',
            ]);

        $action = new CreateAccountAction($service, new CreateAccountResponseFactory());
        $response = $action(new Request(content: $payload));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'response' => [
                'data' => [
                    'id' => 15,
                    'username' => 'maria@example.com',
                    'api_key' => 'abc123',
                ],
                'count' => 1,
                'error' => '',
                'success' => true,
            ],
        ], json_decode((string) $response->getContent(), true));
    }
}
