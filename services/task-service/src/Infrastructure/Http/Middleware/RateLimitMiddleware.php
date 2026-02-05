<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Http\Middleware;

/**
 * Rate Limit Middleware
 * Implements sliding window rate limiting using Redis
 */
final class RateLimitMiddleware
{
    private \Redis $redis;
    private int $limit;
    private int $window;
    private bool $enabled;

    public function __construct(\Redis $redis, array $config = [])
    {
        $this->redis = $redis;
        $this->limit = $config['limit'] ?? 100;
        $this->window = $config['window'] ?? 60;
        $this->enabled = $config['enabled'] ?? true;
    }

    /**
     * Check if request is within rate limit
     *
     * @param string $identifier Unique identifier (usually user_id)
     * @param string $endpoint Endpoint being accessed
     * @return array Response with success status and headers
     */
    public function handle(string $identifier, string $endpoint): array
    {
        if (!$this->enabled) {
            return [
                'success' => true,
                'headers' => $this->getHeaders($this->limit, $this->limit, time() + $this->window),
            ];
        }

        $key = $this->getKey($identifier, $endpoint);
        $now = time();
        // Obtiene el contador actual de solicitudes
        $current = (int) $this->redis->get($key);

        if ($current >= $this->limit) {
            $ttl = $this->redis->ttl($key);
            $ttl = $ttl > 0 ? $ttl : $this->window;
            $resetTime = $now + max(1, $ttl);

            return [
                'success' => false,
                'status' => 429,
                'headers' => $this->getHeaders($this->limit, 0, $resetTime),
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => max(1, $ttl),
                ],
            ];
        }

        if ($current === 0) {
            $this->redis->setex($key, $this->window, '1');
        } else {
            $newCount = $current + 1;
            $ttl = $this->redis->ttl($key);
            $ttl = $ttl > 0 ? $ttl : $this->window;
            $this->redis->setex($key, max(1, $ttl), (string) $newCount);
        }

        $remaining = $this->limit - ($current + 1);
        $ttl = $this->redis->ttl($key);
        $ttl = $ttl > 0 ? $ttl : $this->window;
        $resetTime = $now + max(1, $ttl);

        return [
            'success' => true,
            'headers' => $this->getHeaders($this->limit, $remaining, $resetTime),
        ];
    }

    /**
     * Generate rate limit key
     */
    private function getKey(string $identifier, string $endpoint): string
    {
        $cleanEndpoint = preg_replace('/[^a-zA-Z0-9_-]/', '_', $endpoint);
        return "rate_limit:{$identifier}:{$cleanEndpoint}";
    }

    /**
     * Generate rate limit headers
     */
    private function getHeaders(int $limit, int $remaining, int $reset): array
    {
        return [
            'X-RateLimit-Limit' => (string) $limit,
            'X-RateLimit-Remaining' => (string) max(0, $remaining),
            'X-RateLimit-Reset' => (string) $reset,
        ];
    }

    /**
     * Get custom limit for specific endpoint
     */
    public static function getLimitForEndpoint(string $method, string $path): int
    {
        $limits = [
            'POST:/v1/api/tasks' => 20,
            'GET:/v1/api/tasks' => 60,
            'PUT:/v1/api/tasks' => 30,
            'DELETE:/v1/api/tasks' => 30,
            'GET:/v1/api/tasks/export' => 10,
        ];

        // Match exact path or partial path
        foreach ($limits as $key => $limit) {
            if (str_starts_with($key, $method . ':') && str_contains($path, explode(':', $key)[1])) {
                return $limit;
            }
        }

        return 100; // Default limit
    }
}
