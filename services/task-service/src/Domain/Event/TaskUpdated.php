<?php

declare(strict_types=1);

namespace TaskService\Domain\Event;

/**
 * TaskUpdated Event
 * Immutable DTO for task update event
 */
final class TaskUpdated
{
    public int $taskId;
    public int $userId;
    public string $title;
    public string $status;
    public string $updatedAt;

    public function __construct(
        int $taskId,
        int $userId,
        string $title,
        string $status,
        string $updatedAt
    ) {
        $this->taskId = $taskId;
        $this->userId = $userId;
        $this->title = $title;
        $this->status = $status;
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'event' => 'task.updated',
            'userId' => $this->userId,
            'data' => [
                'id' => $this->taskId,
                'title' => $this->title,
                'status' => $this->status,
                'updated_at' => $this->updatedAt,
            ],
        ];
    }
}
