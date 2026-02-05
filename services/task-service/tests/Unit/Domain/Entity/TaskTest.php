<?php

declare(strict_types=1);

namespace TaskService\Tests\Unit\Domain\Entity;

use PHPUnit\Framework\TestCase;
use TaskService\Domain\Entity\Task;
use TaskService\Domain\ValueObject\TaskTitle;
use TaskService\Domain\ValueObject\TaskStatus;

final class TaskTest extends TestCase
{
    public function testCreateTaskSuccessfully(): void
    {
        $task = new Task(
            1,
            100,
            new TaskTitle('Test Task'),
            'Test Description',
            TaskStatus::fromString('pending'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
            new \DateTimeImmutable('2024-01-01 12:00:00')
        );

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals(1, $task->getId());
        $this->assertEquals(100, $task->getUserId());
        $this->assertEquals('Test Task', $task->getTitle()->getValue());
        $this->assertEquals('Test Description', $task->getDescription());
        $this->assertEquals('pending', $task->getStatus()->getValue());
    }

    public function testCreateTaskWithoutId(): void
    {
        $task = new Task(
            null,
            100,
            new TaskTitle('New Task'),
            'New Description',
            TaskStatus::fromString('pending'),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $this->assertNull($task->getId());
        $this->assertEquals(100, $task->getUserId());
    }

    public function testUpdateTaskTitle(): void
    {
        $task = new Task(
            1,
            100,
            new TaskTitle('Original Title'),
            'Description',
            TaskStatus::fromString('pending'),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $newTitle = new TaskTitle('Updated Title');
        $task = new Task(
            $task->getId(),
            $task->getUserId(),
            $newTitle,
            $task->getDescription(),
            $task->getStatus(),
            $task->getCreatedAt(),
            new \DateTimeImmutable()
        );

        $this->assertEquals('Updated Title', $task->getTitle()->getValue());
    }

    public function testUpdateTaskStatus(): void
    {
        $task = new Task(
            1,
            100,
            new TaskTitle('Task'),
            'Description',
            TaskStatus::fromString('pending'),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $task = new Task(
            $task->getId(),
            $task->getUserId(),
            $task->getTitle(),
            $task->getDescription(),
            TaskStatus::fromString('in_progress'),
            $task->getCreatedAt(),
            new \DateTimeImmutable()
        );

        $this->assertEquals('in_progress', $task->getStatus()->getValue());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
        $updatedAt = new \DateTimeImmutable('2024-01-01 13:00:00');

        $task = new Task(
            1,
            100,
            new TaskTitle('Test Task'),
            'Test Description',
            TaskStatus::fromString('done'),
            $createdAt,
            $updatedAt
        );

        $array = $task->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals(100, $array['user_id']);
        $this->assertEquals('Test Task', $array['title']);
        $this->assertEquals('Test Description', $array['description']);
        $this->assertEquals('done', $array['status']);
        $this->assertEquals('2024-01-01 12:00:00', $array['created_at']);
        $this->assertEquals('2024-01-01 13:00:00', $array['updated_at']);
    }

    public function testToArrayWithNullDescription(): void
    {
        $task = new Task(
            1,
            100,
            new TaskTitle('Test Task'),
            null,
            TaskStatus::fromString('pending'),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $array = $task->toArray();

        $this->assertNull($array['description']);
    }

    public function testTaskEquality(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
        $updatedAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        $task1 = new Task(
            1,
            100,
            new TaskTitle('Task'),
            'Description',
            TaskStatus::fromString('pending'),
            $createdAt,
            $updatedAt
        );

        $task2 = new Task(
            1,
            100,
            new TaskTitle('Task'),
            'Description',
            TaskStatus::fromString('pending'),
            $createdAt,
            $updatedAt
        );

        $this->assertEquals($task1->getId(), $task2->getId());
        $this->assertEquals($task1->getUserId(), $task2->getUserId());
        $this->assertEquals($task1->getTitle()->getValue(), $task2->getTitle()->getValue());
    }
}
