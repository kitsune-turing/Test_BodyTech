<?php

declare(strict_types=1);

namespace AuthService\Tests\Integration\Infrastructure\Security;

use AuthService\Infrastructure\Security\FirebaseJwtProvider;
use PHPUnit\Framework\TestCase;

class FirebaseJwtProviderTest extends TestCase
{
    private FirebaseJwtProvider $jwtProvider;
    private string $secret = 'test-secret-key-minimum-32-characters-long-for-security';

    protected function setUp(): void
    {
        $this->jwtProvider = new FirebaseJwtProvider($this->secret, 3600);
    }

    public function test_generates_valid_jwt_token(): void
    {
        $userId = 123;

        $result = $this->jwtProvider->generate($userId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('jti', $result);
        $this->assertEquals(3600, $result['expires_in']);
    }

    public function test_token_contains_three_parts(): void
    {
        $result = $this->jwtProvider->generate(123);
        $token = $result['token'];

        $parts = explode('.', $token);

        $this->assertCount(3, $parts);
    }

    public function test_validates_own_generated_token(): void
    {
        $userId = 456;
        $result = $this->jwtProvider->generate($userId);
        $token = $result['token'];

        $payload = $this->jwtProvider->validate($token);

        $this->assertEquals($userId, $payload['sub']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('jti', $payload);
    }

    public function test_throws_exception_for_invalid_token(): void
    {
        $this->expectException(\Exception::class);

        $this->jwtProvider->validate('invalid.token.format');
    }

    public function test_throws_exception_for_tampered_token(): void
    {
        $result = $this->jwtProvider->generate(123);
        $token = $result['token'];

        $parts = explode('.', $token);
        $parts[1] = base64_encode('{"sub":999}');
        $tamperedToken = implode('.', $parts);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token signature');

        $this->jwtProvider->validate($tamperedToken);
    }

    public function test_extracts_jti_from_token(): void
    {
        $result = $this->jwtProvider->generate(123);
        $token = $result['token'];
        $expectedJti = $result['jti'];

        $extractedJti = $this->jwtProvider->extractJti($token);

        $this->assertEquals($expectedJti, $extractedJti);
    }

    public function test_returns_null_for_invalid_token_jti_extraction(): void
    {
        $jti = $this->jwtProvider->extractJti('invalid.token');

        $this->assertNull($jti);
    }

    public function test_throws_exception_for_short_secret(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FirebaseJwtProvider('short-secret');
    }
}
