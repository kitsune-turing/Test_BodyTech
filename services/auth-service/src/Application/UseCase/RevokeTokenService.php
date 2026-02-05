<?php

declare(strict_types=1);

namespace AuthService\Application\UseCase;

use AuthService\Application\Port\JwtProviderInterface;
use AuthService\Application\Port\TokenBlacklistInterface;
use Exception;

/**
 * Revoke Token Use Case
 * Handles JWT token revocation (logout)
 */
final class RevokeTokenService
{
    private TokenBlacklistInterface $blacklist;
    private JwtProviderInterface $jwtProvider;

    public function __construct(
        TokenBlacklistInterface $blacklist,
        JwtProviderInterface $jwtProvider
    ) {
        $this->blacklist = $blacklist;
        $this->jwtProvider = $jwtProvider;
    }

    /**
     * @throws Exception
     */
    public function execute(string $token): void
    {
        try {
            // Valida y decodifica el token JWT
            $payload = $this->jwtProvider->validate($token);

            // Extrae el jti (ID del token) y exp (fecha de expiración)
            $jti = $payload['jti'] ?? null;
            $exp = $payload['exp'] ?? null;

            if (!$jti || !$exp) {
                throw new Exception('Invalid token payload');
            }

            // Agrega el token a la lista negra en Redis
            $this->blacklist->add($jti, $exp);

        } catch (Exception $e) {
            throw new Exception(json_encode([
                'code' => 'INVALID_TOKEN',
                'message' => 'Cannot revoke invalid token',
                'details' => $e->getMessage(),
            ]));
        }
    }
}
