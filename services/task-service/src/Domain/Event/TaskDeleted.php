<?php

declare(strict_types=1);

namespace TaskService\Domain\Event;

use TaskService\Domain\Entity\Task;

/**
 * TaskDeleted Event
 * Immutable DTO for task deletion event
 */
final class TaskDeleted
{
    public int $taskId;
    public int $userId;
    public string $deletedAt;

    public function __construct(Task $task)
    {
        $this->taskId = $task->getId();
        $this->userId = $task->getUserId();
        $this->deletedAt = date('c');
    }
}
