<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Persistence\Model;

use Phalcon\Mvc\Model;

class Task extends Model
{
    public int $id;
    public int $user_id;
    public string $title;
    public ?string $description;
    public string $status;
    public string $created_at;
    public string $updated_at;

    public function initialize(): void
    {
        $this->setSource('tasks');
        $this->setConnectionService('db');
        $this->keepSnapshots(true);
        $this->useDynamicUpdate(true);
    }

    public function getSource(): string
    {
        return 'tasks';
    }

    public function columnMap(): array
    {
        return [
            'id' => 'id',
            'user_id' => 'user_id',
            'title' => 'title',
            'description' => 'description',
            'status' => 'status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
