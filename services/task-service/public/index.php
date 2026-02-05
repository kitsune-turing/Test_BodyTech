<?php

declare(strict_types=1);

use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;

// Determine project root
$projectRoot = dirname(__DIR__);

// Always load our custom autoloader first
require_once $projectRoot . '/autoload.php';

// Try to load composer autoloader as fallback (if it exists)
$composerAutoload = $projectRoot . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

// Create DI container
$di = new FactoryDefault();

// Register config
$di->setShared('config', function () use ($projectRoot) {
    return require $projectRoot . '/config/config.php';
});

// Register services
require_once $projectRoot . '/config/services.php';

// Handle CORS preflight OPTIONS requests BEFORE creating Phalcon app
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $allowedOrigins = [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:5174',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5174',
    ];
    
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($requestOrigin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    header('Content-Length: 0');
    http_response_code(200);
    exit(0);
}

// Create Phalcon Micro application
$app = new Micro($di);

// CORS Middleware for actual requests
$app->before(function () use ($app) {
    // Set CORS headers for non-OPTIONS requests
    $allowedOrigins = [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:5174',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5174',
    ];
    
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($requestOrigin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    
    return true;
});

// Load controllers
use TaskService\Infrastructure\Http\Controller\ExportController;
use TaskService\Infrastructure\Http\Controller\TaskController;
use TaskService\Infrastructure\Http\Middleware\JwtAuthMiddleware;
use TaskService\Infrastructure\Http\Middleware\RateLimitMiddleware;

// Authentication helper with rate limiting
$authenticatedRoute = function($handler) use ($app) {
    // First, check authentication
    $authMiddleware = $app->di->get(JwtAuthMiddleware::class);
    $authHeader = $app->request->getHeader('Authorization');
    $authResult = $authMiddleware->handle($authHeader);

    if (!$authResult['success']) {
        return $app->response
            ->setStatusCode($authResult['status'])
            ->setJsonContent(['error' => $authResult['error']]);
    }

    $userId = $authResult['user_id'];

    // Rate limiting disabled for now - TODO: Fix RateLimitMiddleware DI issue
    /*
    $rateLimitMiddleware = $app->di->get(RateLimitMiddleware::class);
    $method = $app->request->getMethod();
    $path = $app->request->getURI();
    $endpoint = $method . ':' . $path;

    // Get endpoint-specific limit
    $customLimit = RateLimitMiddleware::getLimitForEndpoint($method, $path);
    $config = $app->di->get('config')['rate_limit'];
    $config['limit'] = $customLimit;

    $rateLimitResult = $rateLimitMiddleware->handle((string)$userId, $endpoint);

    // Add rate limit headers
    if (isset($rateLimitResult['headers'])) {
        foreach ($rateLimitResult['headers'] as $header => $value) {
            $app->response->setHeader($header, $value);
        }
    }

    if (!$rateLimitResult['success']) {
        return $app->response
            ->setStatusCode($rateLimitResult['status'])
            ->setJsonContent(['error' => $rateLimitResult['error']]);
    }
    */

    return $handler($userId);
};

// List tasks (GET /v1/api/tasks)
$app->get('/v1/api/tasks', function () use ($app, $authenticatedRoute) {
    return $authenticatedRoute(function($userId) use ($app) {
        try {
            $controller = $app->di->get(TaskController::class);
            $queryParams = $app->request->getQuery();
            $result = $controller->list($userId, $queryParams);
            
            return $app->response
                ->setStatusCode($result['status'])
                ->setJsonContent($result['data'] ?? $result);
        } catch (\Exception $e) {
            return $app->response
                ->setStatusCode(500)
                ->setJsonContent([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
        }
    });
});

// Create task (POST /v1/api/tasks)
$app->post('/v1/api/tasks', function () use ($app, $authenticatedRoute) {
    return $authenticatedRoute(function($userId) use ($app) {
        $controller = $app->di->get(TaskController::class);
        $rawBody = $app->request->getRawBody();
        $requestData = json_decode($rawBody, true);
        $result = $controller->create($userId, $requestData);
        
        return $app->response
            ->setStatusCode($result['status'])
            ->setJsonContent($result['data'] ?? $result);
    });
});

// Export tasks (GET /v1/api/tasks/export/csv or /v1/api/tasks/export/pdf)
$app->get('/v1/api/tasks/export/csv', function () use ($app, $authenticatedRoute) {
    return $authenticatedRoute(function($userId) use ($app) {
        try {
            $controller = $app->di->get(ExportController::class);
            $queryParams = $app->request->getQuery();
            $result = $controller->exportCSV($userId, $queryParams);

            // Set CORS headers
            $allowedOrigins = [
                'http://localhost:5173',
                'http://localhost:3000',
                'http://localhost:5174',
                'http://127.0.0.1:5173',
            ];
            $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($requestOrigin, $allowedOrigins, true)) {
                $app->response->setHeader('Access-Control-Allow-Origin', $requestOrigin);
            }

            if (isset($result['headers'])) {
                foreach ($result['headers'] as $header => $value) {
                    $app->response->setHeader($header, $value);
                }
            }

            return $app->response
                ->setStatusCode($result['status'])
                ->setContent($result['data']);
        } catch (\Exception $e) {
            return $app->response
                ->setStatusCode(500)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    });
});

$app->get('/v1/api/tasks/export/pdf', function () use ($app, $authenticatedRoute) {
    return $authenticatedRoute(function($userId) use ($app) {
        try {
            $controller = $app->di->get(ExportController::class);
            $queryParams = $app->request->getQuery();
            $result = $controller->exportPDF($userId, $queryParams);

            // Set CORS headers
            $allowedOrigins = [
                'http://localhost:5173',
                'http://localhost:3000',
                'http://localhost:5174',
                'http://127.0.0.1:5173',
            ];
            $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($requestOrigin, $allowedOrigins, true)) {
                $app->response->setHeader('Access-Control-Allow-Origin', $requestOrigin);
            }

            if (isset($result['headers'])) {
                foreach ($result['headers'] as $header => $value) {
                    $app->response->setHeader($header, $value);
                }
            }

            return $app->response
                ->setStatusCode($result['status'])
                ->setContent($result['data']);
        } catch (\Exception $e) {
            return $app->response
                ->setStatusCode(500)
                ->setJsonContent(['error' => $e->getMessage()]);
        }
    });
});

// Get specific task (GET /v1/api/tasks/{id})
$app->get('/v1/api/tasks/{id:[0-9]+}', function ($id) use ($app, $authenticatedRoute) {
    return $authenticatedRoute(function($userId) use ($app, $id) {
        $controller = $app->di->get(TaskController::class);
        $result = $controller->get((int)$id, $userId);
        
        return $app->response
            ->setStatusCode($result['status'])
            ->setJsonContent($result['data'] ?? $result);
    });
});

// Update task (PUT /v1/api/tasks/{id})
$app->put('/v1/api/tasks/{id:[0-9]+}', function ($id) use ($app, $authenticatedRoute) {
    return $authenticatedRoute(function($userId) use ($app, $id) {
        $controller = $app->di->get(TaskController::class);
        $rawBody = $app->request->getRawBody();
        $requestData = json_decode($rawBody, true);
        $result = $controller->update((int)$id, $userId, $requestData);
        
        return $app->response
            ->setStatusCode($result['status'])
            ->setJsonContent($result['data'] ?? $result);
    });
});

// Delete task (DELETE /v1/api/tasks/{id})
$app->delete('/v1/api/tasks/{id:[0-9]+}', function ($id) use ($app, $authenticatedRoute) {
    return $authenticatedRoute(function($userId) use ($app, $id) {
        $controller = $app->di->get(TaskController::class);
        $result = $controller->delete((int)$id, $userId);

        return $app->response
            ->setStatusCode($result['status'])
            ->setJsonContent($result['data'] ?? $result);
    });
});

// Health check (GET /health)
$app->get('/health', function () use ($app) {
    return $app->response
        ->setStatusCode(200)
        ->setJsonContent(['status' => 'healthy', 'service' => 'task-service']);
});

// Debug route
$app->get('/debug/export', function () use ($app) {
    return $app->response
        ->setStatusCode(200)
        ->setJsonContent(['debug' => 'export route found']);
});

// Not found handler
$app->notFound(function () use ($app) {
    return $app->response
        ->setStatusCode(404)
        ->setJsonContent(['error' => 'Endpoint not found']);
});

// Error handler
$app->error(function (\Throwable $e) use ($app) {
    return $app->response
        ->setStatusCode(500)
        ->setJsonContent([
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
});

// Handle the request
$app->handle($_SERVER['REQUEST_URI']);
