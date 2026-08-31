<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Uuid;
use DateTimeImmutable;
use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $user = $statement->fetch();
        return is_array($user) ? $user : null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, uuid, name, email, status, last_login_at FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return is_array($user) ? $user : null;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $statement = $this->pdo->prepare('INSERT INTO users (uuid, name, email, password_hash) VALUES (:uuid, :name, :email, :password_hash)');
        $statement->execute([
            'uuid' => Uuid::v4(),
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password_hash' => $passwordHash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function platformRole(int $userId): ?string
    {
        $statement = $this->pdo->prepare('SELECT role FROM platform_user_roles WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $role = $statement->fetchColumn();
        return is_string($role) ? $role : null;
    }

    public function firstActiveChurchMembership(int $userId): ?array
    {
        $statement = $this->pdo->prepare("SELECT cu.church_id, cu.role, c.name church_name, c.status church_status FROM church_users cu JOIN churches c ON c.id = cu.church_id WHERE cu.user_id = :user_id AND cu.status = 'active' AND c.status IN ('trial', 'active') ORDER BY cu.id LIMIT 1");
        $statement->execute(['user_id' => $userId]);
        $membership = $statement->fetch();
        return is_array($membership) ? $membership : null;
    }

    public function recordSuccessfulLogin(int $userId): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET failed_login_count = 0, locked_until = NULL, status = IF(status = \'locked\', \'active\', status), last_login_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $userId]);
    }

    public function recordFailedLogin(int $userId, int $maxAttempts, int $lockMinutes): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET failed_login_count = failed_login_count + 1, locked_until = CASE WHEN failed_login_count + 1 >= :max_attempts THEN DATE_ADD(NOW(), INTERVAL :lock_minutes MINUTE) ELSE locked_until END, status = CASE WHEN failed_login_count + 1 >= :max_attempts THEN \'locked\' ELSE status END WHERE id = :id');
        $statement->bindValue('max_attempts', $maxAttempts, PDO::PARAM_INT);
        $statement->bindValue('lock_minutes', $lockMinutes, PDO::PARAM_INT);
        $statement->bindValue('id', $userId, PDO::PARAM_INT);
        $statement->execute();
    }

    public function isLocked(array $user): bool
    {
        if (($user['status'] ?? '') === 'disabled') {
            return true;
        }
        if (empty($user['locked_until'])) {
            return false;
        }
        return new DateTimeImmutable((string) $user['locked_until']) > new DateTimeImmutable();
    }
}
