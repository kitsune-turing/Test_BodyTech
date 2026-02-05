<?php

declare(strict_types=1);

namespace TaskService\Tests\E2E;

use PHPUnit\Framework\TestCase;

class TaskFlowTest extends TestCase
{
    private string $authServiceUrl = 'http://localhost:8001';
    private string $taskServiceUrl = 'http://localhost:8002';
    private ?string $testToken = null;
    private ?string $testEmail = null;

    protected function setUp(): void
    {
        if (!$this->isServiceAvailable($this->authServiceUrl)) {
            $this->markTestSkipped('Auth Service is not available at ' . $this->authServiceUrl);
        }

        if (!$this->isServiceAvailable($this->taskServiceUrl)) {
            $this->markTestSkipped('Task Service is not available at ' . $this->taskServiceUrl);
        }

        // Crea un usuario de prueba y obtiene el token
        $this->setupTestUser();
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

    private function setupTestUser(): void
    {
        $this->testEmail = 'e2e_task_test_' . time() . '@example.com';
        $password = 'SecureP@ss123';

        // Registra el usuario de prueba
        $this->makeRequest('POST', $this->authServiceUrl . '/v1/api/register', [
            'email' => $this->testEmail,
            'password' => $password
        ]);

        // Inicia sesión para obtener el token
        $loginResponse = $this->makeRequest('POST', $this->authServiceUrl . '/v1/api/login', [
            'email' => $this->testEmail,
            'password' => $password
        ]);

        $this->testToken = $loginResponse['body']['token'] ?? null;
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

    public function test_complete_task_flow(): void
    {
        $this->assertNotNull($this->testToken, 'Test token should be available');

        $listResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks',
            null,
            $this->testToken
        );

        $this->assertEquals(200, $listResponse['status']);
        $this->assertIsArray($listResponse['body']);

        $createResponse = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            [
                'title' => 'E2E Test Task',
                'description' => 'This is a test task created by E2E test',
                'status' => 'pending'
            ],
            $this->testToken
        );

        $this->assertEquals(201, $createResponse['status']);
        $this->assertArrayHasKey('id', $createResponse['body']);
        $this->assertArrayHasKey('title', $createResponse['body']);
        $this->assertEquals('E2E Test Task', $createResponse['body']['title']);
        $this->assertEquals('pending', $createResponse['body']['status']);

        $taskId = $createResponse['body']['id'];

        $getResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks/' . $taskId,
            null,
            $this->testToken
        );

        $this->assertEquals(200, $getResponse['status']);
        $this->assertEquals($taskId, $getResponse['body']['id']);
        $this->assertEquals('E2E Test Task', $getResponse['body']['title']);

        $updateResponse = $this->makeRequest(
            'PUT',
            $this->taskServiceUrl . '/v1/api/tasks/' . $taskId,
            [
                'title' => 'Updated E2E Test Task',
                'description' => 'Updated description',
                'status' => 'in_progress'
            ],
            $this->testToken
        );

        $this->assertEquals(200, $updateResponse['status']);
        $this->assertEquals('Updated E2E Test Task', $updateResponse['body']['title']);
        $this->assertEquals('in_progress', $updateResponse['body']['status']);

