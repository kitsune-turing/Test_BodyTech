<?php

declare(strict_types=1);

namespace AuthService\Tests\Unit\Application\DTO;

use AuthService\Application\DTO\LoginRequest;
use PHPUnit\Framework\TestCase;

final class LoginRequestTest extends TestCase
{
    public function testFromArrayCreatesValidLoginRequest(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123',
        ];

        $loginRequest = LoginRequest::fromArray($data);

        $this->assertInstanceOf(LoginRequest::class, $loginRequest);
        $this->assertEquals('test@example.com', $loginRequest->getEmail());
        $this->assertEquals('SecurePassword123', $loginRequest->getPassword());
    }

    public function testFromArrayThrowsExceptionWhenEmailMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is required');

        LoginRequest::fromArray(['password' => 'test123']);
    }

    public function testFromArrayThrowsExceptionWhenPasswordMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password is required');

        LoginRequest::fromArray(['email' => 'test@example.com']);
    }

    public function testFromArrayThrowsExceptionWhenEmailEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is required');

        LoginRequest::fromArray([
            'email' => '',
            'password' => 'test123',
        ]);
    }

    public function testFromArrayThrowsExceptionWhenPasswordEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password is required');

        LoginRequest::fromArray([
            'email' => 'test@example.com',
            'password' => '',
        ]);
    }

    public function testFromArrayTrimsEmail(): void
    {
        $data = [
            'email' => '  test@example.com  ',
            'password' => 'SecurePassword123',
        ];

        $loginRequest = LoginRequest::fromArray($data);

        $this->assertEquals('test@example.com', $loginRequest->getEmail());
    }

    public function testValidateEmailFormat(): void
    {
        $data = [
            'email' => 'invalid-email',
            'password' => 'SecurePassword123',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        LoginRequest::fromArray($data);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123',
        ];

        $loginRequest = LoginRequest::fromArray($data);
        $result = $loginRequest->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('password', $result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('SecurePassword123', $result['password']);
    }
}
