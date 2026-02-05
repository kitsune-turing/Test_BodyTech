<?php

declare(strict_types=1);

namespace AuthService\Domain\Repository;

use AuthService\Domain\Entity\User;
use AuthService\Domain\ValueObject\Email;

/**
 * User Repository Interface
 * Port for persistence operations
 */
interface UserRepositoryInterface
{
    /**
     * Save a user (create or update)
     */
    public function save(User $user): User;

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User;

    /**
     * Find user by email
     */
    public function findByEmail(Email $email): ?User;

    /**
     * Check if email exists
     */
    public function emailExists(Email $email): bool;

    /**
     * Delete user by ID
     */
    public function delete(int $id): bool;
}
