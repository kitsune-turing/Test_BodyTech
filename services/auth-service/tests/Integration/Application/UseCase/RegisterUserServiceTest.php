<?php

declare(strict_types=1);

namespace AuthService\Tests\Integration\Application\UseCase;

use AuthService\Application\DTO\RegisterRequest;
use AuthService\Application\UseCase\RegisterUserService;
use AuthService\Domain\Repository\UserRepositoryInterface;
use AuthService\Domain\Validator\UserValidator;
use AuthService\Infrastructure\Security\Argon2PasswordHasher;
use PHPUnit\Framework\TestCase;

class RegisterUserServiceTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private Argon2PasswordHasher $passwordHasher;
    private UserValidator $validator;
    private RegisterUserService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = new Argon2PasswordHasher();
        $this->validator = new UserValidator();

        $this->service = new RegisterUserService(
            $this->userRepository,
            $this->passwordHasher,
            $this->validator
        );
    }

    public function test_registers_user_with_valid_data(): void
    {
        $request = new RegisterRequest('test@example.com', 'SecureP@ss123');

        $this->userRepository
            ->expects($this->once())
            ->method('emailExists')
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($user) {
                $this->assertEquals('test@example.com', $user->getEmail()->getValue());
                return $user;
            });

        $user = $this->service->execute($request);

        $this->assertEquals('test@example.com', $user->getEmail()->getValue());
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $request = new RegisterRequest('invalid-email', 'SecureP@ss123');

        $this->expectException(\Exception::class);

        $this->service->execute($request);
    }

    public function test_throws_exception_for_weak_password(): void
    {
        $request = new RegisterRequest('test@example.com', 'weak');

        $this->expectException(\Exception::class);

        $this->service->execute($request);
    }

    public function test_throws_exception_for_existing_email(): void
    {
        $request = new RegisterRequest('existing@example.com', 'SecureP@ss123');

        $this->userRepository
            ->expects($this->once())
            ->method('emailExists')
            ->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/EMAIL_EXISTS/');

        $this->service->execute($request);
    }

    public function test_hashes_password_with_argon2id(): void
    {
        $request = new RegisterRequest('test@example.com', 'SecureP@ss123');

        $this->userRepository
            ->expects($this->once())
            ->method('emailExists')
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($user) {
                $hash = $user->getPasswordHash()->getHash();
                $this->assertStringStartsWith('$argon2id$', $hash);
                $this->assertNotEquals('SecureP@ss123', $hash);
                return $user;
            });

        $this->service->execute($request);
    }
}
