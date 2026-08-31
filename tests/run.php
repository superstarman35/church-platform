<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$passes = 0;

function check(bool $condition, string $message): void
{
    global $failures, $passes;
    if ($condition) {
        $passes++;
        echo "PASS {$message}\n";
        return;
    }
    $failures[] = $message;
    echo "FAIL {$message}\n";
}

function source(string $path): string
{
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException("Cannot read {$path}");
    }
    return $content;
}

$migration = source($root . '/database/migrations/202608310001_initial_identity.sql');
foreach (['churches', 'users', 'platform_user_roles', 'platform_mfa_credentials', 'church_users', 'products', 'plans', 'plan_features', 'subscriptions', 'admin_audit_logs'] as $table) {
    check(str_contains($migration, "CREATE TABLE {$table}"), "migration creates {$table}");
}
check(str_contains($migration, 'UNIQUE KEY uq_church_users_membership (church_id, user_id)'), 'membership is unique per church and user');
check(str_contains($migration, "'invitation-trial'"), 'invitation trial plan is seeded');

$tenantRepo = source($root . '/app/Repositories/ChurchUserRepository.php');
check(str_contains($tenantRepo, 'TenantContext $tenant'), 'tenant repository requires TenantContext');
check(str_contains($tenantRepo, 'WHERE cu.church_id = :church_id'), 'tenant admin query scopes by church_id');

$churchRepo = source($root . '/app/Repositories/ChurchRepository.php');
check(str_contains($churchRepo, 'id = :id AND id = :church_id'), 'tenant church query scopes by church_id');

$routes = source($root . '/routes/web.php');
check(str_contains($routes, 'requirePlatform'), 'platform routes require platform authorization');
check(str_contains($routes, 'requireTenant'), 'tenant routes require tenant authorization');

$controllers = source($root . '/app/Controllers/ChurchController.php') . source($root . '/app/Controllers/AuthController.php');
check(substr_count($controllers, 'Csrf::verify') >= 4, 'state-changing web actions verify CSRF');

$auth = source($root . '/app/Core/Auth.php');
check(str_contains($auth, 'password_verify'), 'login uses password_verify');
check(str_contains($auth, 'Session::regenerate'), 'login regenerates session id');
check(str_contains($auth, 'MFA_REQUIRED'), 'platform login requires MFA');
check(str_contains($auth, '$pending[\'attempts\'] >= 5'), 'MFA attempts are limited');
check(str_contains(source($root . '/app/Repositories/MfaRepository.php'), 'last_used_counter'), 'MFA prevents TOTP replay');

$service = source($root . '/app/Services/ChurchProvisioningService.php');
check(str_contains($service, 'beginTransaction'), 'church provisioning is transactional');
check(str_contains($service, 'password_hash'), 'admin passwords are hashed');
check(str_contains($service, 'createInvitationTrial'), 'church provisioning creates invitation trial');

$invitationMigration = source($root . '/database/migrations/202608310002_invitation_admin.sql');
foreach (['invitations', 'invitation_media', 'invitation_applications', 'invitation_daily_stats'] as $table) {
    check(str_contains($invitationMigration, "CREATE TABLE {$table}"), "invitation migration creates {$table}");
}
check(substr_count($invitationMigration, 'church_id BIGINT UNSIGNED NOT NULL') >= 4, 'all invitation business tables require church_id');
$invitationRepo = source($root . '/app/Repositories/InvitationRepository.php');
check(substr_count($invitationRepo, 'church_id') >= 10, 'invitation repository consistently scopes tenant data');
check(str_contains($invitationRepo, 'TenantContext $tenant'), 'invitation admin repository requires TenantContext');
$invitationController = source($root . '/app/Controllers/InvitationAdminController.php');
check(substr_count($invitationController, 'Csrf::verify') >= 4, 'invitation mutations verify CSRF');
check(str_contains($invitationController, 'invitation.monthly_create_count'), 'invitation creation checks subscription quota');
check(str_contains($invitationController, 'invitation.active_count'), 'publishing checks active invitation quota');
$publicController = source($root . '/app/Controllers/PublicInvitationController.php');
check(str_contains($publicController, 'application.max_count'), 'public applications check subscription quota');
check(str_contains($invitationController, 'youtubeAllowed') && str_contains(source($root . '/resources/views/public/invitation.php'), 'youtube_url'), 'public invitation supports YouTube-only media policy');
check(str_contains(source($root . '/app/Services/InvitationImageService.php'), 'MAX_UPLOAD_BYTES=1048576'), 'invitation images enforce the 1MB upload limit');
check(str_contains(source($root . '/app/Services/InvitationImageService.php'), 'imagewebp'), 'invitation images are converted to WebP');
$trackedEnv = shell_exec('git -C ' . escapeshellarg($root) . ' ls-files -- .env');
check(trim((string) $trackedEnv) === '', 'real .env is not committed to the repository');
check(str_contains(source($root . '/.gitignore'), "database/migrations/*.sql"), 'SQL migrations are explicitly tracked');

if ($failures !== []) {
    fwrite(STDERR, count($failures) . " contract test(s) failed.\n");
    exit(1);
}

echo "OK {$passes} contract tests passed.\n";
