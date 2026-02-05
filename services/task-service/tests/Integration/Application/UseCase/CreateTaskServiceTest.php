<?php

declare(strict_types=1);

namespace TaskService\Tests\Integration\Application\UseCase;

use TaskService\Application\Port\EventPublisherInterface;
use TaskService\Application\UseCase\CreateTaskService;
use TaskService\Domain\Repository\TaskRepositoryInterface;
use TaskService\Domain\Validator\TaskValidator;
use PHPUnit\Framework\TestCase;

class CreateTaskServiceTest extends TestCase
{
    private TaskRepositoryInterface $taskRepository;
    private TaskValidator $validator;
    private EventPublisherInterface $eventPublisher;
    private CreateTaskService $service;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepositoryInterface::class);
        $this->validator = new TaskValidator();
        $this->eventPublisher = $this->createMock(EventPublisherInterface::class);

        $this->service = new CreateTaskService(
            $this->taskRepository,
            $this->validator,
            $this->eventPublisher
        );
    }

    public function test_creates_task_with_valid_data(): void
    {
        $userId = 1;
        $data = [
            'title' => 'Test Task',
            'description' => 'Test description',
            'status' => 'pending'
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($task) {
                $this->assertEquals('Test Task', $task->getTitle()->getValue());
                $this->assertEquals(1, $task->getUserId());
                return $task;
            });

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTaskCreated');

        $task = $this->service->execute($userId, $data);

        $this->assertEquals('Test Task', $task->getTitle()->getValue());
        $this->assertEquals($userId, $task->getUserId());
    }

    public function test_throws_exception_for_empty_title(): void
    {
        $data = [
            'title' => '',
            'status' => 'pending'
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/VALIDATION_ERROR/');

        $this->service->execute(1, $data);
    }

    public function test_throws_exception_for_invalid_status(): void
    {
        $data = [
            'title' => 'Test Task',
            'status' => 'invalid_status'
        ];

        $this->expectException(\Exception::class);

        $this->service->execute(1, $data);
    }

    public function test_defaults_to_pending_status_when_not_provided(): void
    {
        $data = [
            'title' => 'Test Task'
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($task) {
                $this->assertTrue($task->getStatus()->isPending());
                return $task;
            });

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTaskCreated');

        $this->service->execute(1, $data);
    }

    public function test_publishes_event_after_saving_task(): void
    {
        $data = [
            'title' => 'Event Test Task',
            'status' => 'pending'
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnArgument(0);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishTaskCreated')
            ->with($this->callback(function ($event) {
                return $event->title === 'Event Test Task' &&
                       $event->status === 'pending';
            }));

        $this->service->execute(1, $data);
    }
}
