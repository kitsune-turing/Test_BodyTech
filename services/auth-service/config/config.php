<?php

return [
    'app' => [
        'env' => getenv('APP_ENV') ?: 'development',
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '5432',
        'dbname' => getenv('DB_NAME') ?: 'auth_db',
        'username' => getenv('DB_USER') ?: 'auth_user',
        'password' => getenv('DB_PASS') ?: 'auth_pass',
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: 'localhost',
        'port' => (int) (getenv('REDIS_PORT') ?: 6379),
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'change-this-secret-in-production',
        'exp' => (int) (getenv('JWT_EXP') ?: 86400), // 24 hours for development
    ],
    'argon2' => [
        'memory_cost' => (int) (getenv('ARGON2_MEMORY_COST') ?: 65536),
        'time_cost' => (int) (getenv('ARGON2_TIME_COST') ?: 4),
        'threads' => (int) (getenv('ARGON2_THREADS') ?: 2),
    ],
    'rate_limit' => [
        'enabled' => getenv('RATE_LIMIT_ENABLED') !== 'false',
        'default_limit' => (int) (getenv('RATE_LIMIT_DEFAULT') ?: 100),
        'window' => (int) (getenv('RATE_LIMIT_WINDOW') ?: 60),
    ],
];
