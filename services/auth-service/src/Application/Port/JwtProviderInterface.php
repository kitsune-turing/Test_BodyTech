<?php

declare(strict_types=1);

namespace AuthService\Application\Port;

/**
 * JWT Provider Port
 * Abstraction for JWT generation and validation
 */
interface JwtProviderInterface
{
    /**
     * Generate JWT token for user
     *
     * @return array ['token' => string, 'expires_in' => int, 'jti' => string]
     */
    public function generate(int $userId): array;

    /**
     * Validate and decode JWT token
     *
     * @return array Decoded payload ['sub' => int, 'iat' => int, 'exp' => int, 'jti' => string]
     * @throws \Exception if token is invalid
     */
    public function validate(string $token): array;

    /**
     * Extract JTI from token without full validation
     */
    public function extractJti(string $token): ?string;
}
