<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ChurchController;
use App\Controllers\InvitationAdminController;
use App\Controllers\MediaController;
use App\Controllers\MfaController;
use App\Controllers\PlatformDashboardController;
use App\Controllers\PublicInvitationController;
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
    $invitationController = new InvitationAdminController($pdo);
    $publicInvitation = new PublicInvitationController($pdo);

    $router->get('/', static function () use ($auth): void {
        Response::redirect($auth->user() === null ? '/login' : ($auth->isPlatform() ? '/control' : '/admin'));
    });
    $router->get('/login', static fn () => $authController->showLogin());
    $router->post('/login', static fn () => $authController->login());
    $router->get('/mfa', static fn () => $mfaController->show());
    $router->post('/mfa', static fn () => $mfaController->verify());
    $router->post('/logout', static fn () => $authController->logout());

    $router->get('/media/{uuid}', static fn (array $p) => (new MediaController($pdo))->show($p['uuid']));
    $router->get('/i/{churchSlug}/{slug}', static fn (array $p) => $publicInvitation->show($p['churchSlug'], $p['slug']));
    $router->post('/i/{churchSlug}/{slug}/apply', static fn (array $p) => $publicInvitation->apply($p['churchSlug'], $p['slug']));
    $router->post('/i/{churchSlug}/{slug}/share', static fn (array $p) => $publicInvitation->share($p['churchSlug'], $p['slug']));

    $platform = [static fn () => $auth->requirePlatform()];
    $router->get('/control', static fn () => (new PlatformDashboardController($pdo))->index(), $platform);
    $router->get('/control/churches', static fn () => $churchController->index(), $platform);
    $router->get('/control/churches/create', static fn () => $churchController->create(), $platform);
    $router->post('/control/churches', static fn () => $churchController->store(), $platform);
    $router->get('/control/churches/{churchId}/admins/create', static fn (array $p) => $churchController->createAdmin((int)$p['churchId']), $platform);
    $router->post('/control/churches/{churchId}/admins', static fn (array $p) => $churchController->storeAdmin((int)$p['churchId']), $platform);

    $tenant = [static fn () => $auth->requireTenant()];
    $router->get('/admin', static fn () => (new TenantDashboardController($pdo))->index(), $tenant);
    $router->get('/admin/invitations', static fn () => $invitationController->index(), $tenant);
    $router->get('/admin/invitations/create', static fn () => $invitationController->create(), $tenant);
    $router->post('/admin/invitations', static fn () => $invitationController->store(), $tenant);
    $router->get('/admin/invitations/{id}/edit', static fn (array $p) => $invitationController->edit((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}', static fn (array $p) => $invitationController->update((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/clone', static fn (array $p) => $invitationController->clone((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/publish', static fn (array $p) => $invitationController->publish((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/end', static fn (array $p) => $invitationController->end((int)$p['id']), $tenant);
    $router->get('/admin/invitations/{id}/applications', static fn (array $p) => $invitationController->applications((int)$p['id']), $tenant);
};
