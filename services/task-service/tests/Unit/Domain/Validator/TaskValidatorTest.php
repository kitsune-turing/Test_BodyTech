<?php

declare(strict_types=1);

namespace TaskService\Tests\Unit\Domain\Validator;

use TaskService\Domain\Validator\TaskValidator;
use PHPUnit\Framework\TestCase;

class TaskValidatorTest extends TestCase
{
    private TaskValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TaskValidator();
    }

    public function test_validates_valid_task(): void
    {
        $data = [
            'title' => 'Comprar leche',
            'description' => 'Leche desnatada',
            'status' => 'pending'
        ];

        $errors = $this->validator->validate($data);
        $this->assertEmpty($errors);
    }

    public function test_fails_when_title_is_empty(): void
    {
        $data = [
            'title' => '',
            'status' => 'pending'
        ];

        $errors = $this->validator->validate($data);
        $this->assertArrayHasKey('title', $errors);
    }

    public function test_fails_when_title_is_too_long(): void
    {
        $data = [
            'title' => str_repeat('a', 256),
            'status' => 'pending'
        ];

        $errors = $this->validator->validate($data);
        $this->assertArrayHasKey('title', $errors);
    }

    public function test_fails_when_description_is_too_long(): void
    {
        $data = [
            'title' => 'Test',
            'description' => str_repeat('a', 1001),
            'status' => 'pending'
        ];

        $errors = $this->validator->validate($data);
        $this->assertArrayHasKey('description', $errors);
    }

    public function test_fails_when_status_is_invalid(): void
    {
        $data = [
            'title' => 'Test',
            'status' => 'invalid_status'
        ];

        $errors = $this->validator->validate($data);
        $this->assertArrayHasKey('status', $errors);
    }

    public function test_validates_filters_with_valid_status(): void
    {
        $filters = ['status' => 'pending'];

        $errors = $this->validator->validateFilters($filters);
        $this->assertEmpty($errors);
    }

    public function test_fails_filters_with_invalid_status(): void
    {
        $filters = ['status' => 'invalid'];

        $errors = $this->validator->validateFilters($filters);
        $this->assertArrayHasKey('status', $errors);
    }
}
