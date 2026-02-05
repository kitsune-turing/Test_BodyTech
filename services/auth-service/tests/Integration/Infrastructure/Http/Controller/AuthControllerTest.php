<?php

declare(strict_types=1);

namespace AuthService\Tests\Integration\Infrastructure\Http\Controller;

use PHPUnit\Framework\TestCase;
use AuthService\Infrastructure\Http\Controller\AuthController;
use AuthService\Application\UseCase\RegisterUserService;
use AuthService\Application\UseCase\AuthenticateUserService;
use AuthService\Application\UseCase\RevokeTokenService;
use AuthService\Infrastructure\Cache\RedisCacheManager;

final class AuthControllerTest extends TestCase
{
    private AuthController $controller;
    private $registerService;
    private $authenticateService;
    private $revokeTokenService;
    private $cacheManager;

    protected function setUp(): void
    {
        $this->registerService = $this->createMock(RegisterUserService::class);
        $this->authenticateService = $this->createMock(AuthenticateUserService::class);
        $this->revokeTokenService = $this->createMock(RevokeTokenService::class);
        $this->cacheManager = $this->createMock(RedisCacheManager::class);

        $this->controller = new AuthController(
            $this->registerService,
            $this->authenticateService,
            $this->revokeTokenService,
            $this->cacheManager
        );
    }

    public function testRegisterSuccessfully(): void
    {
        $requestData = [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123',
            'name' => 'Test User',
        ];

        $expectedUser = [
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00',
        ];

        $this->registerService
            ->expects($this->once())
            ->method('execute')
            ->with($requestData)
            ->willReturn($expectedUser);

        $result = $this->controller->register($requestData);

        $this->assertEquals(201, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($expectedUser, $result['data']);
    }

    public function testRegisterWithValidationError(): void
    {
        $requestData = [
            'email' => 'invalid-email',
            'password' => '123',
        ];

        $this->registerService
            ->expects($this->once())
            ->method('execute')
            ->with($requestData)
            ->willThrowException(new \Exception(json_encode([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => ['email' => 'Invalid email format'],
            ])));

        $result = $this->controller->register($requestData);

        $this->assertEquals(400, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('error', $result['data']);
    }

    public function testRegisterWithDuplicateEmail(): void
    {
        $requestData = [
            'email' => 'existing@example.com',
            'password' => 'SecurePassword123',
        ];

        $this->registerService
            ->expects($this->once())
            ->method('execute')
            ->with($requestData)
            ->willThrowException(new \Exception(json_encode([
                'code' => 'DUPLICATE_EMAIL',
                'message' => 'Email already exists',
            ])));

        $result = $this->controller->register($requestData);

        $this->assertEquals(400, $result['status']);
    }

    public function testLoginSuccessfully(): void
    {
        $requestData = [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123',
        ];

        $expectedResponse = [
            'token' => 'jwt.token.here',
            'user' => [
                'id' => 1,
                'email' => 'test@example.com',
                'name' => 'Test User',
            ],
        ];

        $this->authenticateService
            ->expects($this->once())
            ->method('execute')
            ->with($requestData['email'], $requestData['password'])
            ->willReturn($expectedResponse);

        $result = $this->controller->login($requestData);

        $this->assertEquals(200, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($expectedResponse, $result['data']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $requestData = [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ];

        $this->authenticateService
            ->expects($this->once())
            ->method('execute')
            ->with($requestData['email'], $requestData['password'])
            ->willThrowException(new \Exception(json_encode([
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid email or password',
            ])));

        $result = $this->controller->login($requestData);

        $this->assertEquals(400, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('error', $result['data']);
    }

    public function testLogoutSuccessfully(): void
    {
        $token = 'valid.jwt.token';

        $this->revokeTokenService
            ->expects($this->once())
            ->method('execute')
            ->with($token);

        $result = $this->controller->logout($token);

        $this->assertEquals(200, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('message', $result['data']);
    }

    public function testLogoutWithInvalidToken(): void
    {
        $token = 'invalid.token';

        $this->revokeTokenService
            ->expects($this->once())
            ->method('execute')
            ->with($token)
            ->willThrowException(new \Exception('Invalid token'));

        $result = $this->controller->logout($token);

        $this->assertEquals(500, $result['status']);
    }
}