        $listAgainResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks',
            null,
            $this->testToken
        );

        $this->assertEquals(200, $listAgainResponse['status']);
        $this->assertGreaterThan(0, count($listAgainResponse['body']));

        $foundTask = false;
        foreach ($listAgainResponse['body'] as $task) {
            if ($task['id'] === $taskId) {
                $foundTask = true;
                $this->assertEquals('Updated E2E Test Task', $task['title']);
                $this->assertEquals('in_progress', $task['status']);
                break;
            }
        }
        $this->assertTrue($foundTask, 'Updated task should be in the list');

        $filterResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks?status=in_progress',
            null,
            $this->testToken
        );

        $this->assertEquals(200, $filterResponse['status']);
        foreach ($filterResponse['body'] as $task) {
            $this->assertEquals('in_progress', $task['status']);
        }

        $doneResponse = $this->makeRequest(
            'PUT',
            $this->taskServiceUrl . '/v1/api/tasks/' . $taskId,
            [
                'status' => 'done'
            ],
            $this->testToken
        );

        $this->assertEquals(200, $doneResponse['status']);
        $this->assertEquals('done', $doneResponse['body']['status']);
    }

    public function test_create_task_without_authentication(): void
    {
        $response = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            [
                'title' => 'Unauthorized Task',
                'status' => 'pending'
            ],
            null
        );

        $this->assertEquals(401, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('UNAUTHORIZED', $response['body']['error']['code']);
    }

    public function test_create_task_with_invalid_token(): void
    {
        $response = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            [
                'title' => 'Invalid Token Task',
                'status' => 'pending'
            ],
            'invalid.token.here'
        );

        $this->assertEquals(401, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('INVALID_TOKEN', $response['body']['error']['code']);
    }

    public function test_create_task_with_empty_title(): void
    {
        $response = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            [
                'title' => '',
                'status' => 'pending'
            ],
            $this->testToken
        );

        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('VALIDATION_ERROR', $response['body']['error']['code']);
    }

    public function test_create_task_with_invalid_status(): void
    {
        $response = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            [
                'title' => 'Task with invalid status',
                'status' => 'invalid_status'
            ],
            $this->testToken
        );

        $this->assertEquals(400, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
    }

    public function test_update_nonexistent_task(): void
    {
        $response = $this->makeRequest(
            'PUT',
            $this->taskServiceUrl . '/v1/api/tasks/999999',
            [
                'title' => 'Updated Title'
            ],
            $this->testToken
        );

        $this->assertEquals(404, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('TASK_NOT_FOUND', $response['body']['error']['code']);
    }

    public function test_get_nonexistent_task(): void
    {
        $response = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks/999999',
            null,
            $this->testToken
        );

        $this->assertEquals(404, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertEquals('TASK_NOT_FOUND', $response['body']['error']['code']);
    }

    public function test_ownership_validation(): void
    {
        $secondUserEmail = 'e2e_second_user_' . time() . '@example.com';
        $password = 'SecureP@ss123';

        $this->makeRequest('POST', $this->authServiceUrl . '/v1/api/register', [
            'email' => $secondUserEmail,
            'password' => $password
        ]);

        $loginResponse = $this->makeRequest('POST', $this->authServiceUrl . '/v1/api/login', [
            'email' => $secondUserEmail,
            'password' => $password
        ]);

        $secondUserToken = $loginResponse['body']['token'];

        $createResponse = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            [
                'title' => 'First User Task',
                'status' => 'pending'
            ],
            $this->testToken
        );

        $taskId = $createResponse['body']['id'];

        $updateResponse = $this->makeRequest(
            'PUT',
            $this->taskServiceUrl . '/v1/api/tasks/' . $taskId,
            [
                'title' => 'Hacked Task'
            ],
            $secondUserToken
        );

        $this->assertEquals(403, $updateResponse['status']);
        $this->assertArrayHasKey('error', $updateResponse['body']);
        $this->assertEquals('FORBIDDEN', $updateResponse['body']['error']['code']);

        $getResponse = $this->makeRequest(
            'GET',
            $this->taskServiceUrl . '/v1/api/tasks/' . $taskId,
            null,
            $secondUserToken
        );

        $this->assertEquals(403, $getResponse['status']);
        $this->assertArrayHasKey('error', $getResponse['body']);
        $this->assertEquals('FORBIDDEN', $getResponse['body']['error']['code']);
    }

    public function test_default_status_is_pending(): void
    {
        $response = $this->makeRequest(
            'POST',
            $this->taskServiceUrl . '/v1/api/tasks',
            [
                'title' => 'Task without status'
            ],
            $this->testToken
        );

        $this->assertEquals(201, $response['status']);
        $this->assertEquals('pending', $response['body']['status']);
    }

    public function test_health_check(): void
    {
        $response = $this->makeRequest('GET', $this->taskServiceUrl . '/health', null, null);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('status', $response['body']);
        $this->assertEquals('healthy', $response['body']['status']);
    }
}
