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
check(str_contains($routes, "'/admin/events'") && str_contains($routes, "'/admin/support'"), 'tenant event and support routes exist');

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
$appRepo = source($root . '/app/Repositories/InvitationApplicationRepository.php');
check(str_contains($appRepo, 'updateStatusForTenant(TenantContext $tenant'), 'application status updates require TenantContext');
check(str_contains($appRepo, 'listAllForTenant(TenantContext $tenant') && str_contains($appRepo, 'a.church_id=:church_id'), 'aggregate applications remain tenant scoped');
check(str_contains($appRepo, "['new', 'confirmed', 'cancelled']"), 'application status uses an explicit allowlist');
check(str_contains($invitationController, 'invitation.applications_exported'), 'application CSV exports write an audit event');
check(substr_count($invitationController, 'authorizeApplicantData($tenant)') >= 3, 'application personal data requires an explicit management-role check');
check(str_contains($invitationController, "'^[=+\\-@\\t]"), 'application CSV neutralizes spreadsheet formulas');
check(str_contains($routes, '/applications/{applicationId}/status'), 'application status route is registered');
check(str_contains($routes, '/admin/invitations/{id}/share'), 'tenant invitation share tools route is registered');
check(str_contains($routes, '/admin/invitations/{id}/trash') && str_contains($routes, '/admin/invitations/{id}/restore'), 'tenant invitation trash and restore routes are registered');
check(str_contains($invitationController, 'shareTools(int $id)'), 'share tools require a tenant-scoped invitation lookup');
$shareView = source($root . '/resources/views/admin/invitations/share.php');
check(str_contains($shareView, 'navigator.clipboard.writeText'), 'share tools provide URL copy');
check(str_contains($shareView, 'rawurlencode'), 'share links encode invitation data');
$publicController = source($root . '/app/Controllers/PublicInvitationController.php');
check(str_contains($publicController, 'application.max_count'), 'public applications check subscription quota');
check(str_contains($invitationController, 'youtubeAllowed') && str_contains(source($root . '/resources/views/public/invitation.php'), 'youtube_url'), 'public invitation supports YouTube-only media policy');
check(str_contains(source($root . '/app/Services/InvitationImageService.php'), 'MAX_UPLOAD_BYTES=1048576'), 'invitation images enforce the 1MB upload limit');
check(str_contains(source($root . '/app/Services/InvitationImageService.php'), 'imagewebp'), 'invitation images are converted to WebP');
check(str_contains(source($root . '/app/Services/InvitationImageService.php'), 'invitation.photos_per_item'), 'gallery uploads enforce per-invitation photo limits');
check(str_contains(source($root . '/resources/views/admin/invitations/form.php'), 'gallery_images[]') && str_contains(source($root . '/resources/views/public/invitation.php'), 'loading="lazy"'), 'gallery upload and lazy public rendering are wired');
check(str_contains(source($root . '/app/Repositories/InvitationMediaRepository.php'), 'i.deleted_at IS NULL') && str_contains(source($root . '/app/Repositories/InvitationMediaRepository.php'), 'i.expires_at > NOW()'), 'public invitation media follows lifecycle visibility rules');
check(str_contains($routes, '/media/{mediaId}/delete'), 'tenant gallery delete route is registered');
check(str_contains(source($root . '/app/Repositories/InvitationMediaRepository.php'), 'deleteGallery(TenantContext $tenant'), 'gallery deletion requires TenantContext');
check(str_contains(source($root . '/app/Services/InvitationImageService.php'), 'str_starts_with($path, $root . DIRECTORY_SEPARATOR)'), 'gallery file deletion stays inside upload storage');
$statsRepo = source($root . '/app/Repositories/InvitationStatsRepository.php');
check(str_contains($statsRepo, 'monthlyTraffic(int $churchId'), 'traffic history is tenant-scoped by church');
check(str_contains($statsRepo, 'recordTraffic(int $churchId') && str_contains(source($root . '/app/Controllers/MediaController.php'), '->recordTraffic('), 'public media bytes are recorded without increasing view counts');
check(str_contains($statsRepo, "DATE_FORMAT(stat_date, '%Y-%m')"), 'traffic history is grouped by month');
check(str_contains($statsRepo, 'summaryForTenant(TenantContext $tenant') && str_contains($statsRepo, 'dailyForTenant(TenantContext $tenant'), 'invitation analytics queries require TenantContext');
check(substr_count($statsRepo, 'i.church_id=s.church_id') >= 2 && substr_count($statsRepo, 's.church_id=:church_id') >= 2, 'invitation analytics verifies tenant ownership in both stats and invitation rows');
check(str_contains($invitationController, "limit($snapshot, 'analytics.retention_days')"), 'analytics retention comes from a feature limit');
check(str_contains($routes, '/admin/invitations/{id}/stats'), 'tenant invitation statistics route is registered');
check(str_contains($invitationController, 'hasImageUpload') && str_contains($invitationController, 'assertTrafficAllowsUploads'), 'traffic quota blocks only new image uploads at the server');
$dashboard = source($root . '/resources/views/admin/dashboard.php');
check(str_contains($dashboard, "trafficLevel==='blocked'") && str_contains($dashboard, '다음 자동 초기화'), 'dashboard explains traffic thresholds and monthly reset');
$trackedEnv = shell_exec('git -C ' . escapeshellarg($root) . ' ls-files -- .env');
check(trim((string) $trackedEnv) === '', 'real .env is not committed to the repository');
check(str_contains(source($root . '/.gitignore'), "database/migrations/*.sql"), 'SQL migrations are explicitly tracked');

