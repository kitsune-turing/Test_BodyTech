<?php

declare(strict_types=1);

namespace TaskService\Domain\Event;

/**
 * TaskCreated Event
 * Immutable DTO for task creation event
 */
final class TaskCreated
{
    public int $taskId;
    public int $userId;
    public string $title;
    public string $status;
    public string $createdAt;

    public function __construct(
        int $taskId,
        int $userId,
        string $title,
        string $status,
        string $createdAt
    ) {
        $this->taskId = $taskId;
        $this->userId = $userId;
        $this->title = $title;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    public function toArray(): array
    {
        return [
            'event' => 'task.created',
            'userId' => $this->userId,
            'data' => [
                'id' => $this->taskId,
                'title' => $this->title,
                'status' => $this->status,
                'created_at' => $this->createdAt,
            ],
        ];
    }
}
