<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Persistence\Model;

use Phalcon\Mvc\Model;

class User extends Model
{
    public int $id;
    public string $email;
    public string $password;
    public ?string $name;
    public string $created_at;
    public string $updated_at;

    public function initialize(): void
    {
        $this->setSource('users');
        $this->setConnectionService('db');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
    }

    public function getSource(): string
    {
        return 'users';
    }

    public function columnMap(): array
    {
        return [
            'id' => 'id',
            'email' => 'email',
            'password' => 'password',
            'name' => 'name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
