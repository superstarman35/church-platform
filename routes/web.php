<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ChurchController;
use App\Controllers\MfaController;
use App\Controllers\PlatformDashboardController;
use App\Controllers\TenantDashboardController;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Router;
use PDO;

return static function (Router $router, PDO $pdo): void {
    $auth = new Auth($pdo);
    $authController = new AuthController($pdo);
    $mfaController = new MfaController($pdo);
    $churchController = new ChurchController($pdo);

    $router->get('/', static function () use ($auth): void {
        Response::redirect($auth->user() === null ? '/login' : ($auth->isPlatform() ? '/control' : '/admin'));
    });
    $router->get('/login', static fn () => $authController->showLogin());
    $router->post('/login', static fn () => $authController->login());
    $router->get('/mfa', static fn () => $mfaController->show());
    $router->post('/mfa', static fn () => $mfaController->verify());
    $router->post('/logout', static fn () => $authController->logout());

    $platform = [static fn () => $auth->requirePlatform()];
    $router->get('/control', static fn () => (new PlatformDashboardController($pdo))->index(), $platform);
    $router->get('/control/churches', static fn () => $churchController->index(), $platform);
    $router->get('/control/churches/create', static fn () => $churchController->create(), $platform);
    $router->post('/control/churches', static fn () => $churchController->store(), $platform);
    $router->get('/control/churches/{churchId}/admins/create', static fn (array $params) => $churchController->createAdmin((int) $params['churchId']), $platform);
    $router->post('/control/churches/{churchId}/admins', static fn (array $params) => $churchController->storeAdmin((int) $params['churchId']), $platform);

    $tenant = [static fn () => $auth->requireTenant()];
    $router->get('/admin', static fn () => (new TenantDashboardController($pdo))->index(), $tenant);
};
