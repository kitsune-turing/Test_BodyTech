<?php

declare(strict_types=1);

namespace TaskService\Infrastructure\Cache;

use Phalcon\Cache\Adapter\Redis as PhalconRedis;
use Phalcon\Storage\SerializerFactory;

class PhalconCacheAdapter
{
    private PhalconRedis $cache;

    public function __construct(string $host = 'redis', int $port = 6379, string $prefix = 'task_service:')
    {
        $serializerFactory = new SerializerFactory();
        
        $this->cache = new PhalconRedis($serializerFactory, [
            'host' => $host,
            'port' => $port,
            'index' => 0,
            'persistent' => false,
            'auth' => '',
            'socket' => '',
            'prefix' => $prefix,
            'serializer' => 'Json',
            'lifetime' => 300, // 5 minutes default
        ]);
    }

    public function get(string $key)
    {
        return $this->cache->get($key);
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }
}
