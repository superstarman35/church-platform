<?php

declare(strict_types=1);

use App\Core\Router;

$pdo = require dirname(__DIR__) . '/bootstrap/app.php';
$router = new Router();
$registerRoutes = require dirname(__DIR__) . '/routes/web.php';
$registerRoutes($router, $pdo);
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
