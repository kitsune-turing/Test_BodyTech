<?php

declare(strict_types=1);

use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use RealtimeService\Infrastructure\Security\JwtValidator;
use RealtimeService\Infrastructure\WebSocket\ConnectionManager;

require_once __DIR__ . '/vendor/autoload.php';

// Load config
$config = require __DIR__ . '/config/config.php';

// Initialize services
$jwtValidator = new JwtValidator($config['jwt']['secret']);
$connectionManager = new ConnectionManager();

// Create WebSocket Server
$server = new Server(
    $config['websocket']['host'],
    $config['websocket']['port']
);

// Event: Server Started
$server->on('start', function (Server $server) use ($config) {
});

// Event: Connection Opened
$server->on('open', function (Server $server, Request $request) use ($jwtValidator, $connectionManager) {
    echo "[WebSocket] New connection attempt from fd: {$request->fd}\n";

    // Extract token from query string
    $token = $request->get['token'] ?? null;

    if (!$token) {
        echo "[WebSocket] Connection rejected: No token provided\n";
        $server->push($request->fd, json_encode([
            'error' => 'Authentication required. Please provide token in query string.'
        ]));
        $server->close($request->fd);
        return;
    }

    try {
        // Validate JWT
        $payload = $jwtValidator->validate($token);
        $userId = $payload['sub'] ?? null;

        if (!$userId) {
            throw new Exception('Invalid token payload: missing user ID');
        }

        // Register connection
        $connectionManager->add($request->fd, (int)$userId);

        echo "[WebSocket] Connection established for user: {$userId}, fd: {$request->fd}\n";

        // Send welcome message
        $server->push($request->fd, json_encode([
            'event' => 'connected',
            'message' => 'Successfully connected to WebSocket server',
            'userId' => $userId,
        ]));

    } catch (Exception $e) {
        echo "[WebSocket] Authentication failed: " . $e->getMessage() . "\n";
        $server->push($request->fd, json_encode([
            'error' => 'Authentication failed: ' . $e->getMessage()
        ]));
        $server->close($request->fd);
    }
});

// Event: Message Received
$server->on('message', function (Server $server, Frame $frame) use ($connectionManager) {
    $userId = $connectionManager->getUserId($frame->fd);

    // Echo back (optional)
    $server->push($frame->fd, json_encode([
        'event' => 'message_received',
        'data' => $frame->data,
    ]));
});

// Event: Connection Closed
$server->on('close', function (Server $server, int $fd) use ($connectionManager) {
    $userId = $connectionManager->getUserId($fd);

    $connectionManager->remove($fd);
});

// Start the server
$server->start();

// Redis Pub/Sub Subscriber (in worker process)
$server->on('workerStart', function ($server, $workerId) use ($connectionManager, $config) {
    // Only run in worker 0
    if ($workerId !== 0) {
        return;
    }

    go(function () use ($server, $connectionManager, $config) {
        $redis = new Redis();
        $maxRetries = 5;
        $retryDelay = 2;
        $attempt = 0;
        $connected = false;

        while ($attempt < $maxRetries && !$connected) {
            try {
                $redis->connect($config['redis']['host'], $config['redis']['port'], 5);
                $redis->ping();
                $connected = true;

                // Subscribe to task-events channel
                $redis->subscribe([$config['redis']['channel']], function ($redis, $channel, $message) use ($server, $connectionManager) {
                    try {
                        $event = json_decode($message, true);

                        if (!$event || !isset($event['userId'])) {
                            return;
                        }

                        $targetUserId = $event['userId'];

                        // Find all connections for this user
                        $userConnections = $connectionManager->getConnectionsByUserId($targetUserId);

                        if (empty($userConnections)) {
                            return;
                        }

                        // Broadcast to all user's connections
                        $payload = json_encode([
                            'event' => $event['event'],
                            'data' => $event['data'],
                        ]);

                        foreach ($userConnections as $fd) {
                            if ($server->isEstablished($fd)) {
                                $server->push($fd, $payload);
                            }
                        }

                    } catch (Exception $e) {
                        // Error silently
                    }
                });

            } catch (Exception $e) {
                $attempt++;
                
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                }
            }
        }
    });
});
