<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AuditLogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(?int $actorUserId, ?int $churchId, string $action, ?string $subjectType = null, int|string|null $subjectId = null, array $metadata = []): void
    {
        $statement = $this->pdo->prepare('INSERT INTO admin_audit_logs (actor_user_id, church_id, action, subject_type, subject_id, metadata, ip_address, user_agent) VALUES (:actor_user_id, :church_id, :action, :subject_type, :subject_id, :metadata, :ip_address, :user_agent)');
        $statement->execute([
            'actor_user_id' => $actorUserId,
            'church_id' => $churchId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
        ]);
    }
}
