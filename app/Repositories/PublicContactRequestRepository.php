<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PublicContactRequestRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO public_contact_requests (category, name, email, phone, church_name, subject, message, agreed_terms, ip_address, user_agent) VALUES (:category, :name, :email, :phone, :church_name, :subject, :message, :agreed_terms, :ip_address, :user_agent)'
        );

        $statement->execute([
            'category' => $data['category'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'church_name' => $data['church_name'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'agreed_terms' => $data['agreed_terms'] ? 1 : 0,
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listForPlatform(array $filters = []): array
    {
        $sql = 'SELECT r.id, r.category, r.name, r.email, r.phone, r.church_name, r.subject, r.message, r.agreed_terms, r.status, r.handled_at, r.handled_by_user_id, u.name AS handled_by_name, r.handled_note, INET_NTOA(r.ip_address) AS ip_address, r.user_agent, r.created_at
                FROM public_contact_requests r
                LEFT JOIN users u ON u.id = r.handled_by_user_id
                WHERE 1=1';
        $params = [];

        $category = (string)($filters['category'] ?? '');
        if (in_array($category, ['general', 'subscription', 'technical', 'policy'], true)) {
            $sql .= ' AND category = :category';
            $params['category'] = $category;
        }

        $status = (string)($filters['status'] ?? '');
        if (in_array($status, ['open', 'in_progress', 'answered', 'closed'], true)) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }

        $statement = $this->pdo->prepare($sql . ' ORDER BY r.id DESC LIMIT 300');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.id, r.category, r.name, r.email, r.phone, r.church_name, r.subject, r.message, r.agreed_terms, r.status, r.handled_at, r.handled_by_user_id, u.name AS handled_by_name, r.handled_note, INET_NTOA(r.ip_address) AS ip_address, r.user_agent, r.created_at
             FROM public_contact_requests r
             LEFT JOIN users u ON u.id = r.handled_by_user_id
             WHERE r.id = :id'
        );
        $statement->execute(['id' => $id]);
        $request = $statement->fetch();
        return $request === false ? null : $request;
    }

    public function updateStatus(int $id, string $status, string $note, int $userId): ?array
    {
        if (!in_array($status, ['open', 'in_progress', 'answered', 'closed'], true)) {
            return null;
        }

        $this->pdo->beginTransaction();
        try {
            $finder = $this->pdo->prepare('SELECT id, status, handled_note, handled_by_user_id FROM public_contact_requests WHERE id = :id FOR UPDATE');
            $finder->execute(['id' => $id]);
            $current = $finder->fetch();
            if ($current === false) {
                $this->pdo->rollBack();
                return null;
            }

            $statement = $this->pdo->prepare(
                'UPDATE public_contact_requests
                 SET status = :status,
                     handled_by_user_id = :handled_by_user_id,
                     handled_note = :handled_note,
                     handled_at = NOW()
                 WHERE id = :id'
            );
            $statement->execute([
                'status' => $status,
                'handled_by_user_id' => $userId > 0 ? $userId : null,
                'handled_note' => $note !== '' ? $note : null,
                'id' => $id,
            ]);

            $this->pdo->commit();
            return [
                'id' => $id,
                'status' => (string)$current['status'],
                'note' => (string)$current['handled_note'],
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
