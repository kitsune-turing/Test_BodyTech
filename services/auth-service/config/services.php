<?php

/**
 * Phalcon 4.x Dependency Injection Container
 */

use Phalcon\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Postgresql;
use AuthService\Application\Port\JwtProviderInterface;
use AuthService\Application\Port\PasswordHasherInterface;
use AuthService\Application\Port\TokenBlacklistInterface;
use AuthService\Application\UseCase\AuthenticateUserService;
use AuthService\Application\UseCase\RegisterUserService;
use AuthService\Application\UseCase\RevokeTokenService;
use AuthService\Domain\Repository\UserRepositoryInterface;
use AuthService\Domain\Validator\UserValidator;
use AuthService\Infrastructure\Cache\RedisCacheManager;
use AuthService\Infrastructure\Http\Controller\AuthController;
use AuthService\Infrastructure\Http\Middleware\AuthMiddleware;
use AuthService\Infrastructure\Http\Middleware\RateLimitMiddleware;
use AuthService\Infrastructure\Persistence\PhalconUserRepository;
use AuthService\Infrastructure\Security\Argon2PasswordHasher;
use AuthService\Infrastructure\Security\FirebaseJwtProvider;
use AuthService\Infrastructure\Security\RedisTokenBlacklist;

$config = require __DIR__ . '/config.php';

// Get global DI container (set by index.php)
$di = Di::getDefault();

// Phalcon Database Connection
$di->setShared('db', function () use ($config) {
    return new Postgresql([
        'host' => $config['database']['host'],
        'port' => $config['database']['port'],
        'dbname' => $config['database']['dbname'],
        'username' => $config['database']['username'],
        'password' => $config['database']['password'],
    ]);
});

// PDO Connection (for compatibility with existing repositories)
$di->setShared('pdo', function () use ($config) {
    try {
        $pdo = new PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
            $config['database']['host'],
            $config['database']['port'],
            $config['database']['dbname']
        ),
        $config['database']['username'],
        $config['database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    return $pdo;
} catch (PDOException $e) {
    throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
}
});

// Redis Connection
$di->setShared('redis', function () use ($config) {
    try {
        $redis = new Redis();
        $redis->connect($config['redis']['host'], $config['redis']['port']);
        return $redis;
    } catch (RedisException $e) {
        throw new RuntimeException('Redis connection failed: ' . $e->getMessage(), 0, $e);
    }
});

// Cache Manager
$di->setShared('cacheManager', function () use ($di) {
    return new RedisCacheManager($di->get('redis'), 300, 'auth_service:');
});

// Ports Implementations
$di->setShared(PasswordHasherInterface::class, function () use ($config) {
    return new Argon2PasswordHasher(
        $config['argon2']['memory_cost'],
        $config['argon2']['time_cost'],
        $config['argon2']['threads']
    );
});

$di->setShared(JwtProviderInterface::class, function () use ($config) {
    return new FirebaseJwtProvider(
        $config['jwt']['secret'],
        $config['jwt']['exp']
    );
});

$di->setShared(TokenBlacklistInterface::class, function () use ($di) {
    return new RedisTokenBlacklist($di->get('redis'));
});

$di->setShared(UserRepositoryInterface::class, function () use ($di) {
    return new PhalconUserRepository($di->get('pdo'));
});

// Validators
$di->setShared(UserValidator::class, function () {
    return new UserValidator();
});

// Use Cases
$di->setShared(RegisterUserService::class, function () use ($di) {
    return new RegisterUserService(
        $di->get(UserRepositoryInterface::class),
        $di->get(PasswordHasherInterface::class),
        $di->get(UserValidator::class)
    );
});

$di->setShared(AuthenticateUserService::class, function () use ($di) {
    return new AuthenticateUserService(
        $di->get(UserRepositoryInterface::class),
        $di->get(PasswordHasherInterface::class),
        $di->get(JwtProviderInterface::class),
        $di->get(UserValidator::class)
    );
});

$di->setShared(RevokeTokenService::class, function () use ($di) {
    return new RevokeTokenService(
        $di->get(TokenBlacklistInterface::class),
        $di->get(JwtProviderInterface::class)
    );
});

// Controllers
$di->setShared(AuthController::class, function () use ($di) {
    return new AuthController(
        $di->get(RegisterUserService::class),
        $di->get(AuthenticateUserService::class),
        $di->get(RevokeTokenService::class),
        $di->get('cacheManager')
    );
});

// Middleware
$di->setShared(AuthMiddleware::class, function () use ($di) {
    return new AuthMiddleware(
        $di->get(JwtProviderInterface::class),
        $di->get(TokenBlacklistInterface::class)
    );
});

$di->setShared(RateLimitMiddleware::class, function () use ($di, $config) {
    return new RateLimitMiddleware($di->get('redis'), $config['rate_limit']);
});

return $di;