$profileMigration = source($root . '/database/migrations/202608310003_church_profile.sql');
check(str_contains($profileMigration, 'CREATE TABLE church_profiles'), 'church profile migration creates church_profiles');
check(str_contains($profileMigration, 'church_id BIGINT UNSIGNED PRIMARY KEY'), 'church profile has one tenant-owned row per church');
$profileController = source($root . '/app/Controllers/ChurchProfileController.php');
check(str_contains($profileController, 'Csrf::verify'), 'church profile update verifies CSRF');
check(str_contains($profileController, "['owner','admin']"), 'church profile update restricts management roles');
check(str_contains($profileController, 'church.profile_updated'), 'church profile changes write an audit event');
check(str_contains($churchRepo, 'profileForTenant(TenantContext $tenant)'), 'church profile reads require TenantContext');
check(str_contains($churchRepo, 'WHERE c.id = :church_id'), 'church profile reads scope by church_id');
check(str_contains($churchRepo, 'WHERE id=:church_id'), 'church profile core updates scope by church_id');

check(str_contains(source($root . '/app/Repositories/QuotaChangeRequestRepository.php'), 'church_id=:church_id') && str_contains(source($root . '/app/Controllers/TenantDashboardController.php'), 'quota_change.requested'), 'quota change requests are tenant-scoped and audited');

check(str_contains(source($root . '/app/Repositories/QuotaChangeRequestRepository.php'), 'FOR UPDATE') && str_contains(source($root . '/app/Repositories/QuotaChangeRequestRepository.php'), 'beginTransaction'), 'platform quota review is transactional');
check(str_contains(source($root . '/app/Controllers/PlatformDashboardController.php'), 'quota_change.') && str_contains(source($root . '/routes/web.php'), '/control/quota-requests/{id}/approve'), 'platform quota approval is routed and audited');
check(str_contains(source($root . '/app/Repositories/InvitationStatsRepository.php'), 'traffic_reset_logs') && str_contains(source($root . '/app/Services/SubscriptionEntitlementService.php'), 'quota_overrides'), 'traffic reset and increases affect effective usage and limit');


