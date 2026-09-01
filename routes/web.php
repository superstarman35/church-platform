<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ChurchController;
use App\Controllers\ChurchProfileController;
use App\Controllers\ChurchAdminController;
use App\Controllers\EventAdminController;
use App\Controllers\InvitationAdminController;
use App\Controllers\InvitationDesignController;
use App\Controllers\MediaController;
use App\Controllers\MediaAdminController;
use App\Controllers\MfaController;
use App\Controllers\QuestionAdminController;
use App\Controllers\PlatformDashboardController;
use App\Controllers\PlatformSupportController;
use App\Controllers\PlatformTrialController;
use App\Controllers\PublicInvitationController;
use App\Controllers\TenantDashboardController;
use App\Controllers\TrafficAdminController;
use App\Controllers\AnalyticsAdminController;
use App\Controllers\SubscriptionController;
use App\Controllers\SupportController;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;
use PDO;

return static function (Router $router, PDO $pdo): void {
    $auth = new Auth($pdo);
    $authController = new AuthController($pdo);
    $mfaController = new MfaController($pdo);
    $churchController = new ChurchController($pdo);
    $invitationController = new InvitationAdminController($pdo);
    $churchProfileController = new ChurchProfileController($pdo);
    $churchAdminController = new ChurchAdminController($pdo);
    $eventAdminController = new EventAdminController($pdo);
    $supportController = new SupportController($pdo);
    $publicInvitation = new PublicInvitationController($pdo);

    $router->get('/', static function () use ($auth): void {
        View::render('home.index', [
            'title' => '마음을 잇는 기독교 모바일 초대장',
            'dashboardUrl' => $auth->user() === null ? null : ($auth->isPlatform() ? '/control' : '/admin'),
        ], 'home.layout');
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
    $router->get('/i/{churchSlug}/{slug}/access', static fn (array $p) => $publicInvitation->access($p['churchSlug'], $p['slug']));
    $router->post('/i/{churchSlug}/{slug}/access', static fn (array $p) => $publicInvitation->unlock($p['churchSlug'], $p['slug']));

    $platform = [static fn () => $auth->requirePlatform()];
    $router->get('/control', static fn () => (new PlatformDashboardController($pdo))->index(), $platform);
    $router->post('/control/quota-requests/{id}/approve', static fn (array $p) => (new PlatformDashboardController($pdo))->reviewQuotaRequest((int)$p['id'], 'approved'), $platform);
    $router->post('/control/quota-requests/{id}/reject', static fn (array $p) => (new PlatformDashboardController($pdo))->reviewQuotaRequest((int)$p['id'], 'rejected'), $platform);
    $router->post('/control/subscription-requests/{id}/payment-review', static fn (array $p) => (new PlatformDashboardController($pdo))->reviewSubscriptionRequest((int)$p['id'], 'awaiting_payment'), $platform);
    $router->post('/control/subscription-requests/{id}/reject', static fn (array $p) => (new PlatformDashboardController($pdo))->reviewSubscriptionRequest((int)$p['id'], 'rejected'), $platform);
    $router->post('/control/subscription-requests/{id}/complete', static fn (array $p) => (new PlatformDashboardController($pdo))->completeSubscriptionRequest((int)$p['id']), $platform);
    $router->get('/control/churches', static fn () => $churchController->index(), $platform);
    $router->get('/control/churches/create', static fn () => $churchController->create(), $platform);
    $router->post('/control/churches', static fn () => $churchController->store(), $platform);
    $router->get('/control/churches/{churchId}/admins/create', static fn (array $p) => $churchController->createAdmin((int)$p['churchId']), $platform);
    $router->post('/control/churches/{churchId}/admins', static fn (array $p) => $churchController->storeAdmin((int)$p['churchId']), $platform);
    $router->get('/control/support', static fn () => (new PlatformSupportController($pdo))->index(), $platform);
    $router->post('/control/support/{id}/review', static fn (array $p) => (new PlatformSupportController($pdo))->review((int)$p['id']), $platform);
    $router->get('/control/trials', static fn () => (new PlatformTrialController($pdo))->index(), $platform);
    $router->post('/control/trials/{id}/extend', static fn (array $p) => (new PlatformTrialController($pdo))->operate((int)$p['id'], 'extend'), $platform);
    $router->post('/control/trials/{id}/expire', static fn (array $p) => (new PlatformTrialController($pdo))->operate((int)$p['id'], 'expire'), $platform);
    $router->post('/control/trials/{id}/recover', static fn (array $p) => (new PlatformTrialController($pdo))->operate((int)$p['id'], 'recover'), $platform);

    $tenant = [static fn () => $auth->requireTenant()];
    $router->get('/admin', static fn () => (new TenantDashboardController($pdo))->index(), $tenant);
    $router->get('/admin/traffic', static fn () => (new TrafficAdminController($pdo))->index(), $tenant);
    $router->get('/admin/analytics', static fn () => (new AnalyticsAdminController($pdo))->index(), $tenant);
    $router->get('/admin/analytics/export', static fn () => (new AnalyticsAdminController($pdo))->export(), $tenant);
    $router->get('/admin/subscription', static fn () => (new SubscriptionController($pdo))->index(), $tenant);
    $router->post('/admin/subscription/change-request', static fn () => (new SubscriptionController($pdo))->requestChange(), $tenant);
    $router->post('/admin/quota-request', static fn () => (new TenantDashboardController($pdo))->requestQuotaChange(), $tenant);
    $router->get('/admin/church', static fn () => $churchProfileController->edit(), $tenant);
    $router->post('/admin/church', static fn () => $churchProfileController->update(), $tenant);
    $router->get('/admin/managers', static fn () => $churchAdminController->index(), $tenant);
    $router->post('/admin/managers', static fn () => $churchAdminController->store(), $tenant);
    $router->post('/admin/managers/{id}/suspend', static fn (array $p) => $churchAdminController->suspend((int)$p['id']), $tenant);
    $router->get('/admin/events', static fn () => $eventAdminController->index(), $tenant);
    $router->get('/admin/events/{invitationId}/questions', static fn (array $p) => (new QuestionAdminController($pdo))->index((int)$p['invitationId']), $tenant);
    $router->post('/admin/events/{invitationId}/questions', static fn (array $p) => (new QuestionAdminController($pdo))->save((int)$p['invitationId']), $tenant);
    $router->post('/admin/events/{invitationId}/questions/{id}/deactivate', static fn (array $p) => (new QuestionAdminController($pdo))->deactivate((int)$p['invitationId'],(int)$p['id']), $tenant);
    $router->post('/admin/events/{invitationId}/applications/{applicationId}/status', static fn (array $p) => $eventAdminController->updateStatus((int)$p['invitationId'], (int)$p['applicationId']), $tenant);
    $router->post('/admin/events/{invitationId}/applications/{applicationId}/attendance', static fn (array $p) => $eventAdminController->updateAttendance((int)$p['invitationId'], (int)$p['applicationId']), $tenant);
    $router->get('/admin/support', static fn () => $supportController->index(), $tenant);
    $router->get('/admin/media', static fn () => (new MediaAdminController($pdo))->index(), $tenant);
    $router->post('/admin/media/{id}', static fn (array $p) => (new MediaAdminController($pdo))->update((int)$p['id']), $tenant);
    $router->post('/admin/media/{id}/trash', static fn (array $p) => (new MediaAdminController($pdo))->trash((int)$p['id']), $tenant);
    $router->post('/admin/media/{id}/restore', static fn (array $p) => (new MediaAdminController($pdo))->restore((int)$p['id']), $tenant);
    $router->get('/admin/storage', static fn () => (new MediaAdminController($pdo))->storage(), $tenant);
    $router->post('/admin/support', static fn () => $supportController->store(), $tenant);
    $router->get('/admin/invitations', static fn () => $invitationController->index(), $tenant);
    $router->get('/admin/invitation-design', static fn () => (new InvitationDesignController($pdo))->index(), $tenant);
    $router->post('/admin/invitation-design/{id}', static fn (array $p) => (new InvitationDesignController($pdo))->update((int)$p['id']), $tenant);
    $router->get('/admin/invitations/create', static fn () => $invitationController->create(), $tenant);
    $router->post('/admin/invitations', static fn () => $invitationController->store(), $tenant);
    $router->get('/admin/invitations/{id}/edit', static fn (array $p) => $invitationController->edit((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}', static fn (array $p) => $invitationController->update((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/clone', static fn (array $p) => $invitationController->clone((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/publish', static fn (array $p) => $invitationController->publish((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/end', static fn (array $p) => $invitationController->end((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/trash', static fn (array $p) => $invitationController->trash((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/restore', static fn (array $p) => $invitationController->restore((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/media/{mediaId}/delete', static fn (array $p) => $invitationController->deleteGallery((int)$p['id'], (int)$p['mediaId']), $tenant);
    $router->get('/admin/invitations/{id}/share', static fn (array $p) => $invitationController->shareTools((int)$p['id']), $tenant);
    $router->get('/admin/invitations/{id}/stats', static fn (array $p) => $invitationController->stats((int)$p['id']), $tenant);
    $router->get('/admin/invitations/{id}/applications', static fn (array $p) => $invitationController->applications((int)$p['id']), $tenant);
    $router->get('/admin/invitations/{id}/applications/export', static fn (array $p) => $invitationController->exportApplications((int)$p['id']), $tenant);
    $router->post('/admin/invitations/{id}/applications/{applicationId}/status', static fn (array $p) => $invitationController->updateApplicationStatus((int)$p['id'], (int)$p['applicationId']), $tenant);
};
