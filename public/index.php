<?php

// Start session
session_start();

// Set timezone
date_default_timezone_set('Africa/Lagos');

// Require Composer autoloader
require_once '../vendor/autoload.php';

// Autoloader for application classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Error and Exception handling
error_reporting(E_ALL);
set_error_handler('App\Core\Error::errorHandler');
set_exception_handler('App\Core\Error::exceptionHandler');

// Require the configuration file
require_once '../config/config.php';

// Create a new router instance
$router = new App\Core\Router();

// Add routes
$router->add('', ['controller' => 'Home', 'action' => 'index']);
$router->add('{controller}/{action}');
$router->add('{controller}/{id:\d+}/{action}');

// Dispatch the router
$router->dispatch($_SERVER['QUERY_STRING']);
