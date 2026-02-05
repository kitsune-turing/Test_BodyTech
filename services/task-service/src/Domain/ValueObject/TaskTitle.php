<?php

declare(strict_types=1);

namespace TaskService\Domain\ValueObject;

use InvalidArgumentException;

/**
 * TaskTitle Value Object
 * Ensures title validity (length constraints)
 */
final class TaskTitle
{
    private const MIN_LENGTH = 1;
    private const MAX_LENGTH = 255;

    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = trim($value);
    }

    private function validate(string $title): void
    {
        $trimmed = trim($title);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('Title cannot be empty');
        }

        if (strlen($trimmed) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Title must be at least %d character(s)', self::MIN_LENGTH)
            );
        }

        if (strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Title cannot exceed %d characters', self::MAX_LENGTH)
            );
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
