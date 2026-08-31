<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\AuditLogRepository;
use App\Repositories\MfaRepository;
use App\Repositories\UserRepository;
use PDO;

final class Auth
{
    public const FAILED = 'failed';
    public const AUTHENTICATED = 'authenticated';
    public const MFA_REQUIRED = 'mfa_required';

    private UserRepository $users;
    private AuditLogRepository $audit;
    private MfaRepository $mfa;

    public function __construct(private readonly PDO $pdo)
    {
        $this->users = new UserRepository($pdo);
        $this->audit = new AuditLogRepository($pdo);
        $this->mfa = new MfaRepository($pdo);
    }

    public function attempt(string $email, string $password): string
    {
        $user = $this->users->findByEmail($email);
        $maxAttempts = (int) Config::get('app.login.max_attempts', 5);
        $lockMinutes = (int) Config::get('app.login.lock_minutes', 15);

        if ($user === null || $this->users->isLocked($user) || !password_verify($password, (string) $user['password_hash'])) {
            if ($user !== null && !$this->users->isLocked($user)) {
                $this->users->recordFailedLogin((int) $user['id'], $maxAttempts, $lockMinutes);
            }
            $this->audit->record($user === null ? null : (int) $user['id'], null, 'auth.login_failed', 'user', $user['id'] ?? null, ['email_hash' => hash('sha256', mb_strtolower(trim($email)))]);
            return self::FAILED;
        }

        $platformRole = $this->users->platformRole((int) $user['id']);
        $membership = $this->users->firstActiveChurchMembership((int) $user['id']);
        if ($platformRole === null && $membership === null) {
            return self::FAILED;
        }

        $this->users->recordSuccessfulLogin((int) $user['id']);
        Session::regenerate();

        if ($platformRole !== null) {
            Session::put('pending_mfa', [
                'user_id' => (int) $user['id'],
                'name' => (string) $user['name'],
                'email' => (string) $user['email'],
                'platform_role' => $platformRole,
                'expires_at' => time() + 300,
                'attempts' => 0,
            ]);
            $this->audit->record((int) $user['id'], null, 'auth.password_succeeded_mfa_pending', 'user', (int) $user['id']);
            return self::MFA_REQUIRED;
        }

        $this->establishTenantSession($user, $membership);
        $this->audit->record((int) $user['id'], (int) $membership['church_id'], 'auth.login_succeeded', 'user', (int) $user['id']);
        return self::AUTHENTICATED;
    }

    public function hasPendingMfa(): bool
    {
        $pending = Session::get('pending_mfa');
        return is_array($pending) && (int) ($pending['user_id'] ?? 0) > 0 && (int) ($pending['expires_at'] ?? 0) >= time();
    }

    public function completePlatformMfa(string $code): bool
    {
        $pending = Session::get('pending_mfa');
        if (!$this->hasPendingMfa() || !is_array($pending)) {
            Session::forget('pending_mfa');
            return false;
        }
        if (!$this->mfa->verify((int) $pending['user_id'], $code)) {
            $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            $this->audit->record((int) $pending['user_id'], null, 'auth.mfa_failed', 'user', (int) $pending['user_id'], ['attempt' => $pending['attempts']]);
            if ($pending['attempts'] >= 5) {
                Session::forget('pending_mfa');
            } else {
                Session::put('pending_mfa', $pending);
            }
            return false;
        }
        Session::regenerate();
        Session::put('auth', [
            'user_id' => (int) $pending['user_id'],
            'name' => (string) $pending['name'],
            'email' => (string) $pending['email'],
            'platform_role' => (string) $pending['platform_role'],
            'church_id' => null,
            'church_role' => null,
        ]);
        Session::forget('pending_mfa');
        $this->audit->record((int) $pending['user_id'], null, 'auth.mfa_succeeded', 'user', (int) $pending['user_id']);
        return true;
    }

    private function establishTenantSession(array $user, array $membership): void
    {
        Session::put('auth', [
            'user_id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'platform_role' => null,
            'church_id' => (int) $membership['church_id'],
            'church_role' => (string) $membership['role'],
        ]);
    }

    public function logout(): void
    {
        $auth = $this->user();
        if ($auth !== null) {
            $this->audit->record((int) $auth['user_id'], isset($auth['church_id']) ? (int) $auth['church_id'] : null, 'auth.logout', 'user', (int) $auth['user_id']);
        }
        Session::invalidate();
    }

    public function user(): ?array
    {
        $auth = Session::get('auth');
        return is_array($auth) ? $auth : null;
    }

    public function isPlatform(): bool
    {
        return in_array($this->user()['platform_role'] ?? null, ['platform_admin', 'platform_operator'], true);
    }

    public function hasTenant(): bool
    {
        return (int) ($this->user()['church_id'] ?? 0) > 0;
    }

    public function requireGuest(): void
    {
        if ($this->user() !== null) {
            Response::redirect($this->isPlatform() ? '/control' : '/admin');
        }
    }

    public function requirePlatform(): void
    {
        if ($this->user() === null) {
            Response::redirect('/login');
        }
        if (!$this->isPlatform()) {
            Response::abort(403, '플랫폼 관리자 권한이 필요합니다.');
        }
    }

    public function requireTenant(): void
    {
        if ($this->user() === null) {
            Response::redirect('/login');
        }
        if (!$this->hasTenant()) {
            Response::abort(403, '교회 관리자 권한이 필요합니다.');
        }
    }
}
