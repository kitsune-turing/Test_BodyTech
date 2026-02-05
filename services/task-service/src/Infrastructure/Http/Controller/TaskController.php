<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Http\Controller;

use TaskService\Application\UseCase\CreateTaskService;
use TaskService\Application\UseCase\DeleteTaskService;
use TaskService\Application\UseCase\GetTaskService;
use TaskService\Application\UseCase\ListTasksService;
use TaskService\Application\UseCase\UpdateTaskService;
use TaskService\Infrastructure\Cache\RedisCacheManager;

/**
 * Task Controller
 * Handles HTTP requests for task endpoints
 */
final class TaskController
{
    private ListTasksService $listTasksService;
    private CreateTaskService $createTaskService;
    private UpdateTaskService $updateTaskService;
    private DeleteTaskService $deleteTaskService;
    private GetTaskService $getTaskService;
    private RedisCacheManager $cacheManager;

    public function __construct(
        ListTasksService $listTasksService,
        CreateTaskService $createTaskService,
        UpdateTaskService $updateTaskService,
        DeleteTaskService $deleteTaskService,
        GetTaskService $getTaskService,
        RedisCacheManager $cacheManager
    ) {
        $this->listTasksService = $listTasksService;
        $this->createTaskService = $createTaskService;
        $this->updateTaskService = $updateTaskService;
        $this->deleteTaskService = $deleteTaskService;
        $this->getTaskService = $getTaskService;
        $this->cacheManager = $cacheManager;
    }

    public function list(int $userId, array $filters = []): array
    {
        try {
            $hasActiveFilters = !empty($filters['search'])
                || !empty($filters['status'])
                || (isset($filters['page']) && (int)$filters['page'] !== 1)
                || (isset($filters['sort']) && $filters['sort'] !== 'created_at')
                || (isset($filters['order']) && strtoupper($filters['order']) !== 'DESC');

            if (!$hasActiveFilters) {
                $cacheKey = RedisCacheManager::getUserTasksKey($userId);
                $cachedTasks = $this->cacheManager->getDecoded($cacheKey);

                if ($cachedTasks !== null) {
                    return [
                        'status' => 200,
                        'data' => $cachedTasks,
                        'cached' => true,
                    ];
                }
            }

            $tasks = $this->listTasksService->execute($userId, $filters);

            if (!$hasActiveFilters) {
                $cacheKey = RedisCacheManager::getUserTasksKey($userId);
                $this->cacheManager->setEncoded($cacheKey, $tasks, 300);
            }

            return [
                'status' => 200,
                'data' => $tasks,
                'cached' => false,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ];
        }
    }

    public function create(int $userId, array $requestData): array
    {
        try {
            $task = $this->createTaskService->execute($userId, $requestData);

            // Invalida el caché de tareas del usuario
            $this->cacheManager->delete(RedisCacheManager::getUserTasksKey($userId));

            return [
                'status' => 201,
                'data' => $task->toArray(),
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function update(int $taskId, int $userId, array $requestData): array
    {
        try {
            $task = $this->updateTaskService->execute($taskId, $userId, $requestData);

            // Invalida los cachés relacionados con la tarea y el usuario
            $this->cacheManager->delete(RedisCacheManager::getUserTasksKey($userId));
            $this->cacheManager->delete(RedisCacheManager::getTaskKey($taskId));

            return [
                'status' => 200,
                'data' => $task->toArray(),
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function get(int $taskId, int $userId): array
    {
        try {
            // Intenta obtener desde el caché primero
            $cacheKey = RedisCacheManager::getTaskKey($taskId);
            $cachedTask = $this->cacheManager->getDecoded($cacheKey);
            
            if ($cachedTask !== null) {
                return [
                    'status' => 200,
                    'data' => $cachedTask,
                    'cached' => true,
                ];
            }

            // Si no está en caché, obtiene desde la base de datos
            $task = $this->getTaskService->execute($taskId, $userId);

            // Almacena en caché por 1 hora
            $this->cacheManager->setEncoded($cacheKey, $task->toArray(), 3600);

            return [
                'status' => 200,
                'data' => $task->toArray(),
                'cached' => false,
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function delete(int $taskId, int $userId): array
    {
        try {
            $this->deleteTaskService->execute($taskId, $userId);

            // Invalida los cachés relacionados con la tarea eliminada
            $this->cacheManager->delete(RedisCacheManager::getTaskKey($taskId));
            
            // Invalida la lista de tareas del usuario (requiere userId del contexto de eliminación).
            // Esto se maneja invalidando el patrón completo.
            $this->cacheManager->deletePattern("user_tasks:*");

            return [
                'status' => 204,
                'data' => null,
            ];
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    private function handleError(\Exception $e): array
    {
        $errorData = json_decode($e->getMessage(), true);

        if (is_array($errorData) && isset($errorData['code'])) {
            $statusCode = 500; // default
            
            switch($errorData['code']) {
                case 'VALIDATION_ERROR':
                    $statusCode = 400;
                    break;
                case 'NOT_FOUND':
                    $statusCode = 404;
                    break;
                case 'FORBIDDEN':
                    $statusCode = 403;
                    break;
            }

            return [
                'status' => $statusCode,
                'error' => $errorData,
            ];
        }

        return [
            'status' => 500,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'An unexpected error occurred',
            ],
        ];
    }
}

