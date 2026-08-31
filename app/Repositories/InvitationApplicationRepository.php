<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
use PDO;

final class InvitationApplicationRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(array $invitation, array $data): int
    {
        $sql = "INSERT INTO invitation_applications
                (church_id, invitation_id, applicant_name, phone, email, attendee_count, message, consented_at)
                VALUES (:church_id,:invitation_id,:applicant_name,:phone,:email,:attendee_count,:message,NOW())";
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'church_id' => (int) $invitation['church_id'], 'invitation_id' => (int) $invitation['id'],
            'applicant_name' => $data['applicant_name'], 'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null, 'attendee_count' => $data['attendee_count'],
            'message' => $data['message'] ?: null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listForTenant(TenantContext $tenant, int $invitationId): array
    {
        $statement = $this->pdo->prepare('SELECT a.* FROM invitation_applications a JOIN invitations i ON i.id=a.invitation_id AND i.church_id=a.church_id WHERE a.church_id=:church_id AND a.invitation_id=:invitation_id ORDER BY a.id DESC');
        $statement->execute(['church_id' => $tenant->churchId(), 'invitation_id' => $invitationId]);
        return $statement->fetchAll();
    }

    public function countForPeriod(int $churchId, string $startsAt, string $endsAt): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM invitation_applications WHERE church_id=:church_id AND created_at >= :starts_at AND created_at < :ends_at');
        $statement->execute(['church_id' => $churchId, 'starts_at' => $startsAt, 'ends_at' => $endsAt]);
        return (int) $statement->fetchColumn();
    }
}

