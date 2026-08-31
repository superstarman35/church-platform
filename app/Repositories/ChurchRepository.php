<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Uuid;
use PDO;

final class ChurchRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function allForPlatform(): array
    {
        $sql = "SELECT c.id, c.uuid, c.slug, c.name, c.organization_type, c.status, c.product_family, c.created_at, s.status subscription_status, s.trial_ends_at FROM churches c LEFT JOIN subscriptions s ON s.id = (SELECT s2.id FROM subscriptions s2 WHERE s2.church_id = c.id ORDER BY s2.id DESC LIMIT 1) ORDER BY c.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function findForPlatform(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM churches WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $church = $statement->fetch();
        return is_array($church) ? $church : null;
    }

    public function findForTenant(int $id, int $churchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, uuid, slug, name, organization_type, status, product_family FROM churches WHERE id = :id AND id = :church_id LIMIT 1');
        $statement->execute(['id' => $id, 'church_id' => $churchId]);
        $church = $statement->fetch();
        return is_array($church) ? $church : null;
    }

    public function slugExists(string $slug): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM churches WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);
        return (bool) $statement->fetchColumn();
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO churches (uuid, slug, name, organization_type, status, product_family, contact_name, contact_email, contact_phone) VALUES (:uuid, :slug, :name, :organization_type, \'trial\', \'invitation\', :contact_name, :contact_email, :contact_phone)');
        $statement->execute([
            'uuid' => Uuid::v4(),
            'slug' => $data['slug'],
            'name' => $data['name'],
            'organization_type' => $data['organization_type'],
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?: null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
