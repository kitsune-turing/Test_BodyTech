<?php

declare(strict_types=1);

namespace AuthService\Infrastructure\Cache;

use Redis;

/**
 * Redis Cache Manager
 * Handles caching operations using Redis
 */
final class RedisCacheManager
{
    private Redis $redis;
    private int $defaultTtl;
    private string $keyPrefix;

    public function __construct(
        Redis $redis,
        int $defaultTtl = 300,
        string $keyPrefix = 'auth_service:'
    ) {
        $this->redis = $redis;
        $this->defaultTtl = $defaultTtl;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Get value from cache
     */
    public function get(string $key): ?string
    {
        try {
            $value = $this->redis->get($this->prefixKey($key));
            return $value !== false ? $value : null;
        } catch (\Exception $e) {
            error_log("Cache get error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get deserialized value from cache
     */
    public function getDecoded(string $key): mixed
    {
        $value = $this->get($key);
        return $value !== null ? json_decode($value, true) : null;
    }

    /**
     * Set value in cache
     */
    public function set(string $key, string $value, ?int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->defaultTtl;
            return $this->redis->setex(
                $this->prefixKey($key),
                $ttl,
                $value
            );
        } catch (\Exception $e) {
            error_log("Cache set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set encoded value in cache
     */
    public function setEncoded(string $key, $value, ?int $ttl = null): bool
    {
        return $this->set($key, json_encode($value), $ttl);
    }

    /**
     * Delete value from cache
     */
    public function delete(string $key): bool
    {
        try {
            return (bool)$this->redis->del($this->prefixKey($key));
        } catch (\Exception $e) {
            error_log("Cache delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        try {
            return (bool)$this->redis->exists($this->prefixKey($key));
        } catch (\Exception $e) {
            error_log("Cache exists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Flush entire cache
     */
    public function flush(): bool
    {
        try {
            $pattern = $this->prefixKey('*');
            $keys = $this->redis->keys($pattern);
            if (empty($keys)) {
                return true;
            }
            return (bool)$this->redis->del(...$keys);
        } catch (\Exception $e) {
            error_log("Cache flush error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cache key for user profile
     */
    public static function getUserKey(int $userId): string
    {
        return "user:{$userId}";
    }

    /**
     * Get cache key for user email lookup
     */
    public static function getUserEmailKey(string $email): string
    {
        return "user_email:" . md5($email);
    }

    /**
     * Prefix a cache key
     */
    private function prefixKey(string $key): string
    {
        return $this->keyPrefix . $key;
    }
}
