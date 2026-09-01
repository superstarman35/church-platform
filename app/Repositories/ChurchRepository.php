<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
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

    public function profileForTenant(TenantContext $tenant): ?array
    {
        $statement = $this->pdo->prepare('SELECT c.id, c.slug, c.name, c.organization_type, c.contact_name, c.contact_email, c.contact_phone, p.english_name, p.short_description, p.representative_name, p.representative_title, p.postal_code, p.address_line1, p.address_detail, p.map_url, p.website_url, p.youtube_url, p.instagram_url, p.facebook_url FROM churches c LEFT JOIN church_profiles p ON p.church_id = c.id WHERE c.id = :church_id LIMIT 1');
        $statement->execute(['church_id' => $tenant->churchId()]);
        $profile = $statement->fetch();
        return is_array($profile) ? $profile : null;
    }

    public function updateProfileForTenant(TenantContext $tenant, array $data, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $core = $this->pdo->prepare('UPDATE churches SET name=:name, contact_name=:contact_name, contact_email=:contact_email, contact_phone=:contact_phone WHERE id=:church_id');
            $core->execute(['name'=>$data['name'], 'contact_name'=>$data['contact_name'] ?: null, 'contact_email'=>$data['contact_email'] ?: null, 'contact_phone'=>$data['contact_phone'] ?: null, 'church_id'=>$tenant->churchId()]);
            $sql = 'INSERT INTO church_profiles (church_id, english_name, short_description, representative_name, representative_title, postal_code, address_line1, address_detail, map_url, website_url, youtube_url, instagram_url, facebook_url, updated_by) VALUES (:church_id,:english_name,:short_description,:representative_name,:representative_title,:postal_code,:address_line1,:address_detail,:map_url,:website_url,:youtube_url,:instagram_url,:facebook_url,:updated_by) ON DUPLICATE KEY UPDATE english_name=VALUES(english_name), short_description=VALUES(short_description), representative_name=VALUES(representative_name), representative_title=VALUES(representative_title), postal_code=VALUES(postal_code), address_line1=VALUES(address_line1), address_detail=VALUES(address_detail), map_url=VALUES(map_url), website_url=VALUES(website_url), youtube_url=VALUES(youtube_url), instagram_url=VALUES(instagram_url), facebook_url=VALUES(facebook_url), updated_by=VALUES(updated_by)';
            $profile = $this->pdo->prepare($sql);
            $values = ['church_id'=>$tenant->churchId(), 'updated_by'=>$userId];
            foreach (['english_name','short_description','representative_name','representative_title','postal_code','address_line1','address_detail','map_url','website_url','youtube_url','instagram_url','facebook_url'] as $field) $values[$field] = $data[$field] === '' ? null : $data[$field];
            $profile->execute($values);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
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
