<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Persistence;

use TaskService\Domain\Entity\Task;
use TaskService\Domain\Repository\TaskRepositoryInterface;
use TaskService\Domain\ValueObject\TaskStatus;
use TaskService\Domain\ValueObject\TaskTitle;
use PDO;

/**
 * Phalcon Task Repository
 * Implements persistence using PDO
 */
final class PhalconTaskRepository implements TaskRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Task $task): Task
    {
        if ($task->getId() === null) {
            return $this->insert($task);
        }

        return $this->update($task);
    }

    public function findById(int $id): ?Task
    {
        $stmt = $this->pdo->prepare('
            SELECT id, user_id, title, description, status, created_at, updated_at
            FROM tasks
            WHERE id = :id
        ');

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapToEntity($row);
    }

    public function findByUserId(int $userId, ?TaskStatus $status = null): array
    {
        $sql = '
            SELECT id, user_id, title, description, status, created_at, updated_at
            FROM tasks
            WHERE user_id = :user_id
        ';

        $params = ['user_id' => $userId];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status->getValue();
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = $this->mapToEntity($row);
        }

        return $tasks;
    }

    public function findByUserIdPaginated(
        int $userId,
        ?TaskStatus $status = null,
        ?string $search = null,
        int $limit = 20,
        int $offset = 0,
        string $sortBy = 'created_at',
        string $sortOrder = 'DESC'
    ): array {
        $allowedSortFields = ['created_at', 'updated_at', 'title', 'status'];
        $sortBy = in_array($sortBy, $allowedSortFields, true) ? $sortBy : 'created_at';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = '
            SELECT id, user_id, title, description, status, created_at, updated_at
            FROM tasks
            WHERE user_id = :user_id
        ';

        $params = ['user_id' => $userId];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status->getValue();
        }

        if ($search !== null && $search !== '') {
            $sql .= ' AND (title ILIKE :search OR description ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY {$sortBy} {$sortOrder}";
        $sql .= ' LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = $this->mapToEntity($row);
        }

        return $tasks;
    }

    public function countByUserId(int $userId, ?TaskStatus $status = null, ?string $search = null): int
    {
        $sql = '
            SELECT COUNT(*) as count
            FROM tasks
            WHERE user_id = :user_id
        ';

        $params = ['user_id' => $userId];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status->getValue();
        }

        if ($search !== null && $search !== '') {
            $sql .= ' AND (title ILIKE :search OR description ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function countByUserAndStatus(int $userId, TaskStatus $status): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count
            FROM tasks
            WHERE user_id = :user_id AND status = :status
        ');

        $stmt->execute([
            'user_id' => $userId,
            'status' => $status->getValue(),
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    private function insert(Task $task): Task
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO tasks (user_id, title, description, status, created_at, updated_at)
            VALUES (:user_id, :title, :description, :status::task_status, NOW(), NOW())
            RETURNING id, user_id, title, description, status, created_at, updated_at
        ');

        $stmt->execute([
            'user_id' => $task->getUserId(),
            'title' => $task->getTitle()->getValue(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus()->getValue(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this->mapToEntity($row);
    }

    private function update(Task $task): Task
    {
        $stmt = $this->pdo->prepare('
            UPDATE tasks
            SET title = :title,
                description = :description,
                status = :status::task_status,
                updated_at = NOW()
            WHERE id = :id
            RETURNING id, user_id, title, description, status, created_at, updated_at
        ');

        $stmt->execute([
            'id' => $task->getId(),
            'title' => $task->getTitle()->getValue(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus()->getValue(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this->mapToEntity($row);
    }

    private function mapToEntity(array $row): Task
    {
        return new Task(
            (int) $row['id'],
            (int) $row['user_id'],
            new TaskTitle($row['title']),
            $row['description'],
            TaskStatus::fromString($row['status']),
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }
}
