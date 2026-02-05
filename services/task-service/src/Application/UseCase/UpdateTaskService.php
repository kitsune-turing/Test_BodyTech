<?php

declare(strict_types=1);

namespace TaskService\Application\UseCase;

use TaskService\Application\Port\EventPublisherInterface;
use TaskService\Domain\Entity\Task;
use TaskService\Domain\Event\TaskUpdated;
use TaskService\Domain\Repository\TaskRepositoryInterface;
use TaskService\Domain\Validator\TaskValidator;
use TaskService\Domain\ValueObject\TaskStatus;
use TaskService\Domain\ValueObject\TaskTitle;
use Exception;

/**
 * Update Task Use Case
 */
final class UpdateTaskService
{
    private TaskRepositoryInterface $taskRepository;
    private TaskValidator $validator;
    private EventPublisherInterface $eventPublisher;

    public function __construct(
        TaskRepositoryInterface $taskRepository,
        TaskValidator $validator,
        EventPublisherInterface $eventPublisher
    ) {
        $this->taskRepository = $taskRepository;
        $this->validator = $validator;
        $this->eventPublisher = $eventPublisher;
    }

    public function execute(int $taskId, int $userId, array $data): Task
    {
        // Busca la tarea por ID
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
                'message' => 'You do not have permission to update this task',
            ]));
        }

        // Valida los datos de la tarea
        $errors = $this->validator->validate($data);
        if (!empty($errors)) {
            throw new Exception(json_encode([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Invalid task data',
                'details' => $errors,
            ]));
        }

        // Actualiza los campos de la tarea
        $task->update(
            new TaskTitle($data['title']),
            $data['description'] ?? $task->getDescription(),
            isset($data['status']) ? TaskStatus::fromString($data['status']) : $task->getStatus()
        );

        // Guarda la tarea actualizada en la base de datos
        $updatedTask = $this->taskRepository->save($task);

        // Publica el evento de tarea actualizada
        $event = new TaskUpdated(
            $updatedTask->getId(),
            $updatedTask->getUserId(),
            $updatedTask->getTitle()->getValue(),
            $updatedTask->getStatus()->getValue(),
            $updatedTask->getUpdatedAt()->format('Y-m-d\TH:i:s\Z')
        );

        $this->eventPublisher->publishTaskUpdated($event);

        return $updatedTask;
    }
}