$subscriptionController = source($root . '/app/Controllers/SubscriptionController.php');
check(str_contains($routes, '/admin/subscription'), 'tenant subscription screen route is registered');
check(str_contains($subscriptionController, 'TenantContext::fromSession()'), 'subscription screen derives church from tenant session');
check(str_contains(source($root . '/app/Repositories/QuotaChangeRequestRepository.php'), 'historyForTenant(TenantContext $tenant'), 'subscription request history requires TenantContext');
check(str_contains(source($root . '/resources/views/admin/subscription.php'), '기능별 한도'), 'subscription screen presents feature-based limits');
$subscriptionMigration = source($root . '/database/migrations/202608310007_subscription_management.sql');
check(str_contains($subscriptionMigration, 'CREATE TABLE subscription_change_requests'), 'subscription migration creates change requests');
check(str_contains($routes, '/admin/subscription/change-request'), 'tenant paid conversion request route is registered');
check(str_contains($routes, '/control/subscription-requests/{id}/payment-review'), 'platform payment review route is registered');
check(str_contains($subscriptionController, 'Csrf::verify'), 'subscription conversion request verifies CSRF');
$subscriptionRequests = source($root . '/app/Repositories/SubscriptionChangeRequestRepository.php');
check(str_contains($subscriptionRequests, 'TenantContext $tenant') && str_contains($subscriptionRequests, 'church_id=:church_id'), 'subscription requests are tenant-scoped');
check(str_contains($subscriptionRequests, "['owner','admin']"), 'subscription requests require a management role');
check(str_contains($subscriptionRequests, 'FOR UPDATE') && str_contains($subscriptionRequests, 'beginTransaction'), 'subscription request creation serializes duplicate tenant requests');
check(str_contains($subscriptionRequests, 'p.price_krw > 0') && str_contains($subscriptionRequests, 'p.trial_days IS NULL'), 'paid product catalog excludes trial plans');
check(!str_contains($subscriptionRequests, 'UPDATE subscriptions'), 'platform review cannot change a subscription before payment confirmation');
check(str_contains(source($root . '/resources/views/control/dashboard.php'), '현재 구독은 변경하지') || str_contains(source($root . '/resources/views/control/dashboard.php'), '구독 요금제나 한도를 변경하지'), 'platform review explains payment confirmation safety');
$managerController = source($root . '/app/Controllers/ChurchAdminController.php');
$managerRepo = source($root . '/app/Repositories/ChurchUserRepository.php');
check(str_contains($routes, '/admin/managers/{id}/suspend'), 'tenant manager suspension route is registered');
check(substr_count($managerController, 'Csrf::verify') >= 2, 'manager mutations verify CSRF');
check(str_contains($managerController, 'admin.max_count'), 'manager count uses a feature limit');
check(str_contains($managerController, "role()!=='owner'&&$role==='owner'"), 'admin cannot create an owner');
check(str_contains($managerController, '마지막 대표관리자'), 'last owner is protected');
check(str_contains($managerRepo, 'WHERE cu.church_id=:church_id AND cu.user_id=:user_id'), 'manager target lookup is tenant-scoped');
check(str_contains($managerRepo, "SET status='suspended'") && !str_contains($managerRepo, 'DELETE FROM church_users'), 'manager access is suspended instead of deleted');
$operationsRepo=source($root . '/app/Repositories/PlatformOperationsRepository.php');
check(str_contains($operationsRepo,'LIMIT 200'),'platform operations list has a bounded result set');
check(str_contains($operationsRepo,'prepare($sql)') && str_contains($operationsRepo,"str_replace(['%','_']"),'platform operations search is parameterized and escapes LIKE wildcards');
check(!str_contains($operationsRepo,'contact_email') && !str_contains($operationsRepo,'contact_phone'),'platform operations list minimizes personal data');
check(str_contains($operationsRepo,"feature_code='traffic.monthly_bytes'") && str_contains($operationsRepo,"feature_code='storage.total_bytes'"),'platform operations limits are feature based');
check(str_contains(source($root . '/resources/views/control/dashboard.php'),'조회 전용 화면'),'platform operations UI declares read-only behavior');
check(str_contains(source($root . '/resources/views/layout.php'),'/admin/managers'),'tenant manager navigation is linked');
$supportMigration=source($root . '/database/migrations/202609010001_support_tickets.sql');
check(str_contains($supportMigration,'CREATE TABLE support_tickets') && str_contains($supportMigration,'church_id BIGINT UNSIGNED NOT NULL'),'support tickets require tenant ownership');
$supportRepo=source($root . '/app/Repositories/SupportTicketRepository.php');
check(str_contains($supportRepo,'TenantContext $tenant') && str_contains($supportRepo,'WHERE church_id=:church_id'),'support tickets remain tenant scoped');
check(str_contains(source($root . '/app/Controllers/SupportController.php'),'Csrf::verify'),'support ticket creation verifies CSRF');
check(str_contains(source($root . '/app/Controllers/EventAdminController.php'),'authorizeApplicantData'),'aggregate applicant view restricts personal data');
check(str_contains($routes,"'/control/support'") && str_contains($routes,'requirePlatform'),'platform support queue is protected by platform middleware');
check(str_contains($supportRepo,'FOR UPDATE') && str_contains($supportRepo,'beginTransaction'),'platform support review is transactional');
check(str_contains(source($root . '/app/Controllers/PlatformSupportController.php'),'support_ticket.reviewed'),'platform support review writes an audit event');
$trialRepo=source($root . '/app/Repositories/TrialManagementRepository.php');
check(str_contains($routes,"'/control/trials'") && str_contains($routes,'PlatformTrialController'),'platform trial management is routed behind platform middleware');
check(str_contains(source($root . '/app/Controllers/PlatformTrialController.php'),'Csrf::verify'),'trial operations verify CSRF');
check(str_contains($trialRepo,'FOR UPDATE') && str_contains($trialRepo,'beginTransaction'),'trial operations serialize changes in a transaction');
check(str_contains($trialRepo,"billing_cycle='trial'") && str_contains($trialRepo,'church_id=:church_id'),'trial operations target only trial subscriptions and scope updates by church');
check(!str_contains($trialRepo,'DELETE FROM') && str_contains(source($root . '/database/migrations/202609010002_trial_operations.sql'),'ON DELETE RESTRICT'),'trial management preserves data and operation history');
$mediaMigration=source($root.'/database/migrations/202609010003_media_management.sql');
check(str_contains($mediaMigration,'alt_text')&&str_contains($mediaMigration,'usage_consent'),'media migration stores accessibility and consent metadata');
$mediaRepo=source($root.'/app/Repositories/InvitationMediaRepository.php');
check(str_contains($mediaRepo,'listForTenant(TenantContext $tenant')&&str_contains($mediaRepo,'m.church_id=:church_id'),'media listing is tenant scoped');
check(str_contains($mediaRepo,'UPDATE invitation_media SET deleted_at=NOW()')&&str_contains($mediaRepo,'restore(TenantContext $tenant'),'media uses recoverable trash');
$mediaAdmin=source($root.'/app/Controllers/MediaAdminController.php');
check(substr_count($mediaAdmin,'Csrf::verify')>=3&&str_contains($mediaAdmin,'media.trashed'),'media mutations verify CSRF and are audited');
check(str_contains($routes,"'/admin/media'")&&str_contains($routes,"'/admin/storage'"),'media and storage routes are registered');
check(str_contains(source($root.'/resources/views/layout.php'),'/admin/media')&&str_contains(source($root.'/resources/views/layout.php'),'/admin/storage'),'media navigation is linked');
check(str_contains(source($root.'/app/Services/InvitationImageService.php'),"'original_file_bytes'=>(int)(\$upload['size']??0)"),'original upload size is recorded');
$trafficAdmin=source($root.'/app/Controllers/TrafficAdminController.php');
check(str_contains($routes,"'/admin/traffic'")&&str_contains(source($root.'/resources/views/layout.php'),'/admin/traffic'),'traffic detail is routed and linked');
check(str_contains($trafficAdmin,'TenantContext::fromSession()')&&str_contains($trafficAdmin,'historyForTenant'),'traffic detail is tenant scoped');
$trafficRepo=source($root.'/app/Repositories/InvitationStatsRepository.php');
check(str_contains($trafficRepo,'dailyTrafficForTenant(TenantContext $tenant')&&str_contains($trafficRepo,'topTrafficInvitationsForTenant(TenantContext $tenant'),'traffic analytics queries require TenantContext');
check(str_contains(source($root.'/resources/views/admin/traffic/index.php'),'플랫폼 운영자 승인 후 반영'),'traffic changes remain platform-approved');
$analyticsAdmin=source($root.'/app/Controllers/AnalyticsAdminController.php');
check(str_contains($routes,"'/admin/analytics'")&&str_contains($routes,"'/admin/analytics/export'")&&str_contains(source($root.'/resources/views/layout.php'),'/admin/analytics'),'analytics routes and navigation are linked');
check(str_contains($analyticsAdmin,'TenantContext::fromSession()')&&str_contains($analyticsAdmin,'analytics.retention_days'),'analytics tenant scope and retention are enforced');
check(str_contains($analyticsAdmin,"'previous'=>")&&str_contains($analyticsAdmin,'analytics.exported'),'analytics comparison and audit exist');
check(str_contains($analyticsAdmin,'preg_match')&&str_contains($analyticsAdmin,'fputcsv'),'analytics CSV is formula safe');
check(str_contains($trafficRepo,'popularForTenant(TenantContext $tenant')&&str_contains($trafficRepo,'LIMIT {$limit}'),'popular analytics is bounded');
check(str_contains($trafficRepo,'periodAggregateForTenant(TenantContext $tenant')&&str_contains($analyticsAdmin,"'weekly'=>")&&str_contains($analyticsAdmin,"'monthly'=>"),'weekly monthly analytics are tenant scoped');
$questionRepo=source($root.'/app/Repositories/InvitationQuestionRepository.php');
$questionAdmin=source($root.'/app/Controllers/QuestionAdminController.php');
$publicInvitationController=source($root.'/app/Controllers/PublicInvitationController.php');
$publicInvitationView=source($root.'/resources/views/public/invitation.php');
$eventAdminView=source($root.'/resources/views/admin/events/index.php');
$eventAdminController=source($root.'/app/Controllers/EventAdminController.php');
check(str_contains($questionRepo,'TenantContext $t')&&str_contains($questionRepo,'church_id=:church_id')&&str_contains($questionRepo,'SET is_active=0'),'event questions are tenant scoped and soft deactivated');
check(str_contains($questionAdmin,'Csrf::verify')&&str_contains($questionAdmin,"['owner','admin']")&&str_contains($questionAdmin,'event_question.saved'),'question changes require role CSRF and audit');
check(str_contains($publicInvitationController,'activePublic')&&str_contains(source($root.'/app/Repositories/InvitationApplicationRepository.php'),'JSON_THROW_ON_ERROR')&&str_contains($publicInvitationController,'필수 신청 질문'),'public question answers are server validated and safely encoded');
check(str_contains($publicInvitationView,'answers[')&&str_contains($publicInvitationView,"question_type']==='select'")&&str_contains($publicInvitationView,"question_type']==='checkbox'"),'public invitation renders question inputs');
check(str_contains($eventAdminView,'/questions')&&str_contains($eventAdminView,'/attendance')&&str_contains($eventAdminView,'is_waitlisted'),'event admin exposes question waitlist and attendance UI');
check(str_contains($eventAdminController,'authorizeApplicantData($tenant)')&&str_contains($eventAdminController,'attendance_changed'),'attendance changes restrict applicant data and are audited');
$visibilityMigration=source($root.'/database/migrations/202609010005_invitation_visibility.sql');$publicInvitation=source($root.'/app/Controllers/PublicInvitationController.php');$invitationAdmin=source($root.'/app/Controllers/InvitationAdminController.php');
check(str_contains($visibilityMigration,"ENUM('public','password','private')")&&str_contains($visibilityMigration,'access_password_hash VARCHAR(255)'),'visibility migration stores password hash');
check(str_contains($invitationAdmin,'password_hash')&&!str_contains(source($root.'/app/Repositories/InvitationRepository.php'),'access_password '),'access passwords are never persisted as plaintext');
check(str_contains($publicInvitation,'password_verify')&&str_contains($publicInvitation,"visibility']==='private'"),'password and private access are guarded');
check(substr_count($publicInvitation,'requireVisibilityAccess')>=4,'show apply share enforce access guard');
check(str_contains($publicInvitation,'$count>=5?time()+900:0')&&str_contains($publicInvitation,'invitation_access_failures')&&str_contains($publicInvitation,'invitation.access_failed'),'access failures are limited logged and audited');
check(str_contains($invitationRepo,'i.publish_at <= NOW()')&&str_contains($invitationRepo,'i.expires_at > NOW()'),'visibility keeps schedule and expiry');
check(str_contains($mediaRepo,'activeBelongsToInvitation(TenantContext $tenant')&&str_contains($mediaRepo,'invitation_id=:invitation_id AND deleted_at IS NULL'),'share media is tenant invitation scoped');
check(str_contains($invitationAdmin,"empty(\$existing['access_password_hash'])"),'blank password transition without hash is blocked');
$designMigration=source($root.'/database/migrations/202609010006_invitation_design.sql');$designController=source($root.'/app/Controllers/InvitationDesignController.php');$designView=source($root.'/resources/views/admin/invitation-design/index.php');$designCss=source($root.'/public/assets/design-presets.css');
check(str_contains($designMigration,'color_preset')&&str_contains($designMigration,'font_preset')&&str_contains($designMigration,'button_preset'),'design migration adds controlled presets');
check(str_contains($invitationRepo,'updateDesign(TenantContext $tenant')&&str_contains($invitationRepo,'id=:id AND church_id=:church_id'),'design update is tenant scoped');
check(str_contains($designController,"['owner','admin']")&&str_contains($designController,'Csrf::verify')&&str_contains($designController,'invitation.design_updated'),'design changes require role CSRF and audit');
check(str_contains($designController,'in_array')&&str_contains($designController,'TEMPLATES')&&str_contains($designController,'COLORS'),'design preset input is allowlisted');
check(str_contains($routes,"'/admin/invitation-design'")&&str_contains(source($root.'/resources/views/layout.php'),'/admin/invitation-design'),'design management is routed and linked');
check(str_contains($publicInvitationView,'color-')&&str_contains(source($root.'/resources/views/public/layout.php'),'design-presets.css'),'public invitation applies design presets');
check(str_contains($designView,'1080')&&str_contains($designView,'1200')&&str_contains($designCss,'.design-preview'),'design admin provides image guidance and preview styling');
$subscriptionRepo=source($root.'/app/Repositories/SubscriptionChangeRequestRepository.php');$platformController=source($root.'/app/Controllers/PlatformDashboardController.php');$subscriptionMigration=source($root.'/database/migrations/202608310007_subscription_management.sql');
check(str_contains($subscriptionRepo,'FOR UPDATE')&&str_contains($subscriptionRepo,'beginTransaction')&&str_contains($subscriptionRepo,'rollBack'),'subscription completion is transactional and serialized');
check(str_contains($subscriptionRepo,"status']!=='awaiting_payment'")&&str_contains($subscriptionRepo,'paymentReference')&&str_contains($subscriptionMigration,'payment_confirmed_at'),'subscription completion requires payment confirmation');
check(str_contains($subscriptionRepo,'INSERT INTO subscriptions')&&str_contains($subscriptionRepo,"status='completed'")&&str_contains($subscriptionRepo,"UPDATE churches SET status='active'"),'subscription completion applies plan and closes request');
check(str_contains($routes,'/subscription-requests/{id}/complete')&&str_contains($platformController,'Csrf::verify')&&str_contains($platformController,'subscription_change.completed'),'subscription completion is protected and audited');
$trialRepo=source($root.'/app/Repositories/TrialManagementRepository.php');$trialCli=source($root.'/bin/expire-trials.php');
check(str_contains($trialRepo,'expireDue')&&str_contains($trialRepo,"status='trialing'")&&str_contains($trialRepo,'trial_ends_at<=NOW()'),'trial expiry selects only due active trials');
check(str_contains($trialRepo,'FOR UPDATE')&&str_contains($trialRepo,'beginTransaction')&&str_contains($trialRepo,'rollBack'),'trial auto expiry is serialized and transactional');
check(str_contains($trialRepo,'trial.auto_expired')&&str_contains($trialRepo,'trial_operation_logs')&&!str_contains($trialRepo,'DELETE FROM'),'trial expiry is audited without deleting data');
check(str_contains($trialCli,"PHP_SAPI !== 'cli'")&&str_contains($trialCli,'FILTER_VALIDATE_INT')&&str_contains($trialCli,'expireDue'),'trial expiry CLI validates execution and batch size');
$backup=source($root.'/bin/backup.sh');$restore=source($root.'/bin/restore-test.sh');$envExample=source($root.'/.env.example');
check(str_contains($backup,'mysqldump')&&str_contains($backup,'storage/uploads')&&str_contains($backup,'single-transaction'),'backup includes database and uploads');
check(str_contains($backup,'aes-256-cbc')&&str_contains($backup,'pbkdf2')&&str_contains($backup,'sha256sum'),'backup is encrypted and checksummed');
check(strpos($backup,'tar -tzf')<strpos($backup,'-mtime +7')&&str_contains($backup,'BACKUP_EXTERNAL_DIR'),'retention follows successful verification');
check(str_contains($restore,'RESTORE_TEST_DB')&&str_contains($restore,'Refusing to restore into the application database')&&str_contains($restore,'sha256sum -c'),'restore drill protects production and verifies checksum');
check(str_contains($envExample,'BACKUP_PASSPHRASE')&&str_contains($envExample,'RESTORE_TEST_DB'),'backup configuration uses environment values');
if ($failures !== []) {
    fwrite(STDERR, count($failures) . " contract test(s) failed.\n");
    exit(1);
}

echo "OK {$passes} contract tests passed.\n";
