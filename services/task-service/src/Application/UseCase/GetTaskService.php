<?php

declare(strict_types=1);

namespace TaskService\Application\UseCase;

use TaskService\Domain\Entity\Task;
use TaskService\Domain\Repository\TaskRepositoryInterface;
use Exception;

/**
 * Get Task Use Case
 */
final class GetTaskService
{
    private TaskRepositoryInterface $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function execute(int $taskId, int $userId): Task
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw new Exception(json_encode([
                'code' => 'NOT_FOUND',
                'message' => 'Task not found',
            ]));
        }

        // Verifica que la tarea pertenece al usuario
        if (!$task->belongsToUser($userId)) {
            throw new Exception(json_encode([
                'code' => 'FORBIDDEN',
                'message' => 'You do not have permission to access this task',
            ]));
        }

        return $task;
    }
}
