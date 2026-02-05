<?php

declare(strict_types=1);

namespace TaskService\Tests\Integration\Infrastructure\Persistence;

use TaskService\Domain\Entity\Task;
use TaskService\Domain\ValueObject\TaskStatus;
use TaskService\Domain\ValueObject\TaskTitle;
use TaskService\Infrastructure\Persistence\PhalconTaskRepository;
use PHPUnit\Framework\TestCase;
use PDO;

class PhalconTaskRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PhalconTaskRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                status VARCHAR(50) DEFAULT "pending",
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->repository = new PhalconTaskRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    public function test_saves_new_task(): void
    {
        $task = Task::create(
            1,
            new TaskTitle('Test Task'),
            'Test description',
            TaskStatus::pending()
        );

        $savedTask = $this->repository->save($task);

        $this->assertNotNull($savedTask->getId());
        $this->assertEquals('Test Task', $savedTask->getTitle()->getValue());
        $this->assertEquals(1, $savedTask->getUserId());
    }

    public function test_finds_task_by_id(): void
    {
        $task = Task::create(
            1,
            new TaskTitle('Findable Task'),
            null,
            TaskStatus::pending()
        );
        $savedTask = $this->repository->save($task);

        $foundTask = $this->repository->findById($savedTask->getId());

        $this->assertNotNull($foundTask);
        $this->assertEquals($savedTask->getId(), $foundTask->getId());
        $this->assertEquals('Findable Task', $foundTask->getTitle()->getValue());
    }

    public function test_returns_null_when_task_not_found(): void
    {
        $task = $this->repository->findById(99999);

        $this->assertNull($task);
    }

    public function test_finds_tasks_by_user_id(): void
    {
        $this->repository->save(Task::create(1, new TaskTitle('User 1 Task 1'), null, TaskStatus::pending()));
        $this->repository->save(Task::create(1, new TaskTitle('User 1 Task 2'), null, TaskStatus::done()));
        $this->repository->save(Task::create(2, new TaskTitle('User 2 Task'), null, TaskStatus::pending()));

        $user1Tasks = $this->repository->findByUserId(1);

        $this->assertCount(2, $user1Tasks);
        $this->assertEquals(1, $user1Tasks[0]->getUserId());
        $this->assertEquals(1, $user1Tasks[1]->getUserId());
    }

    public function test_finds_tasks_by_user_id_and_status(): void
    {
        $this->repository->save(Task::create(1, new TaskTitle('Pending 1'), null, TaskStatus::pending()));
        $this->repository->save(Task::create(1, new TaskTitle('Pending 2'), null, TaskStatus::pending()));
        $this->repository->save(Task::create(1, new TaskTitle('Done'), null, TaskStatus::done()));

        $pendingTasks = $this->repository->findByUserId(1, TaskStatus::pending());

        $this->assertCount(2, $pendingTasks);
        $this->assertTrue($pendingTasks[0]->getStatus()->isPending());
        $this->assertTrue($pendingTasks[1]->getStatus()->isPending());
    }

    public function test_updates_existing_task(): void
    {
        $task = Task::create(1, new TaskTitle('Original Title'), null, TaskStatus::pending());
        $savedTask = $this->repository->save($task);

        $savedTask->update(
            new TaskTitle('Updated Title'),
            'New description',
            TaskStatus::done()
        );

        $updatedTask = $this->repository->save($savedTask);

        $this->assertEquals($savedTask->getId(), $updatedTask->getId());
        $this->assertEquals('Updated Title', $updatedTask->getTitle()->getValue());
        $this->assertEquals('New description', $updatedTask->getDescription());
        $this->assertTrue($updatedTask->getStatus()->isDone());
    }

    public function test_deletes_task(): void
    {
        $task = Task::create(1, new TaskTitle('To Delete'), null, TaskStatus::pending());
        $savedTask = $this->repository->save($task);

        $result = $this->repository->delete($savedTask->getId());

        $this->assertTrue($result);
        $this->assertNull($this->repository->findById($savedTask->getId()));
    }

    public function test_counts_tasks_by_user_and_status(): void
    {
        $this->repository->save(Task::create(1, new TaskTitle('Pending 1'), null, TaskStatus::pending()));
        $this->repository->save(Task::create(1, new TaskTitle('Pending 2'), null, TaskStatus::pending()));
        $this->repository->save(Task::create(1, new TaskTitle('Done'), null, TaskStatus::done()));

        $count = $this->repository->countByUserAndStatus(1, TaskStatus::pending());

        $this->assertEquals(2, $count);
    }
}
