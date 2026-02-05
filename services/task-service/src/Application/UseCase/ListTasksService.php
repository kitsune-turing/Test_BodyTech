<?php

declare(strict_types=1);

namespace TaskService\Application\UseCase;

use TaskService\Domain\Repository\TaskRepositoryInterface;
use TaskService\Domain\Validator\TaskValidator;
use TaskService\Domain\ValueObject\TaskStatus;
use Exception;

/**
 * List Tasks Use Case
 */
final class ListTasksService
{
    private TaskRepositoryInterface $taskRepository;
    private TaskValidator $validator;

    public function __construct(
        TaskRepositoryInterface $taskRepository,
        TaskValidator $validator
    ) {
        $this->taskRepository = $taskRepository;
        $this->validator = $validator;
    }

    public function execute(int $userId, array $filters = []): array
    {
        try {
            // Valida los parámetros de filtro
            $errors = $this->validator->validateFilters($filters);
            if (!empty($errors)) {
                throw new Exception(json_encode([
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid filter parameters',
                    'details' => $errors,
                ]));
            }

            // Parsea los parámetros de paginación
            $page = isset($filters['page']) ? max(1, (int) $filters['page']) : 1;
            $limit = isset($filters['limit']) ? min(100, max(1, (int) $filters['limit'])) : 20;
            $offset = ($page - 1) * $limit;

            // Parsea los parámetros de filtro
            $status = isset($filters['status'])
                ? TaskStatus::fromString($filters['status'])
                : null;

            $search = $filters['search'] ?? null;

            // Parsea los parámetros de ordenamiento
            $sortBy = $filters['sort'] ?? 'created_at';
            $sortOrder = isset($filters['order']) ? strtoupper($filters['order']) : 'DESC';

            // Obtiene las tareas paginadas
            $tasks = $this->taskRepository->findByUserIdPaginated(
                $userId,
                $status,
                $search,
                $limit,
                $offset,
                $sortBy,
                $sortOrder
            );

            // Obtiene el conteo total de tareas
            $total = $this->taskRepository->countByUserId($userId, $status, $search);

            // Retorna las tareas con metadata de paginación
            return [
                'items' => array_map(fn($task) => $task->toArray(), $tasks),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit),
                ],
            ];
        } catch (\Exception $e) {
            throw new Exception('Error listing tasks: ' . $e->getMessage(), 0, $e);
        }
    }
}
