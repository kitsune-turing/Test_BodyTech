<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Persistence\Model;

use Phalcon\Mvc\Model;

/**
 * User Model (Phalcon ORM)
 * Maps to users table
 */
class UserModel extends Model
{
    public ?int $id = null;
    public string $email;
    public string $password_hash;
    public string $created_at;
    public string $updated_at;

    public function initialize(): void
    {
        $this->setSource('users');
    }

    public function getSource(): string
    {
        return 'users';
    }
}
