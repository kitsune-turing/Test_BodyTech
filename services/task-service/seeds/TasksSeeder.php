<?php

/**
 * Tasks Seeder
 * Creates test tasks for development
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/config.php';

try {
    // Connect to database
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['dbname']
    );

    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Test tasks (assuming user_id 1 exists from auth service)
    $tasks = [
        [
            'user_id' => 1,
            'title' => 'Comprar leche',
            'description' => 'Leche desnatada 1L del supermercado',
            'status' => 'pending',
        ],
        [
            'user_id' => 1,
            'title' => 'Revisar correos',
            'description' => 'Responder correos pendientes del trabajo',
            'status' => 'in_progress',
        ],
        [
            'user_id' => 1,
            'title' => 'Llamar al dentista',
            'description' => 'Agendar cita para revisión',
            'status' => 'done',
        ],
        [
            'user_id' => 2,
            'title' => 'Estudiar para examen',
            'description' => 'Repasar capítulos 5-8',
            'status' => 'pending',
        ],
        [
            'user_id' => 2,
            'title' => 'Hacer ejercicio',
            'description' => '30 minutos de cardio',
            'status' => 'in_progress',
        ],
    ];

    $stmt = $pdo->prepare('
        INSERT INTO tasks (user_id, title, description, status, created_at, updated_at)
        VALUES (:user_id, :title, :description, :status::task_status, NOW(), NOW())
    ');

    foreach ($tasks as $task) {
        $stmt->execute($task);
    }

} catch (PDOException $e) {
    exit(1);
} catch (Exception $e) {
    exit(1);
}
