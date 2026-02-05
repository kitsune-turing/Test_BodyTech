<?php

declare(strict_types=1);

namespace TaskService\Tests\Unit\Infrastructure\Http\Middleware;

use PHPUnit\Framework\TestCase;
use TaskService\Infrastructure\Http\Middleware\JwtAuthMiddleware;

final class JwtAuthMiddlewareTest extends TestCase
{
    private string $jwtSecret = 'test-secret-key-for-testing';
    private JwtAuthMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new JwtAuthMiddleware($this->jwtSecret);
    }

    private function createValidToken(int $userId, int $exp = null): string
    {
        $exp = $exp ?? (time() + 3600);

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'sub' => $userId,
            'iat' => time(),
            'exp' => $exp,
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $this->jwtSecret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function testHandleValidToken(): void
    {
        $token = $this->createValidToken(123);
        $authHeader = 'Bearer ' . $token;

        $result = $this->middleware->handle($authHeader);

        $this->assertTrue($result['success']);
        $this->assertEquals(123, $result['user_id']);
        $this->assertArrayHasKey('payload', $result);
        $this->assertEquals(123, $result['payload']['sub']);
    }

    public function testHandleMissingAuthHeader(): void
    {
        $result = $this->middleware->handle('');

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['status']);
        $this->assertEquals('UNAUTHORIZED', $result['error']['code']);
        $this->assertStringContainsString('Missing or invalid', $result['error']['message']);
    }

    public function testHandleInvalidAuthHeaderFormat(): void
    {
        $result = $this->middleware->handle('InvalidFormat token');

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['status']);
        $this->assertEquals('UNAUTHORIZED', $result['error']['code']);
    }

    public function testHandleMissingBearerPrefix(): void
    {
        $token = $this->createValidToken(123);
        $result = $this->middleware->handle($token);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['status']);
    }

    public function testHandleInvalidTokenFormat(): void
    {
        $authHeader = 'Bearer invalid.token';

        $result = $this->middleware->handle($authHeader);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['status']);
        $this->assertEquals('INVALID_TOKEN', $result['error']['code']);
    }

    public function testHandleTokenWithInvalidSignature(): void
    {
        $token = $this->createValidToken(123);
        $parts = explode('.', $token);
        $parts[2] = 'invalidsignature';
        $invalidToken = implode('.', $parts);

        $authHeader = 'Bearer ' . $invalidToken;
        $result = $this->middleware->handle($authHeader);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['status']);
        $this->assertEquals('INVALID_TOKEN', $result['error']['code']);
        $this->assertStringContainsString('Invalid token signature', $result['error']['message']);
    }

    public function testHandleExpiredToken(): void
    {
        $expiredToken = $this->createValidToken(123, time() - 3600);
        $authHeader = 'Bearer ' . $expiredToken;

        $result = $this->middleware->handle($authHeader);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['status']);
        $this->assertEquals('INVALID_TOKEN', $result['error']['code']);
        $this->assertStringContainsString('expired', $result['error']['message']);
    }

    public function testHandleTokenWithInvalidPayload(): void
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = 'invalid-json';

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $this->jwtSecret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        $token = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
        $authHeader = 'Bearer ' . $token;

        $result = $this->middleware->handle($authHeader);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['status']);
    }

    public function testHandleTokenWithoutExpiration(): void
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'sub' => 123,
            'iat' => time(),
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $this->jwtSecret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        $token = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
        $authHeader = 'Bearer ' . $token;

        $result = $this->middleware->handle($authHeader);

        $this->assertTrue($result['success']);
        $this->assertEquals(123, $result['user_id']);
    }

    public function testHandleMultipleValidTokens(): void
    {
        $token1 = $this->createValidToken(100);
        $token2 = $this->createValidToken(200);

        $result1 = $this->middleware->handle('Bearer ' . $token1);
        $result2 = $this->middleware->handle('Bearer ' . $token2);

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertEquals(100, $result1['user_id']);
        $this->assertEquals(200, $result2['user_id']);
    }
}
