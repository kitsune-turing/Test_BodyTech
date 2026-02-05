<?php

declare(strict_types=1);

/**
 * Complete System End-to-End Test
 *
 * This test verifies the complete flow of the microservices system:
 * 1. Auth Service: Register and Login
 * 2. Task Service: Create and Update tasks
 * 3. Realtime Service: WebSocket connection (manual verification required)
 *
 * Note: WebSocket testing requires manual verification using the HTML client
 * in docs/examples/websocket-client.html
 */

namespace Tests\E2E;

use PHPUnit\Framework\TestCase;

class CompleteSystemFlowTest extends TestCase
{
    private string $authServiceUrl = 'http://localhost:8001';
    private string $taskServiceUrl = 'http://localhost:8002';
    private string $realtimeServiceUrl = 'ws://localhost:9501';

    protected function setUp(): void
    {
        $this->checkServicesAvailability();
    }

    private function checkServicesAvailability(): void
    {
        $services = [
            'Auth Service' => $this->authServiceUrl,
            'Task Service' => $this->taskServiceUrl,
        ];

        foreach ($services as $name => $url) {
            if (!$this->isServiceAvailable($url)) {
                $this->markTestSkipped("$name is not available at $url");
            }
        }
    }

    private function isServiceAvailable(string $baseUrl): bool
    {
        $ch = curl_init($baseUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    private function makeRequest(string $method, string $url, ?array $data = null, ?string $token = null): array
    {
        $ch = curl_init($url);
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

    public function test_complete_system_flow(): void
    {
        $uniqueEmail = 'e2e_complete_test_' . time() . '@example.com';
        $password = 'CompleteP@ss123';

        $registerResponse = $this->makeRequest(
            'POST',
            $this->authServiceUrl . '/v1/api/register',
            [
                'email' => $uniqueEmail,
                'password' => $password
            ]
        );

        $this->assertEquals(201, $registerResponse['status'], 'User registration should succeed');
        $this->assertArrayHasKey('id', $registerResponse['body']);
        $this->assertArrayHasKey('email', $registerResponse['body']);
        $userId = $registerResponse['body']['id'];

        $loginResponse = $this->makeRequest(
            'POST',
            $this->authServiceUrl . '/v1/api/login',
            [
                'email' => $uniqueEmail,
                'password' => $password
            ]
        );

        $this->assertEquals(200, $loginResponse['status'], 'Login should succeed');
        $this->assertArrayHasKey('token', $loginResponse['body']);
        $this->assertArrayHasKey('expires_in', $loginResponse['body']);
        $token = $loginResponse['body']['token'];

        $tokenParts = explode('.', $token);
        $this->assertCount(3, $tokenParts, 'JWT should have 3 parts (header.payload.signature)');

        $header = json_decode(base64_decode(strtr($tokenParts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);

        $this->assertEquals('JWT', $header['typ']);
        $this->assertEquals('HS256', $header['alg']);
        $this->assertEquals($userId, $payload['sub']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('jti', $payload);
        echo "[E2E TEST] JWT token structure is valid\n";

        $createTaskResponse = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            [
                'title' => 'E2E Complete Test Task',
                'description' => 'This task tests the complete system flow',
                'status' => 'pending'
            ],
            $token
        );

        $this->assertEquals(201, $createTaskResponse['status'], 'Task creation should succeed');
        $this->assertArrayHasKey('id', $createTaskResponse['body']);
        $this->assertEquals('E2E Complete Test Task', $createTaskResponse['body']['title']);
        $this->assertEquals('pending', $createTaskResponse['body']['status']);
        $taskId = $createTaskResponse['body']['id'];

        $getTaskResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks/' . $taskId,
            null,
            $token
        );

        $this->assertEquals(200, $getTaskResponse['status']);
        $this->assertEquals($taskId, $getTaskResponse['body']['id']);
        $this->assertEquals('E2E Complete Test Task', $getTaskResponse['body']['title']);

        $updateTaskResponse = $this->makeRequest(
            'PUT',
            $this->taskServiceUrl . '/v1/api/tasks/' . $taskId,
            [
                'title' => 'E2E Complete Test Task - UPDATED',
                'description' => 'Updated description for complete flow test',
                'status' => 'in_progress'
            ],
            $token
        );

        $this->assertEquals(200, $updateTaskResponse['status'], 'Task update should succeed');
        $this->assertEquals('E2E Complete Test Task - UPDATED', $updateTaskResponse['body']['title']);
        $this->assertEquals('in_progress', $updateTaskResponse['body']['status']);

        $listTasksResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks',
            null,
            $token
        );

        $this->assertEquals(200, $listTasksResponse['status']);
        $this->assertIsArray($listTasksResponse['body']);
        $this->assertGreaterThan(0, count($listTasksResponse['body']));

        $foundTask = false;
        foreach ($listTasksResponse['body'] as $task) {
            if ($task['id'] === $taskId) {
                $foundTask = true;
                $this->assertEquals('in_progress', $task['status']);
                break;
            }
        }
        $this->assertTrue($foundTask, 'Updated task should be in the list');

        echo "[E2E TEST] Step 8: Filtering tasks by status...\n";
        $filterTasksResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks?status=in_progress',
            null,
            $token
        );

        $this->assertEquals(200, $filterTasksResponse['status']);
        foreach ($filterTasksResponse['body'] as $task) {
            $this->assertEquals('in_progress', $task['status']);
        }

        $completeTaskResponse = $this->makeRequest(
            'PUT',
            $this->taskServiceUrl . '/v1/api/tasks/' . $taskId,
            [
                'status' => 'done'
            ],
            $token
        );

        $this->assertEquals(200, $completeTaskResponse['status']);
        $this->assertEquals('done', $completeTaskResponse['body']['status']);

        $logoutResponse = $this->makeRequest(
            'POST',
            $this->authServiceUrl . '/v1/api/logout',
            null,
            $token
        );

        $this->assertEquals(204, $logoutResponse['status'], 'Logout should succeed');
        echo "[E2E TEST] Logout successful, token revoked\n";

        $afterLogoutResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks',
            null,
            $token
        );

        $this->assertEquals(401, $afterLogoutResponse['status'], 'Using revoked token should fail');
    }

    public function test_service_health_checks(): void
    {
        $authHealth = $this->makeRequest('GET', $this->authServiceUrl . '/health');
        $this->assertEquals(200, $authHealth['status']);
        $this->assertEquals('healthy', $authHealth['body']['status']);

        $taskHealth = $this->makeRequest('GET', $this->taskServiceUrl . '/health');
        $this->assertEquals(200, $taskHealth['status']);
        $this->assertEquals('healthy', $taskHealth['body']['status']);

    }

    public function test_cross_service_integration(): void
    {
        $email = 'e2e_integration_' . time() . '@example.com';
        $password = 'IntegrationP@ss123';

        $registerResponse = $this->makeRequest(
            'POST',
            $this->authServiceUrl . '/v1/api/register',
            ['email' => $email, 'password' => $password]
        );
        $this->assertEquals(201, $registerResponse['status']);

        $loginResponse = $this->makeRequest(
            'POST',
            $this->authServiceUrl . '/v1/api/login',
            ['email' => $email, 'password' => $password]
        );
        $token = $loginResponse['body']['token'];

        $createTaskResponse = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            ['title' => 'Integration Test Task', 'status' => 'pending'],
            $token
        );
        $this->assertEquals(201, $createTaskResponse['status']);

        $logoutResponse = $this->makeRequest(
            'POST',
            $this->authServiceUrl . '/v1/api/logout',
            null,
            $token
        );
        $this->assertEquals(204, $logoutResponse['status']);

        $afterRevokeResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks',
            null,
            $token
        );
        $this->assertEquals(401, $afterRevokeResponse['status']);
    }
}
