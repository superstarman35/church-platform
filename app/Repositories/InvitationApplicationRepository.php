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
                (church_id, invitation_id, applicant_name, phone, email, attendee_count, message, is_waitlisted, answers_json, consented_at)
                VALUES (:church_id,:invitation_id,:applicant_name,:phone,:email,:attendee_count,:message,:is_waitlisted,:answers_json,NOW())";
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'church_id' => (int) $invitation['church_id'], 'invitation_id' => (int) $invitation['id'],
            'applicant_name' => $data['applicant_name'], 'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null, 'attendee_count' => $data['attendee_count'],
            'message' => $data['message'] ?: null,'is_waitlisted'=>!empty($data['is_waitlisted'])?1:0,'answers_json'=>empty($data['answers'])?null:json_encode($data['answers'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listForTenant(TenantContext $tenant, int $invitationId, array $filters = []): array
    {
        $sql = 'SELECT a.* FROM invitation_applications a JOIN invitations i ON i.id=a.invitation_id AND i.church_id=a.church_id WHERE a.church_id=:church_id AND a.invitation_id=:invitation_id';
        $params = ['church_id' => $tenant->churchId(), 'invitation_id' => $invitationId];
        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, ['new', 'confirmed', 'cancelled'], true)) {
            $sql .= ' AND a.status=:status';
            $params['status'] = $status;
        }
        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $sql .= ' AND (a.applicant_name LIKE :query OR a.phone LIKE :query OR a.email LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $field => $operator) {
            $date = (string) ($filters[$field] ?? '');
            if ($this->validDate($date)) {
                $sql .= " AND DATE(a.created_at) {$operator} :{$field}";
                $params[$field] = $date;
            }
        }
        $statement = $this->pdo->prepare($sql . ' ORDER BY a.id DESC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function updateStatusForTenant(TenantContext $tenant, int $invitationId, int $applicationId, string $status): bool
    {
        if (!in_array($status, ['new', 'confirmed', 'cancelled'], true)) return false;
        $exists = $this->pdo->prepare(
            'SELECT COUNT(*) FROM invitation_applications a JOIN invitations i ON i.id=a.invitation_id AND i.church_id=a.church_id
             WHERE a.id=:application_id AND a.invitation_id=:invitation_id AND a.church_id=:church_id'
        );
        $exists->execute([
            'application_id' => $applicationId, 'invitation_id' => $invitationId,
            'church_id' => $tenant->churchId(),
        ]);
        if ((int) $exists->fetchColumn() !== 1) return false;
        $statement = $this->pdo->prepare(
            'UPDATE invitation_applications SET status=:status
             WHERE id=:application_id AND invitation_id=:invitation_id AND church_id=:church_id'
        );
        return $statement->execute([
            'status' => $status, 'application_id' => $applicationId,
            'invitation_id' => $invitationId, 'church_id' => $tenant->churchId(),
        ]);
    }

    private function validDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year);
    }

    public function countForPeriod(int $churchId, string $startsAt, string $endsAt): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM invitation_applications WHERE church_id=:church_id AND created_at >= :starts_at AND created_at < :ends_at');
        $statement->execute(['church_id' => $churchId, 'starts_at' => $startsAt, 'ends_at' => $endsAt]);
        return (int) $statement->fetchColumn();
    }
    public function attendeeCountForInvitation(int $churchId,int $invitationId):int{$s=$this->pdo->prepare("SELECT COALESCE(SUM(attendee_count),0) FROM invitation_applications WHERE church_id=:church_id AND invitation_id=:invitation_id AND status<>'cancelled'");$s->execute(['church_id'=>$churchId,'invitation_id'=>$invitationId]);return (int)$s->fetchColumn();}
    public function updateAttendanceForTenant(TenantContext $tenant,int $invitationId,int $applicationId,string $status):bool{if(!in_array($status,['not_checked','attended','absent'],true))return false;$s=$this->pdo->prepare('UPDATE invitation_applications SET attendance_status=:status WHERE id=:id AND invitation_id=:invitation_id AND church_id=:church_id');$s->execute(['status'=>$status,'id'=>$applicationId,'invitation_id'=>$invitationId,'church_id'=>$tenant->churchId()]);return $s->rowCount()===1;}

    public function eventSummariesForTenant(TenantContext $tenant): array
    {
        $statement = $this->pdo->prepare(
            "SELECT i.id, i.title, i.event_at, i.status,
                    COUNT(a.id) AS application_count,
                    COALESCE(SUM(a.attendee_count), 0) AS attendee_count,
                    SUM(CASE WHEN a.status='new' THEN 1 ELSE 0 END) AS new_count,
                    SUM(CASE WHEN a.status='confirmed' THEN 1 ELSE 0 END) AS confirmed_count
             FROM invitations i
             LEFT JOIN invitation_applications a ON a.invitation_id=i.id AND a.church_id=i.church_id
             WHERE i.church_id=:church_id AND i.deleted_at IS NULL
             GROUP BY i.id, i.title, i.event_at, i.status
             ORDER BY COALESCE(i.event_at, i.created_at) DESC, i.id DESC"
        );
        $statement->execute(['church_id' => $tenant->churchId()]);
        return $statement->fetchAll();
    }

    public function listAllForTenant(TenantContext $tenant, array $filters = []): array
    {
        $sql = 'SELECT a.*, i.title AS invitation_title FROM invitation_applications a
                JOIN invitations i ON i.id=a.invitation_id AND i.church_id=a.church_id
                WHERE a.church_id=:church_id AND i.deleted_at IS NULL';
        $params = ['church_id' => $tenant->churchId()];
        $invitationId = (int) ($filters['invitation_id'] ?? 0);
        if ($invitationId > 0) { $sql .= ' AND a.invitation_id=:invitation_id'; $params['invitation_id'] = $invitationId; }
        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, ['new', 'confirmed', 'cancelled'], true)) { $sql .= ' AND a.status=:status'; $params['status'] = $status; }
        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') { $sql .= ' AND (a.applicant_name LIKE :query OR a.phone LIKE :query OR a.email LIKE :query)'; $params['query'] = '%' . $query . '%'; }
        $statement = $this->pdo->prepare($sql . ' ORDER BY a.id DESC LIMIT 500');
        $statement->execute($params);
        return $statement->fetchAll();
    }
}

