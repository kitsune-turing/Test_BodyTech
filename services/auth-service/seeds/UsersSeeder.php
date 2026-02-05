<?php

/**
 * Users Seeder
 * Creates test users for development
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

    // Test users with hashed passwords
    $users = [
        [
            'email' => 'admin@example.com',
            'password' => 'Admin123!',
        ],
        [
            'email' => 'user1@example.com',
            'password' => 'User123!',
        ],
        [
            'email' => 'user2@example.com',
            'password' => 'User123!',
        ],
    ];

    $stmt = $pdo->prepare('
        INSERT INTO users (email, password_hash, created_at, updated_at)
        VALUES (:email, :password_hash, NOW(), NOW())
        ON CONFLICT (email) DO NOTHING
    ');

    foreach ($users as $user) {
        // Hash password with Argon2id
        $passwordHash = password_hash($user['password'], PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2
        ]);

        $stmt->execute([
            'email' => $user['email'],
            'password_hash' => $passwordHash,
        ]);
    }

} catch (PDOException $e) {
    exit(1);
} catch (Exception $e) {
    exit(1);
}
