<?php

namespace ControleOnline\Users\Tests\Controller;

use ControleOnline\Controller\CreateAccountAction;
use ControleOnline\Entity\User;
use ControleOnline\Service\UserService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CreateAccountActionTest extends TestCase
{
    public function testReturnsValidationErrorWithoutMaskingItAsServerError(): void
    {
        $service = $this->createMock(UserService::class);
        $service->expects(self::never())->method('discoveryPeople');
        $service->expects(self::never())->method('createUser');
        $service->expects(self::never())->method('getUserSession');

        $action = new CreateAccountAction($service);
        $response = $action(new Request(content: json_encode([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret',
            'confirmPassword' => 'different',
        ], JSON_THROW_ON_ERROR)));

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
        $people = new \stdClass();
        $user = $this->createMock(User::class);

        $service = $this->createMock(UserService::class);
        $service
            ->expects(self::once())
            ->method('discoveryPeople')
            ->with('maria@example.com', 'Maria', 'Silva')
            ->willReturn($people);
        $service
            ->expects(self::once())
            ->method('createUser')
            ->with($people, 'maria@example.com', 'secret')
            ->willReturn($user);
        $service
            ->expects(self::once())
            ->method('getUserSession')
            ->with($user)
            ->willReturn([
                'id' => 15,
                'username' => 'maria@example.com',
                'api_key' => 'abc123',
            ]);

        $action = new CreateAccountAction($service);
        $response = $action(new Request(content: json_encode([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret',
            'confirmPassword' => 'secret',
        ], JSON_THROW_ON_ERROR)));

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
