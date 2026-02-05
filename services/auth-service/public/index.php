<?php

declare(strict_types=1);

use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use AuthService\Infrastructure\Http\Controller\AuthController;
use AuthService\Infrastructure\Http\Middleware\AuthMiddleware;
use AuthService\Infrastructure\Http\Middleware\RateLimitMiddleware;

try {
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

    // Register services (services.php will use the global DI)
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

// Rate limiting helper
$applyRateLimit = function(string $identifier) use ($app): ?object {
    $rateLimitMiddleware = $app->di->get(RateLimitMiddleware::class);
    $method = $app->request->getMethod();
    $path = $app->request->getURI();
    $endpoint = $method . ':' . $path;

    $rateLimitResult = $rateLimitMiddleware->handle($identifier, $endpoint);

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

    return null;
};

// Register routes
$app->post('/v1/api/register', function () use ($app, $applyRateLimit) {
    // Rate limiting by IP for registration
    $ip = $app->request->getClientAddress();
    $rateLimitResponse = $applyRateLimit($ip);
    if ($rateLimitResponse !== null) {
        return $rateLimitResponse;
    }

    $controller = $app->di->get(AuthController::class);
    
    $rawBody = $app->request->getRawBody();
    $requestData = json_decode($rawBody, true);
    
    // Si json_decode falló, intenta usar file_get_contents
    if ($requestData === null && !empty($rawBody)) {
        $requestData = json_decode($rawBody, true);
        if ($requestData === null) {
            // Si aún es null, trata de parsear manualmente
            error_log('JSON Parse Error: ' . json_last_error_msg());
            error_log('Raw body hex: ' . bin2hex($rawBody));
        }
    }
    
    $result = $controller->register($requestData ?? []);
    
    return $app->response
        ->setStatusCode($result['status'])
        ->setJsonContent($result['data'] ?? $result);
});

$app->post('/v1/api/login', function () use ($app, $applyRateLimit) {
    // Rate limiting by IP for login
    $ip = $app->request->getClientAddress();
    $rateLimitResponse = $applyRateLimit($ip);
    if ($rateLimitResponse !== null) {
        return $rateLimitResponse;
    }

    $controller = $app->di->get(AuthController::class);
    $rawBody = $app->request->getRawBody();
    
    // Debug logging
    error_log("LOGIN REQUEST - Raw Body: " . $rawBody);
    error_log("LOGIN REQUEST - Content Length: " . strlen($rawBody));
    error_log("LOGIN REQUEST - Content-Type: " . ($app->request->getHeader('Content-Type') ?? 'not set'));
    
    $requestData = json_decode($rawBody, true);
    
    error_log("LOGIN REQUEST - Parsed Data: " . json_encode($requestData));
    error_log("LOGIN REQUEST - Email present: " . (isset($requestData['email']) ? 'yes' : 'no'));
    error_log("LOGIN REQUEST - Password present: " . (isset($requestData['password']) ? 'yes' : 'no'));
    
    $result = $controller->login($requestData);
    
    return $app->response
        ->setStatusCode($result['status'])
        ->setJsonContent($result['data'] ?? $result);
});

$app->post('/v1/api/logout', function () use ($app, $applyRateLimit) {
    $middleware = $app->di->get(AuthMiddleware::class);
    $controller = $app->di->get(AuthController::class);

    $authHeader = $app->request->getHeader('Authorization');
    $authResult = $middleware->handle($authHeader);

    if (!$authResult['success']) {
        return $app->response
            ->setStatusCode($authResult['status'])
            ->setJsonContent(['error' => $authResult['error']]);
    }

    // Rate limiting by user ID for logout
    $userId = $authResult['user_id'] ?? $authResult['payload']['sub'] ?? 'unknown';
    $rateLimitResponse = $applyRateLimit((string) $userId);
    if ($rateLimitResponse !== null) {
        return $rateLimitResponse;
    }

    preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches);
    $token = $matches[1] ?? '';
    $result = $controller->logout($token);
    
    return $app->response
        ->setStatusCode($result['status'])
        ->setJsonContent($result['data'] ?? $result);
});

$app->get('/health', function () use ($app) {
    return $app->response
        ->setStatusCode(200)
        ->setJsonContent(['status' => 'healthy', 'service' => 'auth-service', 'framework' => 'Phalcon 4.x']);
});

// Not found handler
$app->notFound(function () use ($app) {
    return $app->response
        ->setStatusCode(404)
        ->setJsonContent(['error' => 'Endpoint not found']);
});

// Error handler
$app->error(function (\Throwable $e) use ($app) {
    $errorData = [
        'status' => 500,
        'error' => [
            'code' => 'INTERNAL_ERROR',
            'message' => 'Internal server error',
            'details' => [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ]
        ]
    ];
    
    // Log to stderr for debugging
    error_log('Phalcon Exception: ' . json_encode($errorData, JSON_PRETTY_PRINT));
    
    // Return clean response to client
    return $app->response
        ->setStatusCode(500)
        ->setJsonContent([
            'status' => 500,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => $e->getMessage()
            ]
        ]);
});

    // Handle the request
    $app->handle($_SERVER['REQUEST_URI']);
    
} catch (\Throwable $e) {
    // Initialize response if DI/Phalcon failed
    header('Content-Type: application/json');
    header('HTTP/1.1 500 Internal Server Error');
    http_response_code(500);
    
    error_log('BOOTSTRAP ERROR: ' . json_encode([
        'message' => $e->getMessage(),
        'class' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT));
    
    echo json_encode([
        'status' => 500,
        'error' => [
            'code' => 'BOOTSTRAP_ERROR',
            'message' => $e->getMessage()
        ]
    ]);
}
