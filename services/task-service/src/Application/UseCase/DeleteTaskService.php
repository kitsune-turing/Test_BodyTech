<?php

declare(strict_types=1);

namespace TaskService\Application\UseCase;

use TaskService\Application\Port\EventPublisherInterface;
use TaskService\Domain\Event\TaskDeleted;
use TaskService\Domain\Repository\TaskRepositoryInterface;
use Exception;

/**
 * Delete Task Use Case
 */
final class DeleteTaskService
{
    private TaskRepositoryInterface $taskRepository;
    private EventPublisherInterface $eventPublisher;

    public function __construct(
        TaskRepositoryInterface $taskRepository,
        EventPublisherInterface $eventPublisher
    ) {
        $this->taskRepository = $taskRepository;
        $this->eventPublisher = $eventPublisher;
    }

    public function execute(int $taskId, int $userId): void
    {
        // Find task
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw new Exception(json_encode([
                'code' => 'NOT_FOUND',
                'message' => 'Task not found',
            ]));
        }

        // Verify ownership
        if (!$task->belongsToUser($userId)) {
            throw new Exception(json_encode([
                'code' => 'FORBIDDEN',
                'message' => 'You cannot delete this task',
            ]));
        }

        // Delete task
        $deleted = $this->taskRepository->delete($taskId);
        
        if (!$deleted) {
            throw new Exception(json_encode([
                'code' => 'INTERNAL_ERROR',
                'message' => 'Failed to delete task from database',
            ]));
        }

        // Publish event (wrapped in try-catch to prevent failure)
        try {
            $this->eventPublisher->publish(new TaskDeleted($task));
        } catch (\Throwable $e) {
            // Event publishing failed silently
        }
    }
}
