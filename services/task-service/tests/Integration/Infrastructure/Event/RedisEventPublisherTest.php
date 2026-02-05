<?php

declare(strict_types=1);

namespace TaskService\Tests\Integration\Infrastructure\Event;

use TaskService\Domain\Event\TaskCreated;
use TaskService\Domain\Event\TaskUpdated;
use TaskService\Infrastructure\Event\RedisEventPublisher;
use PHPUnit\Framework\TestCase;

class RedisEventPublisherTest extends TestCase
{
    private $redis;
    private RedisEventPublisher $publisher;

    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available');
        }

        $this->redis = $this->createMock(\Redis::class);
        $this->publisher = new RedisEventPublisher($this->redis);
    }

    public function test_publishes_task_created_event(): void
    {
        $event = new TaskCreated(
            1,
            10,
            'Test Task',
            'pending',
            '2025-01-15T10:30:00Z'
        );

        $expectedMessage = json_encode($event->toArray());

        $this->redis->expects($this->once())
            ->method('publish')
            ->with('task-events', $expectedMessage);

        $this->publisher->publishTaskCreated($event);
    }

    public function test_publishes_task_updated_event(): void
    {
        $event = new TaskUpdated(
            1,
            10,
            'Updated Task',
            'done',
            '2025-01-15T11:30:00Z'
        );

        $expectedMessage = json_encode($event->toArray());

        $this->redis->expects($this->once())
            ->method('publish')
            ->with('task-events', $expectedMessage);

        $this->publisher->publishTaskUpdated($event);
    }

    public function test_event_message_contains_correct_structure(): void
    {
        $event = new TaskCreated(
            1,
            10,
            'Test Task',
            'pending',
            '2025-01-15T10:30:00Z'
        );

        $this->redis->expects($this->once())
            ->method('publish')
            ->with('task-events', $this->callback(function ($message) {
                $decoded = json_decode($message, true);

                return isset($decoded['event']) &&
                       $decoded['event'] === 'task.created' &&
                       isset($decoded['userId']) &&
                       isset($decoded['data']);
            }));

        $this->publisher->publishTaskCreated($event);
    }
}
