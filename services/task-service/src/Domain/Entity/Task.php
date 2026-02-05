<?php

declare(strict_types=1);

namespace TaskService\Domain\Entity;

use TaskService\Domain\ValueObject\TaskStatus;
use TaskService\Domain\ValueObject\TaskTitle;
use DateTimeImmutable;

/**
 * Task Entity
 * Represents a user task with status tracking
 */
final class Task
{
    private ?int $id;
    private int $userId;
    private TaskTitle $title;
    private ?string $description;
    private TaskStatus $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        int $userId,
        TaskTitle $title,
        ?string $description,
        TaskStatus $status,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public static function create(
        int $userId,
        TaskTitle $title,
        ?string $description,
        TaskStatus $status
    ): self {
        return new self(
            null,
            $userId,
            $title,
            $description,
            $status
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTitle(): TaskTitle
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function belongsToUser(int $userId): bool
    {
        return $this->userId === $userId;
    }

    public function update(
        TaskTitle $title,
        ?string $description,
        TaskStatus $status
    ): void {
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeStatus(TaskStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title->getValue(),
            'description' => $this->description,
            'status' => $this->status->getValue(),
            'created_at' => $this->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $this->updatedAt->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
