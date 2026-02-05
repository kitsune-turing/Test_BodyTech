<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Security;

use AuthService\Application\Port\PasswordHasherInterface;
use AuthService\Domain\ValueObject\HashedPassword;

/**
 * Argon2id Password Hasher
 * Implements secure password hashing using Argon2id algorithm
 */
final class Argon2PasswordHasher implements PasswordHasherInterface
{
    private int $memoryCost;
    private int $timeCost;
    private int $threads;

    public function __construct(
        int $memoryCost = 65536,
        int $timeCost = 4,
        int $threads = 2
    ) {
        $this->memoryCost = $memoryCost;
        $this->timeCost = $timeCost;
        $this->threads = $threads;
    }

    public function hash(string $plainPassword): HashedPassword
    {
        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => $this->memoryCost,
            'time_cost' => $this->timeCost,
            'threads' => $this->threads,
        ]);

        if ($hash === false) {
            throw new \RuntimeException('Failed to hash password');
        }

        return HashedPassword::fromHash($hash);
    }

    public function verify(string $plainPassword, HashedPassword $hash): bool
    {
        return password_verify($plainPassword, $hash->getHash());
    }
}
