<?php

return [
    'app' => [
        'env' => getenv('APP_ENV') ?: 'development',
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '5432',
        'dbname' => getenv('DB_NAME') ?: 'task_db',
        'username' => getenv('DB_USER') ?: 'task_user',
        'password' => getenv('DB_PASS') ?: 'task_pass',
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: 'localhost',
        'port' => (int) (getenv('REDIS_PORT') ?: 6379),
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'change-this-secret-in-production',
    ],
    'rate_limit' => [
        'enabled' => getenv('RATE_LIMIT_ENABLED') !== 'false',
        'default_limit' => (int) (getenv('RATE_LIMIT_DEFAULT') ?: 100),
        'window' => (int) (getenv('RATE_LIMIT_WINDOW') ?: 60),
    ],
];
