<?php
$projectRoot = dirname(__FILE__);

spl_autoload_register(function ($class) use ($projectRoot) {
    if (strpos($class, 'AuthService\\') === 0) {
        $relative_class = substr($class, strlen('AuthService\\'));
        $file = $projectRoot . '/src/' . str_replace('\\', '/', $relative_class) . '.php';
        if (is_file($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
});
?>
