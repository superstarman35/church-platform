<?php

declare(strict_types=1);

use App\Core\TenantContext;
use App\Repositories\ChurchRepository;
use App\Repositories\ChurchUserRepository;
use App\Repositories\UserRepository;
use App\Services\ChurchProvisioningService;

$pdo = require dirname(__DIR__) . '/bootstrap/app.php';
$failures = [];
$passes = 0;

function integrationCheck(bool $condition, string $message): void
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

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['admin_audit_logs', 'subscriptions', 'church_users', 'platform_user_roles', 'users', 'churches'] as $table) {
    $pdo->exec("TRUNCATE TABLE {$table}");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$users = new UserRepository($pdo);
$platformId = $users->create('Platform Admin', 'platform@example.test', password_hash('Platform1234!', PASSWORD_DEFAULT));
$role = $pdo->prepare("INSERT INTO platform_user_roles (user_id, role) VALUES (:user_id, 'platform_admin')");
$role->execute(['user_id' => $platformId]);

$service = new ChurchProvisioningService($pdo);
$churchA = $service->createChurchWithOwner([
    'name' => 'A 교회', 'slug' => 'church-a', 'organization_type' => 'church',
    'contact_name' => 'A 담당자', 'contact_email' => 'contact-a@example.test', 'contact_phone' => '010-0000-0001',
], ['name' => 'A 관리자', 'email' => 'admin-a@example.test', 'password' => 'AdminA12345!'], $platformId);
$churchB = $service->createChurchWithOwner([
    'name' => 'B 교회', 'slug' => 'church-b', 'organization_type' => 'church',
    'contact_name' => 'B 담당자', 'contact_email' => 'contact-b@example.test', 'contact_phone' => '010-0000-0002',
], ['name' => 'B 관리자', 'email' => 'admin-b@example.test', 'password' => 'AdminB12345!'], $platformId);

integrationCheck($churchA !== $churchB, 'two churches are provisioned separately');
integrationCheck((int) $pdo->query('SELECT COUNT(*) FROM subscriptions')->fetchColumn() === 2, 'each church receives one trial subscription');
integrationCheck((int) $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'trialing'")->fetchColumn() === 2, 'trial subscriptions are active');

$tenantA = new TenantContext($churchA, 'owner');
$tenantB = new TenantContext($churchB, 'owner');
$memberships = new ChurchUserRepository($pdo);
$aAdmins = $memberships->listForTenant($tenantA);
$bAdmins = $memberships->listForTenant($tenantB);
integrationCheck(count($aAdmins) === 1 && $aAdmins[0]['email'] === 'admin-a@example.test', 'tenant A only lists tenant A administrators');
integrationCheck(count($bAdmins) === 1 && $bAdmins[0]['email'] === 'admin-b@example.test', 'tenant B only lists tenant B administrators');

$churches = new ChurchRepository($pdo);
integrationCheck($churches->findForTenant($churchA, $churchA) !== null, 'tenant can read its own church');
integrationCheck($churches->findForTenant($churchB, $churchA) === null, 'tenant cannot read another church by changing id');

$stored = $users->findByEmail('admin-a@example.test');
integrationCheck(is_array($stored) && $stored['password_hash'] !== 'AdminA12345!' && password_verify('AdminA12345!', $stored['password_hash']), 'administrator password is hashed and verifiable');
integrationCheck((int) $pdo->query('SELECT COUNT(*) FROM admin_audit_logs')->fetchColumn() >= 2, 'church provisioning writes audit logs');

if ($failures !== []) {
    fwrite(STDERR, count($failures) . " integration test(s) failed.\n");
    exit(1);
}

echo "OK {$passes} integration tests passed.\n";
