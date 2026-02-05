<?php

declare(strict_types=1);

namespace AuthService\Application\DTO;

/**
 * Authentication Response DTO
 */
final class AuthResponse
{
    public string $token;
    public int $expiresIn;
    public array $user;

    public function __construct(string $token, int $expiresIn, array $user)
    {
        $this->token = $token;
        $this->expiresIn = $expiresIn;
        $this->user = $user;
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'expires_in' => $this->expiresIn,
            'user' => $this->user,
        ];
    }
}
