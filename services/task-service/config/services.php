<?php

/**
 * Phalcon 4.x Dependency Injection Container
 */

use Phalcon\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Postgresql;
use TaskService\Application\Port\EventPublisherInterface;
use TaskService\Application\UseCase\CreateTaskService;
use TaskService\Application\UseCase\DeleteTaskService;
use TaskService\Application\UseCase\ExportTasksService;
use TaskService\Application\UseCase\GetTaskService;
use TaskService\Application\UseCase\ListTasksService;
use TaskService\Application\UseCase\UpdateTaskService;
use TaskService\Domain\Repository\TaskRepositoryInterface;
use TaskService\Domain\Validator\TaskValidator;
use TaskService\Infrastructure\Cache\RedisCacheManager;
use TaskService\Infrastructure\Event\RedisEventPublisher;
use TaskService\Infrastructure\Http\Controller\ExportController;
use TaskService\Infrastructure\Http\Controller\TaskController;
use TaskService\Infrastructure\Http\Middleware\JwtAuthMiddleware;
use TaskService\Infrastructure\Http\Middleware\RateLimitMiddleware;
use TaskService\Infrastructure\Persistence\PhalconTaskRepository;

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
    return new RedisCacheManager($di->get('redis'), 300, 'task_service:');
});

// Repository
$di->setShared(TaskRepositoryInterface::class, function () use ($di) {
    return new PhalconTaskRepository($di->get('pdo'));
});

// Event Publisher
$di->setShared(EventPublisherInterface::class, function () use ($di) {
    return new RedisEventPublisher($di->get('redis'));
});

// Validator
$di->setShared(TaskValidator::class, function () {
    return new TaskValidator();
});

// Use Cases
$di->setShared(ListTasksService::class, function () use ($di) {
    return new ListTasksService(
        $di->get(TaskRepositoryInterface::class),
        $di->get(TaskValidator::class)
    );
});

$di->setShared(CreateTaskService::class, function () use ($di) {
    return new CreateTaskService(
        $di->get(TaskRepositoryInterface::class),
        $di->get(TaskValidator::class),
        $di->get(EventPublisherInterface::class)
    );
});

$di->setShared(UpdateTaskService::class, function () use ($di) {
    return new UpdateTaskService(
        $di->get(TaskRepositoryInterface::class),
        $di->get(TaskValidator::class),
        $di->get(EventPublisherInterface::class)
    );
});

$di->setShared(DeleteTaskService::class, function () use ($di) {
    return new DeleteTaskService(
        $di->get(TaskRepositoryInterface::class),
        $di->get(EventPublisherInterface::class)
    );
});

$di->setShared(GetTaskService::class, function () use ($di) {
    return new GetTaskService($di->get(TaskRepositoryInterface::class));
});

$di->setShared(ExportTasksService::class, function () use ($di) {
    return new ExportTasksService($di->get(TaskRepositoryInterface::class));
});

// Controllers
$di->setShared(TaskController::class, function () use ($di) {
    return new TaskController(
        $di->get(ListTasksService::class),
        $di->get(CreateTaskService::class),
        $di->get(UpdateTaskService::class),
        $di->get(DeleteTaskService::class),
        $di->get(GetTaskService::class),
        $di->get('cacheManager')
    );
});

$di->setShared(ExportController::class, function () use ($di) {
    return new ExportController($di->get(ExportTasksService::class));
});

// Middleware
$di->setShared(JwtAuthMiddleware::class, function () use ($config) {
    return new JwtAuthMiddleware($config['jwt']['secret']);
});

$di->setShared(RateLimitMiddleware::class, function () use ($di, $config) {
    return new RateLimitMiddleware($di->get('redis'), $config['rate_limit']);
});

return $di;;
