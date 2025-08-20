<?php

// Start session
session_start();

// Set timezone
date_default_timezone_set('Africa/Lagos');

// Require Composer autoloader
require_once '../vendor/autoload.php';

// Autoloader for application classes
spl_autoload_register(function ($class) {
    $root = dirname(__DIR__); // get the parent directory
    $file = $root . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_readable($file)) {
        require $root . '/' . str_replace('\\', '/', $class) . '.php';
    }
});

// Error and Exception handling
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');

// Require the configuration file
require_once '../config/config.php';

// Create a new router instance
$router = new Core\Router();

// Add routes
$router->add('', ['controller' => 'Home', 'action' => 'index']);
$router->add('{controller}/{action}');
$router->add('{controller}/{id:\d+}/{action}');

// Dispatch the router
$router->dispatch($_SERVER['QUERY_STRING']);
