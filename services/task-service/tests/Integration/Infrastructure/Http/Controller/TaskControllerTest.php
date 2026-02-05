<?php

declare(strict_types=1);

namespace TaskService\Tests\Integration\Infrastructure\Http\Controller;

use PHPUnit\Framework\TestCase;
use TaskService\Infrastructure\Http\Controller\TaskController;
use TaskService\Application\UseCase\ListTasksService;
use TaskService\Application\UseCase\CreateTaskService;
use TaskService\Application\UseCase\UpdateTaskService;
use TaskService\Application\UseCase\DeleteTaskService;
use TaskService\Application\UseCase\GetTaskService;
use TaskService\Infrastructure\Cache\RedisCacheManager;

final class TaskControllerTest extends TestCase
{
    private TaskController $controller;
    private $listTasksService;
    private $createTaskService;
    private $updateTaskService;
    private $deleteTaskService;
    private $getTaskService;
    private $cacheManager;

    protected function setUp(): void
    {
        $this->listTasksService = $this->createMock(ListTasksService::class);
        $this->createTaskService = $this->createMock(CreateTaskService::class);
        $this->updateTaskService = $this->createMock(UpdateTaskService::class);
        $this->deleteTaskService = $this->createMock(DeleteTaskService::class);
        $this->getTaskService = $this->createMock(GetTaskService::class);
        $this->cacheManager = $this->createMock(RedisCacheManager::class);

        $this->controller = new TaskController(
            $this->listTasksService,
            $this->createTaskService,
            $this->updateTaskService,
            $this->deleteTaskService,
            $this->getTaskService,
            $this->cacheManager
        );
    }

    public function testListTasksSuccessfully(): void
    {
        $userId = 1;
        $filters = ['status' => 'pending', 'page' => 1, 'limit' => 20];

        $expectedTasks = [
            'items' => [
                [
                    'id' => 1,
                    'title' => 'Task 1',
                    'status' => 'pending',
                ],
                [
                    'id' => 2,
                    'title' => 'Task 2',
                    'status' => 'pending',
                ],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 20,
                'total' => 2,
                'pages' => 1,
            ],
        ];

        $this->cacheManager
            ->expects($this->once())
            ->method('getDecoded')
            ->willReturn(null);

        $this->listTasksService
            ->expects($this->once())
            ->method('execute')
            ->with($userId, $filters)
            ->willReturn($expectedTasks);

        $this->cacheManager
            ->expects($this->once())
            ->method('setEncoded');

        $result = $this->controller->list($userId, $filters);

        $this->assertEquals(200, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($expectedTasks, $result['data']);
    }

    public function testListTasksFromCache(): void
    {
        $userId = 1;
        $cachedTasks = [
            ['id' => 1, 'title' => 'Cached Task'],
        ];

        $this->cacheManager
            ->expects($this->once())
            ->method('getDecoded')
            ->willReturn($cachedTasks);

        $this->listTasksService
            ->expects($this->never())
            ->method('execute');

        $result = $this->controller->list($userId, []);

        $this->assertEquals(200, $result['status']);
        $this->assertTrue($result['cached']);
        $this->assertEquals($cachedTasks, $result['data']);
    }

    public function testCreateTaskSuccessfully(): void
    {
        $userId = 1;
        $requestData = [
            'title' => 'New Task',
            'description' => 'Task description',
            'status' => 'pending',
        ];

        $expectedTask = [
            'id' => 1,
            'user_id' => 1,
            'title' => 'New Task',
            'description' => 'Task description',
            'status' => 'pending',
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00',
        ];

        $this->createTaskService
            ->expects($this->once())
            ->method('execute')
            ->with($userId, $requestData)
            ->willReturn($expectedTask);

        $this->cacheManager
            ->expects($this->once())
            ->method('delete');

        $result = $this->controller->create($userId, $requestData);

        $this->assertEquals(201, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($expectedTask, $result['data']['task']);
    }

    public function testCreateTaskWithValidationError(): void
    {
        $userId = 1;
        $requestData = [
            'title' => '',
            'status' => 'invalid',
        ];

        $this->createTaskService
            ->expects($this->once())
            ->method('execute')
            ->with($userId, $requestData)
            ->willThrowException(new \Exception(json_encode([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => ['title' => 'Title is required'],
            ])));

        $result = $this->controller->create($userId, $requestData);

        $this->assertEquals(400, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('error', $result['data']);
    }

    public function testGetTaskSuccessfully(): void
    {
        $taskId = 1;
        $userId = 1;

        $expectedTask = [
            'id' => 1,
            'user_id' => 1,
            'title' => 'Task 1',
            'status' => 'pending',
        ];

        $this->getTaskService
            ->expects($this->once())
            ->method('execute')
            ->with($taskId, $userId)
            ->willReturn($expectedTask);

        $result = $this->controller->get($taskId, $userId);

        $this->assertEquals(200, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($expectedTask, $result['data']['task']);
    }

    public function testGetTaskNotFound(): void
    {
        $taskId = 999;
        $userId = 1;

        $this->getTaskService
            ->expects($this->once())
            ->method('execute')
            ->with($taskId, $userId)
            ->willThrowException(new \Exception(json_encode([
                'code' => 'NOT_FOUND',
                'message' => 'Task not found',
            ])));

        $result = $this->controller->get($taskId, $userId);

        $this->assertEquals(400, $result['status']);
    }

    public function testUpdateTaskSuccessfully(): void
    {
        $taskId = 1;
        $userId = 1;
        $requestData = [
            'title' => 'Updated Task',
            'status' => 'in_progress',
        ];

        $expectedTask = [
            'id' => 1,
            'user_id' => 1,
            'title' => 'Updated Task',
            'status' => 'in_progress',
            'updated_at' => '2024-01-01 13:00:00',
        ];

        $this->updateTaskService
            ->expects($this->once())
            ->method('execute')
            ->with($taskId, $userId, $requestData)
            ->willReturn($expectedTask);

        $this->cacheManager
            ->expects($this->once())
            ->method('delete');

        $result = $this->controller->update($taskId, $userId, $requestData);

        $this->assertEquals(200, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($expectedTask, $result['data']);
    }

    public function testDeleteTaskSuccessfully(): void
    {
        $taskId = 1;
        $userId = 1;

        $this->deleteTaskService
            ->expects($this->once())
            ->method('execute')
            ->with($taskId, $userId);

        $this->cacheManager
            ->expects($this->once())
            ->method('delete');

        $result = $this->controller->delete($taskId, $userId);

        $this->assertEquals(204, $result['status']);
    }

    public function testDeleteTaskNotFound(): void
    {
        $taskId = 999;
        $userId = 1;

        $this->deleteTaskService
            ->expects($this->once())
            ->method('execute')
            ->with($taskId, $userId)
            ->willThrowException(new \Exception(json_encode([
                'code' => 'NOT_FOUND',
                'message' => 'Task not found',
            ])));

        $result = $this->controller->delete($taskId, $userId);

        $this->assertEquals(400, $result['status']);
    }
}
