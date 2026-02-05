<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Security;

use AuthService\Application\Port\TokenBlacklistInterface;
use Redis;

/**
 * Redis Token Blacklist
 * Implements token revocation using Redis with automatic expiration
 */
final class RedisTokenBlacklist implements TokenBlacklistInterface
{
    private Redis $redis;
    private string $prefix = 'revoked:';

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function add(string $jti, int $exp): void
    {
        $ttl = max(0, $exp - time());

        if ($ttl > 0) {
            $this->redis->setex($this->prefix . $jti, $ttl, '1');
        }
    }

    public function isRevoked(string $jti): bool
    {
        return (bool) $this->redis->exists($this->prefix . $jti);
    }

    public function cleanup(): int
    {
        // Redis elimina automáticamente las claves expiradas mediante TTL.
        // Este método existe para cumplir con la interfaz,
        // pero no necesita ejecutar ninguna acción en Redis.
        return 0;
    }
}
