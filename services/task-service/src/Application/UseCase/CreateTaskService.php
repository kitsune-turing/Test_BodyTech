<?php

declare(strict_types=1);

namespace TaskService\Application\UseCase;

use TaskService\Application\Port\EventPublisherInterface;
use TaskService\Domain\Entity\Task;
use TaskService\Domain\Event\TaskCreated;
use TaskService\Domain\Repository\TaskRepositoryInterface;
use TaskService\Domain\Validator\TaskValidator;
use TaskService\Domain\ValueObject\TaskStatus;
use TaskService\Domain\ValueObject\TaskTitle;
use Exception;

/**
 * Create Task Use Case
 */
final class CreateTaskService
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

    public function execute(int $userId, array $data): Task
    {
        // Validate
        $errors = $this->validator->validate($data);
        if (!empty($errors)) {
            throw new Exception(json_encode([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Invalid task data',
                'details' => $errors,
            ]));
        }

        // Create Task
        $task = Task::create(
            $userId,
            new TaskTitle($data['title']),
            $data['description'] ?? null,
            isset($data['status']) ? TaskStatus::fromString($data['status']) : TaskStatus::pending()
        );

        // Save
        $savedTask = $this->taskRepository->save($task);

        // Publish Event
        $event = new TaskCreated(
            $savedTask->getId(),
            $savedTask->getUserId(),
            $savedTask->getTitle()->getValue(),
            $savedTask->getStatus()->getValue(),
            $savedTask->getCreatedAt()->format('Y-m-d\TH:i:s\Z')
        );

        $this->eventPublisher->publishTaskCreated($event);

        return $savedTask;
    }
}
