<?php

declare(strict_types=1);

namespace AuthService\Tests\E2E;

use PHPUnit\Framework\TestCase;

class AuthFlowTest extends TestCase
{
    private string $baseUrl = 'http://localhost:8001';
    private static ?int $testUserId = null;

    protected function setUp(): void
    {
        if (!$this->isServiceAvailable()) {
            $this->markTestSkipped('Auth Service is not available at ' . $this->baseUrl);
        }
    }

    private function isServiceAvailable(): bool
    {
        $ch = curl_init($this->baseUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    private function makeRequest(string $method, string $path, ?array $data = null, ?string $token = null): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = ['Content-Type: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => json_decode($response, true)
        ];
    }

    public function test_complete_authentication_flow(): void
    {
        $uniqueEmail = 'e2e_test_' . time() . '@example.com';
        $password = 'SecureP@ss123';

        $registerResponse = $this->makeRequest('POST', '/v1/api/register', [
            'email' => $uniqueEmail,
            'password' => $password
        ]);

        $this->assertEquals(201, $registerResponse['status'], 'Registration should return 201');
        $this->assertArrayHasKey('id', $registerResponse['body']);
        $this->assertArrayHasKey('email', $registerResponse['body']);
        $this->assertEquals($uniqueEmail, $registerResponse['body']['email']);

        $userId = $registerResponse['body']['id'];
        self::$testUserId = $userId;

        $loginResponse = $this->makeRequest('POST', '/v1/api/login', [
            'email' => $uniqueEmail,
            'password' => $password
        ]);

        $this->assertEquals(200, $loginResponse['status'], 'Login should return 200');
        $this->assertArrayHasKey('token', $loginResponse['body']);
        $this->assertArrayHasKey('expires_in', $loginResponse['body']);
        $this->assertIsString($loginResponse['body']['token']);
        $this->assertEquals(3600, $loginResponse['body']['expires_in']);

        $token = $loginResponse['body']['token'];

        $tokenParts = explode('.', $token);
        $this->assertCount(3, $tokenParts, 'JWT should have 3 parts');

        $logoutResponse = $this->makeRequest('POST', '/v1/api/logout', null, $token);
        $this->assertEquals(204, $logoutResponse['status'], 'Logout should return 204');

        $logoutAgainResponse = $this->makeRequest('POST', '/v1/api/logout', null, $token);
        $this->assertEquals(401, $logoutAgainResponse['status'], 'Using revoked token should return 401');
    }

    public function test_register_with_invalid_email(): void
    {
        $response = $this->makeRequest('POST', '/v1/api/register', [
            'email' => 'invalid-email',
            'password' => 'SecureP@ss123'
        ]);

        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('VALIDATION_ERROR', $response['body']['error']['code']);
    }

    public function test_register_with_weak_password(): void
    {
        $response = $this->makeRequest('POST', '/v1/api/register', [
            'email' => 'test_weak_' . time() . '@example.com',
            'password' => 'weak'
        ]);

        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('VALIDATION_ERROR', $response['body']['error']['code']);
    }

    public function test_register_with_duplicate_email(): void
    {
        $uniqueEmail = 'e2e_duplicate_' . time() . '@example.com';
        $password = 'SecureP@ss123';

        $firstResponse = $this->makeRequest('POST', '/v1/api/register', [
            'email' => $uniqueEmail,
            'password' => $password
        ]);
        $this->assertEquals(201, $firstResponse['status']);

        $secondResponse = $this->makeRequest('POST', '/v1/api/register', [
            'email' => $uniqueEmail,
            'password' => $password
        ]);

        $this->assertEquals(409, $secondResponse['status']);
        $this->assertArrayHasKey('error', $secondResponse['body']);
        $this->assertEquals('EMAIL_EXISTS', $secondResponse['body']['error']['code']);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->makeRequest('POST', '/v1/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'WrongP@ss123'
        ]);

        $this->assertEquals(401, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('INVALID_CREDENTIALS', $response['body']['error']['code']);
    }

    public function test_logout_without_token(): void
    {
        $response = $this->makeRequest('POST', '/v1/api/logout', null, null);

        $this->assertEquals(401, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('UNAUTHORIZED', $response['body']['error']['code']);
    }

    public function test_logout_with_invalid_token(): void
    {
        $response = $this->makeRequest('POST', '/v1/api/logout', null, 'invalid.token.here');

        $this->assertEquals(401, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('INVALID_TOKEN', $response['body']['error']['code']);
    }

    public function test_health_check(): void
    {
        $response = $this->makeRequest('GET', '/health');

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('status', $response['body']);
        $this->assertEquals('healthy', $response['body']['status']);
    }
}
