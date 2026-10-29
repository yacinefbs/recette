<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Legacy URLs that leaked the internal folder (/src/public/...) -> clean URL
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('#^/src/public(/.*)?$#i', $uri, $m)) {
    header('Location: '.($m[1] ?? '/') , true, 301);
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Use .env.production (server credentials) on any non-local host; .env stays for local development
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]);
if (! in_array($host, ['localhost', '127.0.0.1', '::1'], true) && is_file($prod = __DIR__.'/../.env.production')) {
    $app->loadEnvironmentFrom(basename($prod));
}

$app->handleRequest(Request::capture());
