<?php

declare(strict_types=1);

namespace AuthService\Application\Port;

use AuthService\Domain\ValueObject\HashedPassword;

/**
 * Password Hasher Port
 * Abstraction for password hashing algorithm
 */
interface PasswordHasherInterface
{
    /**
     * Hash a plain text password
     */
    public function hash(string $plainPassword): HashedPassword;

    /**
     * Verify a plain text password against a hash
     */
    public function verify(string $plainPassword, HashedPassword $hash): bool;
}
