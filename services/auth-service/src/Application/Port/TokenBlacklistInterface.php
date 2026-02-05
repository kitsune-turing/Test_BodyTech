<?php

declare(strict_types=1);

namespace AuthService\Application\Port;

/**
 * Token Blacklist Port
 * Abstraction for token revocation storage
 */
interface TokenBlacklistInterface
{
    /**
     * Add token to blacklist
     *
     * @param string $jti JWT ID claim
     * @param int $exp Expiration timestamp
     */
    public function add(string $jti, int $exp): void;

    /**
     * Check if token is revoked
     */
    public function isRevoked(string $jti): bool;

    /**
     * Remove expired tokens (cleanup)
     */
    public function cleanup(): int;
}
