$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$failures = [System.Collections.Generic.List[string]]::new()
$passes = 0
function Check([bool]$condition, [string]$message) {
    if ($condition) { $script:passes++; Write-Output "PASS $message" } else { $script:failures.Add($message); Write-Output "FAIL $message" }
}
function Read([string]$relative) { Get-Content -LiteralPath (Join-Path $root $relative) -Raw -Encoding utf8 }
$migration = Read 'database\migrations\202608310001_initial_identity.sql'
@('churches','users','platform_user_roles','platform_mfa_credentials','church_users','products','plans','plan_features','subscriptions','admin_audit_logs') | ForEach-Object { Check ($migration.Contains("CREATE TABLE $_")) "migration creates $_" }
$tenantRepo = Read 'app\Repositories\ChurchUserRepository.php'
Check ($tenantRepo.Contains('TenantContext $tenant')) 'tenant repository requires TenantContext'
Check ($tenantRepo.Contains('WHERE cu.church_id = :church_id')) 'tenant admin query scopes by church_id'
$churchRepo = Read 'app\Repositories\ChurchRepository.php'
Check ($churchRepo.Contains('id = :id AND id = :church_id')) 'tenant church query scopes by church_id'
$routes = Read 'routes\web.php'
Check ($routes.Contains('requirePlatform')) 'platform routes require platform authorization'
Check ($routes.Contains('requireTenant')) 'tenant routes require tenant authorization'
$auth = Read 'app\Core\Auth.php'
Check ($auth.Contains('password_verify')) 'login uses password_verify'
Check ($auth.Contains('Session::regenerate')) 'login regenerates session id'
Check ($auth.Contains('MFA_REQUIRED')) 'platform login requires MFA'
Check ($auth.Contains("`$pending['attempts'] >= 5")) 'MFA attempts are limited'
Check ((Read 'app\Repositories\MfaRepository.php').Contains('last_used_counter')) 'MFA prevents TOTP replay'
$service = Read 'app\Services\ChurchProvisioningService.php'
Check ($service.Contains('beginTransaction')) 'church provisioning is transactional'
Check ($service.Contains('password_hash')) 'admin passwords are hashed'
Check (-not (Test-Path -LiteralPath (Join-Path $root '.env'))) 'real .env is absent'
if ($failures.Count -gt 0) { throw "$($failures.Count) contract test(s) failed: $($failures -join ', ')" }
Write-Output "OK $passes contract tests passed."
