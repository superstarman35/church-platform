<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
use App\Core\Uuid;
use PDO;
use RuntimeException;

final class ChurchUserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function add(int $churchId, int $userId, string $role): void
    {
        $statement = $this->pdo->prepare('INSERT INTO church_users (church_id, user_id, role, status) VALUES (:church_id, :user_id, :role, \'active\')');
        $statement->execute(['church_id' => $churchId, 'user_id' => $userId, 'role' => $role]);
    }

    public function exists(int $churchId, int $userId): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM church_users WHERE church_id = :church_id AND user_id = :user_id LIMIT 1');
        $statement->execute(['church_id' => $churchId, 'user_id' => $userId]);
        return (bool) $statement->fetchColumn();
    }

    public function countActive(int $churchId): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM church_users WHERE church_id = :church_id AND status IN ('invited', 'active')");
        $statement->execute(['church_id' => $churchId]);
        return (int) $statement->fetchColumn();
    }
    public function listForTenant(TenantContext $tenant): array
    {
        $statement = $this->pdo->prepare('SELECT u.id, u.name, u.email, u.status, cu.role, cu.status membership_status, cu.created_at FROM church_users cu JOIN users u ON u.id = cu.user_id WHERE cu.church_id = :church_id ORDER BY cu.id');
        $statement->execute(['church_id' => $tenant->churchId()]);
        return $statement->fetchAll();
    }

    public function createForTenant(TenantContext $tenant, string $name, string $email, string $passwordHash, string $role): int
    {
        if (!in_array($role, ['owner', 'admin', 'content_manager'], true)) {
            throw new RuntimeException('허용되지 않은 관리자 역할입니다.');
        }
        $email = mb_strtolower(trim($email));
        $this->pdo->beginTransaction();
        try {
            $existing = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1 FOR UPDATE');
            $existing->execute(['email' => $email]);
            if ($existing->fetchColumn() !== false) {
                throw new RuntimeException('이미 등록된 이메일입니다. 기존 계정 연결은 플랫폼 관리자에게 요청해 주세요.');
            }
            $user = $this->pdo->prepare('INSERT INTO users (uuid, name, email, password_hash) VALUES (:uuid, :name, :email, :password_hash)');
            $user->execute(['uuid' => Uuid::v4(), 'name' => trim($name), 'email' => $email, 'password_hash' => $passwordHash]);
            $userId = (int) $this->pdo->lastInsertId();
            $this->add($tenant->churchId(), $userId, $role);
            $this->pdo->commit();
            return $userId;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }

    public function findForTenant(TenantContext $tenant, int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT cu.user_id, cu.role, cu.status, u.name, u.email FROM church_users cu JOIN users u ON u.id=cu.user_id WHERE cu.church_id=:church_id AND cu.user_id=:user_id LIMIT 1');
        $statement->execute(['church_id' => $tenant->churchId(), 'user_id' => $userId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function countActiveOwners(TenantContext $tenant): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM church_users WHERE church_id=:church_id AND role='owner' AND status='active'");
        $statement->execute(['church_id' => $tenant->churchId()]);
        return (int) $statement->fetchColumn();
    }

    public function suspendForTenant(TenantContext $tenant, int $userId): void
    {
        $statement = $this->pdo->prepare("UPDATE church_users SET status='suspended' WHERE church_id=:church_id AND user_id=:user_id AND status='active'");
        $statement->execute(['church_id' => $tenant->churchId(), 'user_id' => $userId]);
        if ($statement->rowCount() !== 1) throw new RuntimeException('활성 관리자 소속을 찾을 수 없습니다.');
    }
}
