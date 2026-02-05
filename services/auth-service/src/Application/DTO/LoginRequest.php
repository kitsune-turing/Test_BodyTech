<?php

declare(strict_types=1);

namespace AuthService\Application\DTO;

/**
 * Login Request DTO
 */
final class LoginRequest
{
    public string $email;
    public string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['email'] ?? '',
            $data['password'] ?? ''
        );
    }
}
