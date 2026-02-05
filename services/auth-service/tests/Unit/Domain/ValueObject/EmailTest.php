<?php

declare(strict_types=1);

namespace AuthService\Tests\Unit\Domain\ValueObject;

use AuthService\Domain\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_creates_valid_email(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_normalizes_email_to_lowercase(): void
    {
        $email = new Email('Test@EXAMPLE.COM');

        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_trims_whitespace(): void
    {
        $email = new Email('  test@example.com  ');

        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_throws_exception_for_empty_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        new Email('');
    }

    public function test_throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        new Email('invalid-email');
    }

    public function test_throws_exception_for_too_long_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot exceed 255 characters');

        $longEmail = str_repeat('a', 250) . '@example.com';
        new Email($longEmail);
    }

    public function test_equals_compares_two_emails(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('other@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function test_to_string_returns_value(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', (string) $email);
    }
}
