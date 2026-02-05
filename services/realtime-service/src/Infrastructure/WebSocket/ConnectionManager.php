<?php

declare(strict_types=1);

namespace RealtimeService\Infrastructure\WebSocket;

/**
 * Connection Manager
 * Manages WebSocket connections mapping fd → userId
 */
final class ConnectionManager
{
    private array $connections = [];

    public function add(int $fd, int $userId): void
    {
        $this->connections[$fd] = $userId;
    }

    public function remove(int $fd): void
    {
        if (isset($this->connections[$fd])) {
            unset($this->connections[$fd]);
        }
    }

    public function getUserId(int $fd): ?int
    {
        return $this->connections[$fd] ?? null;
    }

    public function getConnectionsByUserId(int $userId): array
    {
        return array_keys(array_filter(
            $this->connections,
            fn($uid) => $uid === $userId
        ));
    }

    public function getAllConnections(): array
    {
        return $this->connections;
    }

    public function count(): int
    {
        return count($this->connections);
    }
}
