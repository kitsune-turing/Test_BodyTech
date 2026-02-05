<?php

declare(strict_types=1);

namespace TaskService\Domain\ValueObject;

use InvalidArgumentException;

/**
 * TaskStatus Value Object
 * Enum-like value object for task status
 */
final class TaskStatus
{
    public const PENDING = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const DONE = 'done';

    private const VALID_STATUSES = [
        self::PENDING,
        self::IN_PROGRESS,
        self::DONE,
    ];

    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    private function validate(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid status "%s". Valid statuses are: %s',
                    $status,
                    implode(', ', self::VALID_STATUSES)
                )
            );
        }
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function inProgress(): self
    {
        return new self(self::IN_PROGRESS);
    }

    public static function done(): self
    {
        return new self(self::DONE);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->value === self::IN_PROGRESS;
    }

    public function isDone(): bool
    {
        return $this->value === self::DONE;
    }

    public function equals(TaskStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
