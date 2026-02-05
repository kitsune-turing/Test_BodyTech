<?php
// PSR-4 Auto-loader for TaskService

$projectRoot = dirname(__FILE__);

spl_autoload_register(function ($class) use ($projectRoot) {
    if (strpos($class, 'TaskService\\') === 0) {
        $relative_class = substr($class, strlen('TaskService\\'));
        $file = $projectRoot . '/src/' . str_replace('\\', '/', $relative_class) . '.php';
        if (is_file($file)) {
            require_once $file;
            return true;
        }
    }
    // Don't return false - let SPL continue to next autoloader (Composer)
});
