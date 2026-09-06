<?php

namespace ControleOnline\Users\Tests\Controller;

use ControleOnline\Controller\DeleteUserAction;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\UserService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DeleteUserActionTest extends TestCase
{
    public function testInvokeDelegatesDeletionToUserService(): void
    {
        $people = new People();

        $service = $this->createMock(UserService::class);
        $service
            ->expects(self::once())
            ->method('deleteUserFromContent')
            ->with($people, '{"id":11}')
            ->willReturn(true);

        $response = (new DeleteUserAction($service))->__invoke($people, new Request(content: json_encode(['id' => 11])));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'response' => [
                'data' => true,
                'count' => 1,
                'error' => '',
                'success' => true,
            ],
        ], json_decode((string) $response->getContent(), true));
    }
}
