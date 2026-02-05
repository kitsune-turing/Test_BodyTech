<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Http\Middleware;

use AuthService\Application\Port\JwtProviderInterface;
use AuthService\Application\Port\TokenBlacklistInterface;

/**
 * Auth Middleware
 * Validates JWT and checks blacklist
 */
final class AuthMiddleware
{
    private JwtProviderInterface $jwtProvider;
    private TokenBlacklistInterface $blacklist;

    public function __construct(
        JwtProviderInterface $jwtProvider,
        TokenBlacklistInterface $blacklist
    ) {
        $this->jwtProvider = $jwtProvider;
        $this->blacklist = $blacklist;
    }

    public function handle(string $authHeader): array
    {
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return [
                'success' => false,
                'status' => 401,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Missing or invalid authorization header',
                ],
            ];
        }

        $token = $matches[1];

        try {
            $payload = $this->jwtProvider->validate($token);

            if ($this->blacklist->isRevoked($payload['jti'])) {
                return [
                    'success' => false,
                    'status' => 401,
                    'error' => [
                        'code' => 'TOKEN_REVOKED',
                        'message' => 'Token has been revoked',
                    ],
                ];
            }

            return [
                'success' => true,
                'user_id' => $payload['sub'],
                'payload' => $payload,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 401,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }
}
