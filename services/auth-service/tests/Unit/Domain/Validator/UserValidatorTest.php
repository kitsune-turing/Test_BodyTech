<?php

declare(strict_types=1);

namespace AuthService\Tests\Unit\Domain\Validator;

use AuthService\Domain\Validator\UserValidator;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    private UserValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UserValidator();
    }

    public function test_validates_valid_email(): void
    {
        $errors = $this->validator->validateEmail('test@example.com');

        $this->assertEmpty($errors);
    }

    public function test_fails_when_email_is_empty(): void
    {
        $errors = $this->validator->validateEmail('');

        $this->assertArrayHasKey('email', $errors);
        $this->assertContains('Email is required', $errors['email']);
    }

    public function test_fails_when_email_is_invalid(): void
    {
        $errors = $this->validator->validateEmail('invalid-email');

        $this->assertArrayHasKey('email', $errors);
        $this->assertContains('Invalid email format', $errors['email']);
    }

    public function test_validates_strong_password(): void
    {
        $errors = $this->validator->validatePassword('StrongP@ss123');

        $this->assertEmpty($errors);
    }

    public function test_fails_when_password_is_empty(): void
    {
        $errors = $this->validator->validatePassword('');

        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Password is required', $errors['password']);
    }

    public function test_fails_when_password_is_too_short(): void
    {
        $errors = $this->validator->validatePassword('Pass1!');

        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Password must be at least 8 characters long', $errors['password']);
    }

    public function test_fails_when_password_missing_uppercase(): void
    {
        $errors = $this->validator->validatePassword('password123!');

        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Password must contain at least one uppercase letter', $errors['password']);
    }

    public function test_fails_when_password_missing_lowercase(): void
    {
        $errors = $this->validator->validatePassword('PASSWORD123!');

        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Password must contain at least one lowercase letter', $errors['password']);
    }

    public function test_fails_when_password_missing_number(): void
    {
        $errors = $this->validator->validatePassword('Password!');

        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Password must contain at least one number', $errors['password']);
    }

    public function test_fails_when_password_missing_special_char(): void
    {
        $errors = $this->validator->validatePassword('Password123');

        $this->assertArrayHasKey('password', $errors);
        $this->assertContains('Password must contain at least one special character', $errors['password']);
    }

    public function test_validates_valid_registration(): void
    {
        $errors = $this->validator->validateRegistration([
            'email' => 'test@example.com',
            'password' => 'StrongP@ss123'
        ]);

        $this->assertEmpty($errors);
    }

    public function test_validates_valid_login(): void
    {
        $errors = $this->validator->validateLogin([
            'email' => 'test@example.com',
            'password' => 'anypassword'
        ]);

        $this->assertEmpty($errors);
    }

    public function test_fails_login_when_fields_are_empty(): void
    {
        $errors = $this->validator->validateLogin([
            'email' => '',
            'password' => ''
        ]);

        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }
}
