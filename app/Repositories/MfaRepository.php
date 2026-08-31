<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Crypto;
use App\Core\Totp;
use PDO;

final class MfaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function enable(int $userId, string $secret): void
    {
        $statement = $this->pdo->prepare('INSERT INTO platform_mfa_credentials (user_id, encrypted_secret, enabled_at) VALUES (:user_id, :encrypted_secret, NOW()) ON DUPLICATE KEY UPDATE encrypted_secret = VALUES(encrypted_secret), enabled_at = NOW(), last_used_counter = NULL');
        $statement->execute(['user_id' => $userId, 'encrypted_secret' => Crypto::encrypt($secret)]);
    }

    public function verify(int $userId, string $code): bool
    {
        $statement = $this->pdo->prepare('SELECT encrypted_secret, last_used_counter FROM platform_mfa_credentials WHERE user_id = :user_id AND enabled_at IS NOT NULL LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $credential = $statement->fetch();
        if (!is_array($credential)) {
            return false;
        }
        $counter = Totp::verify(Crypto::decrypt((string) $credential['encrypted_secret']), trim($code));
        if ($counter === null || ($credential['last_used_counter'] !== null && $counter <= (int) $credential['last_used_counter'])) {
            return false;
        }
        $update = $this->pdo->prepare('UPDATE platform_mfa_credentials SET last_used_counter = :counter WHERE user_id = :user_id AND (last_used_counter IS NULL OR last_used_counter < :counter_check)');
        $update->execute(['counter' => $counter, 'user_id' => $userId, 'counter_check' => $counter]);
        return $update->rowCount() === 1;
    }
}
