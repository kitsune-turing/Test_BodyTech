<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Cache;

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
        string $keyPrefix = 'task_service:'
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
    public function getDecoded(string $key)
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
     * Delete multiple keys by pattern
     */
    public function deletePattern(string $pattern): int
    {
        try {
            $pattern = $this->prefixKey($pattern);
            $keys = $this->redis->keys($pattern);
            if (empty($keys)) {
                return 0;
            }
            return $this->redis->del(...$keys);
        } catch (\Exception $e) {
            error_log("Cache delete pattern error: " . $e->getMessage());
            return 0;
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
     * Clear entire cache
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
     * Increment a counter value
     */
    public function increment(string $key, int $value = 1): int
    {
        try {
            return $this->redis->incrBy($this->prefixKey($key), $value);
        } catch (\Exception $e) {
            error_log("Cache increment error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Prefix a cache key
     */
    private function prefixKey(string $key): string
    {
        return $this->keyPrefix . $key;
    }

    /**
     * Get cache key for user tasks list
     */
    public static function getUserTasksKey(int $userId): string
    {
        return "user_tasks:{$userId}";
    }

    /**
     * Get cache key for single task
     */
    public static function getTaskKey(int $taskId): string
    {
        return "task:{$taskId}";
    }

    /**
     * Get cache key for user-related data
     */
    public static function getUserKey(int $userId): string
    {
        return "user:{$userId}";
    }
}
