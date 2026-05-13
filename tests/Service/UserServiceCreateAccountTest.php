<?php

namespace ControleOnline\Users\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\FileService;
use ControleOnline\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceCreateAccountTest extends TestCase
{
    public function testRequiresNameEmailAndPassword(): void
    {
        $service = $this->createService();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('name, email and password are required');

        $service->createAccountSessionFromContent(json_encode([
            'email' => 'maria@example.com',
            'password' => 'secret',
        ], JSON_THROW_ON_ERROR));
    }

    public function testRejectsDifferentPasswordConfirmation(): void
    {
        $service = $this->createService();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('password confirmation does not match');

        $service->createAccountSessionFromContent(json_encode([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret',
            'confirmPassword' => 'different',
        ], JSON_THROW_ON_ERROR));
    }

    public function testBuildsSessionUsingSplitNameAndUserCreationFlow(): void
    {
        $people = new People();
        $user = $this->createMock(User::class);

        $service = $this->createPartialMockedService();
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

        self::assertSame([
            'id' => 15,
            'username' => 'maria@example.com',
            'api_key' => 'abc123',
        ], $service->createAccountSessionFromContent(json_encode([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'secret',
            'confirmPassword' => 'secret',
        ], JSON_THROW_ON_ERROR)));
    }

    private function createService(): UserService
    {
        return new UserService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
            $this->createMock(FileService::class)
        );
    }

    private function createPartialMockedService(): UserService
    {
        return $this->getMockBuilder(UserService::class)
            ->setConstructorArgs([
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(UserPasswordHasherInterface::class),
                $this->createMock(FileService::class),
            ])
            ->onlyMethods(['discoveryPeople', 'createUser', 'getUserSession'])
            ->getMock();
    }
}
