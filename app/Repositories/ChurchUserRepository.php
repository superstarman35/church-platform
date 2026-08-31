<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
use PDO;

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

    public function listForTenant(TenantContext $tenant): array
    {
        $statement = $this->pdo->prepare('SELECT u.id, u.name, u.email, u.status, cu.role, cu.status membership_status, cu.created_at FROM church_users cu JOIN users u ON u.id = cu.user_id WHERE cu.church_id = :church_id ORDER BY cu.id');
        $statement->execute(['church_id' => $tenant->churchId()]);
        return $statement->fetchAll();
    }
}
