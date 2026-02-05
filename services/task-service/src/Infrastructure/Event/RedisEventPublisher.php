<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Event;

use TaskService\Application\Port\EventPublisherInterface;
use TaskService\Domain\Event\TaskCreated;
use TaskService\Domain\Event\TaskUpdated;
use Redis;

/**
 * Redis Event Publisher
 * Publishes domain events to Redis Pub/Sub
 */
final class RedisEventPublisher implements EventPublisherInterface
{
    private Redis $redis;
    private string $channel = 'task-events';

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function publishTaskCreated(TaskCreated $event): void
    {
        $message = json_encode($event->toArray());
        $this->redis->publish($this->channel, $message);
    }

    public function publishTaskUpdated(TaskUpdated $event): void
    {
        $message = json_encode($event->toArray());
        $this->redis->publish($this->channel, $message);
    }
}
