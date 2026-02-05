<?php

declare(strict_types=1);

namespace AuthService\Domain\ValueObject;

use InvalidArgumentException;

/**
 * HashedPassword Value Object
 * Represents a securely hashed password
 * NEVER stores plain text passwords
 */
final class HashedPassword
{
    private string $hash;

    private function __construct(string $hash)
    {
        if (empty($hash)) {
            throw new InvalidArgumentException('Password hash cannot be empty');
        }

        $this->hash = $hash;
    }

    /**
     * Create from already hashed password
     */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
