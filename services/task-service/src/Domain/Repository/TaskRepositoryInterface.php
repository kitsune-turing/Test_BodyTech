<?php

declare(strict_types=1);

namespace TaskService\Domain\Repository;

use TaskService\Domain\Entity\Task;
use TaskService\Domain\ValueObject\TaskStatus;

/**
 * Task Repository Interface
 * Port for persistence operations
 */
interface TaskRepositoryInterface
{
    /**
     * Save a task (create or update)
     */
    public function save(Task $task): Task;

    /**
     * Find task by ID
     */
    public function findById(int $id): ?Task;

    /**
     * Find tasks by user ID
     *
     * @return Task[]
     */
    public function findByUserId(int $userId, ?TaskStatus $status = null): array;

    /**
     * Find tasks by user ID with pagination and filters
     *
     * @param int $userId User ID
     * @param TaskStatus|null $status Filter by status
     * @param string|null $search Search in title and description
     * @param int $limit Items per page
     * @param int $offset Offset for pagination
     * @param string $sortBy Field to sort by
     * @param string $sortOrder Sort order (ASC|DESC)
     * @return Task[]
     */
    public function findByUserIdPaginated(
        int $userId,
        ?TaskStatus $status = null,
        ?string $search = null,
        int $limit = 20,
        int $offset = 0,
        string $sortBy = 'created_at',
        string $sortOrder = 'DESC'
    ): array;

    /**
     * Count tasks by user ID with filters
     *
     * @param int $userId User ID
     * @param TaskStatus|null $status Filter by status
     * @param string|null $search Search in title and description
     * @return int
     */
    public function countByUserId(int $userId, ?TaskStatus $status = null, ?string $search = null): int;

    /**
     * Delete task by ID
     */
    public function delete(int $id): bool;

    /**
     * Count tasks by user and status
     */
    public function countByUserAndStatus(int $userId, TaskStatus $status): int;
}
