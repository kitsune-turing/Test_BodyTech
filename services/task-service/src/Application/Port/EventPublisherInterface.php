<?php

declare(strict_types=1);

namespace TaskService\Application\Port;

use TaskService\Domain\Event\TaskCreated;
use TaskService\Domain\Event\TaskUpdated;

/**
 * Event Publisher Port
 * Abstraction for publishing domain events
 */
interface EventPublisherInterface
{
    /**
     * Publish TaskCreated event
     */
    public function publishTaskCreated(TaskCreated $event): void;

    /**
     * Publish TaskUpdated event
     */
    public function publishTaskUpdated(TaskUpdated $event): void;
}
