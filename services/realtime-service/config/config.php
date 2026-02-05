<?php

return [
    'app' => [
        'env' => getenv('APP_ENV') ?: 'development',
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: 'localhost',
        'port' => (int) (getenv('REDIS_PORT') ?: 6379),
        'channel' => 'task-events',
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'change-this-secret-in-production',
    ],
    'websocket' => [
        'host' => getenv('WS_HOST') ?: '0.0.0.0',
        'port' => (int) (getenv('WS_PORT') ?: 9501),
    ],
];
