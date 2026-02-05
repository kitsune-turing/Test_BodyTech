<?php

declare(strict_types=1);

namespace AuthService\Domain\Validator;

/**
 * User Validator
 * Validates user data according to business rules
 */
final class UserValidator
{
    /**
     * Validate email format
     */
    public function validateEmail(string $email): array
    {
        $errors = [];

        if (empty($email)) {
            $errors['email'][] = 'Email is required';
            return $errors;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid email format';
        }

        if (strlen($email) > 255) {
            $errors['email'][] = 'Email cannot exceed 255 characters';
        }

        return $errors;
    }

    /**
     * Validate password strength
     * Requirements:
     * - Minimum 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     * - At least one special character
     */
    public function validatePassword(string $password): array
    {
        $errors = [];

        if (empty($password)) {
            $errors['password'][] = 'Password is required';
            return $errors;
        }

        if (strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors['password'][] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors['password'][] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors['password'][] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'][] = 'Password must contain at least one special character';
        }

        return $errors;
    }

    /**
     * Validate registration data
     */
    public function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'][] = 'Name is required';
        } else if (strlen($data['name']) < 3) {
            $errors['name'][] = 'Name must be at least 3 characters';
        } else if (strlen($data['name']) > 255) {
            $errors['name'][] = 'Name cannot exceed 255 characters';
        }

        $emailErrors = $this->validateEmail($data['email'] ?? '');
        $passwordErrors = $this->validatePassword($data['password'] ?? '');

        return array_merge($errors, $emailErrors, $passwordErrors);
    }

    /**
     * Validate login data
     */
    public function validateLogin(array $data): array
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'][] = 'Email is required';
        }

        if (empty($data['password'])) {
            $errors['password'][] = 'Password is required';
        }

        return $errors;
    }
}
