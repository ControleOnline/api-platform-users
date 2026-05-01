<?php

namespace ControleOnline\Users\Tests\Controller;

use ControleOnline\Controller\DeleteUserAction;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\UserService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class DeleteUserActionTest extends TestCase
{
    public function testInvokeDelegatesDeletionToUserService(): void
    {
        $people = new People(7);
        $user = (new User())
            ->setId(11)
            ->setPeople($people);

        $service = $this->createMock(UserService::class);
        $service
            ->expects(self::once())
            ->method('deleteUser')
            ->with($people, 11)
            ->willReturn(true);

        $response = (new DeleteUserAction($service))->__invoke($user);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'response' => [
                'data' => [],
                'count' => 0,
                'error' => null,
                'success' => true,
            ],
        ], $response->getData(true));
    }
}
