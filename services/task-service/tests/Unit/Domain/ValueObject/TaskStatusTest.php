<?php

declare(strict_types=1);

namespace TaskService\Tests\Unit\Domain\ValueObject;

use TaskService\Domain\ValueObject\TaskStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function test_creates_pending_status(): void
    {
        $status = TaskStatus::pending();

        $this->assertEquals('pending', $status->getValue());
        $this->assertTrue($status->isPending());
    }

    public function test_creates_in_progress_status(): void
    {
        $status = TaskStatus::inProgress();

        $this->assertEquals('in_progress', $status->getValue());
        $this->assertTrue($status->isInProgress());
    }

    public function test_creates_done_status(): void
    {
        $status = TaskStatus::done();

        $this->assertEquals('done', $status->getValue());
        $this->assertTrue($status->isDone());
    }

    public function test_creates_from_string(): void
    {
        $status = TaskStatus::fromString('pending');

        $this->assertEquals('pending', $status->getValue());
    }

    public function test_throws_exception_for_invalid_status(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status "invalid"');

        TaskStatus::fromString('invalid');
    }

    public function test_equals_compares_statuses(): void
    {
        $status1 = TaskStatus::pending();
        $status2 = TaskStatus::pending();
        $status3 = TaskStatus::done();

        $this->assertTrue($status1->equals($status2));
        $this->assertFalse($status1->equals($status3));
    }

    public function test_to_string_returns_value(): void
    {
        $status = TaskStatus::pending();

        $this->assertEquals('pending', (string) $status);
    }
}
