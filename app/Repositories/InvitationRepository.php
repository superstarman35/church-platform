<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
use App\Core\Uuid;
use PDO;

final class InvitationRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listForTenant(TenantContext $tenant): array
    {
        $sql = "SELECT i.*, c.slug church_slug,
                (SELECT COALESCE(SUM(ds.views),0) FROM invitation_daily_stats ds WHERE ds.church_id=i.church_id AND ds.invitation_id=i.id) views,
                (SELECT COALESCE(SUM(ds.applications),0) FROM invitation_daily_stats ds WHERE ds.church_id=i.church_id AND ds.invitation_id=i.id) application_count
                FROM invitations i JOIN churches c ON c.id=i.church_id
                WHERE i.church_id = :church_id ORDER BY (i.deleted_at IS NOT NULL), i.id DESC";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_id' => $tenant->churchId()]);
        return $statement->fetchAll();
    }

    public function findForTenant(TenantContext $tenant, int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT i.*, c.slug church_slug FROM invitations i JOIN churches c ON c.id=i.church_id WHERE i.id = :id AND i.church_id = :church_id LIMIT 1');
        $statement->execute(['id' => $id, 'church_id' => $tenant->churchId()]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function findPublished(string $churchSlug, string $slug): ?array
    {
        $sql = "SELECT i.*, c.name church_name, c.slug church_slug, (SELECT m.uuid FROM invitation_media m WHERE m.church_id=i.church_id AND m.invitation_id=i.id AND m.kind='hero' LIMIT 1) hero_uuid
                FROM invitations i JOIN churches c ON c.id = i.church_id
                WHERE c.slug = :church_slug AND i.slug = :slug AND i.status = 'published'
                  AND i.deleted_at IS NULL
                  AND (i.publish_at IS NULL OR i.publish_at <= NOW())
                  AND (i.expires_at IS NULL OR i.expires_at > NOW())
                  AND c.status IN ('trial', 'active') LIMIT 1";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_slug' => $churchSlug, 'slug' => $slug]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function slugExists(int $churchId, string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM invitations WHERE church_id = :church_id AND slug = :slug';
        $params = ['church_id' => $churchId, 'slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptId;
        }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        return (bool) $statement->fetchColumn();
    }

    public function create(TenantContext $tenant, array $data, int $userId): int
    {
        $sql = "INSERT INTO invitations
            (uuid, church_id, slug, title, event_type, template_code, visibility, access_password_hash, share_media_id, summary, body, event_at, registration_starts_at, registration_ends_at, capacity, waitlist_enabled, venue_name, venue_address, map_url, youtube_url, contact_name, contact_phone, publish_at, expires_at, created_by, updated_by)
            VALUES (:uuid, :church_id, :slug, :title, :event_type, :template_code, :visibility, :access_password_hash, :share_media_id, :summary, :body, :event_at, :registration_starts_at, :registration_ends_at, :capacity, :waitlist_enabled, :venue_name, :venue_address, :map_url, :youtube_url, :contact_name, :contact_phone, :publish_at, :expires_at, :created_by, :updated_by)";
        $statement = $this->pdo->prepare($sql);
        $statement->execute($this->parameters($tenant->churchId(), $data, $userId) + ['uuid' => Uuid::v4(), 'created_by' => $userId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(TenantContext $tenant, int $id, array $data, int $userId): bool
    {
        $sql = "UPDATE invitations SET slug=:slug, title=:title, event_type=:event_type, template_code=:template_code,visibility=:visibility,access_password_hash=COALESCE(:access_password_hash,access_password_hash),share_media_id=:share_media_id,
                summary=:summary, body=:body, event_at=:event_at, venue_name=:venue_name, venue_address=:venue_address,
                map_url=:map_url, youtube_url=:youtube_url, contact_name=:contact_name, contact_phone=:contact_phone,
                registration_starts_at=:registration_starts_at,registration_ends_at=:registration_ends_at,capacity=:capacity,waitlist_enabled=:waitlist_enabled,publish_at=:publish_at, expires_at=:expires_at,
                updated_by=:updated_by WHERE id=:id AND church_id=:church_id";
        $statement = $this->pdo->prepare($sql);
        return $statement->execute($this->parameters($tenant->churchId(), $data, $userId) + ['id' => $id]);
    }

    public function cloneForTenant(TenantContext $tenant, int $sourceId, string $slug, int $userId): ?int
    {
        $source = $this->findForTenant($tenant, $sourceId);
        if ($source === null) {
            return null;
        }
        $source['slug'] = $slug;
        $source['title'] = $source['title'] . ' 복사본';
        return $this->create($tenant, $source, $userId);
    }

    public function setStatus(TenantContext $tenant, int $id, string $status, int $userId): bool
    {
        $timeColumn = $status === 'published' ? 'published_at = NOW(), ended_at = NULL' : 'ended_at = NOW()';
        $statement = $this->pdo->prepare("UPDATE invitations SET status=:status, {$timeColumn}, updated_by=:user_id WHERE id=:id AND church_id=:church_id");
        $statement->execute(['status' => $status, 'user_id' => $userId, 'id' => $id, 'church_id' => $tenant->churchId()]);
        return $statement->rowCount() === 1;
    }

    public function setDeleted(TenantContext $tenant, int $id, bool $deleted, int $userId): bool
    {
        $sql = $deleted
            ? "UPDATE invitations SET deleted_at=NOW(), status='ended', ended_at=NOW(), updated_by=:user_id WHERE id=:id AND church_id=:church_id AND deleted_at IS NULL"
            : "UPDATE invitations SET deleted_at=NULL, status='draft', ended_at=NULL, updated_by=:user_id WHERE id=:id AND church_id=:church_id AND deleted_at IS NOT NULL";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['user_id'=>$userId, 'id'=>$id, 'church_id'=>$tenant->churchId()]);
        return $statement->rowCount() === 1;
    }
    public function updateDesign(TenantContext $tenant,int $id,array $design,int $userId): bool
    {
        $statement=$this->pdo->prepare('UPDATE invitations SET template_code=:template_code,color_preset=:color_preset,font_preset=:font_preset,button_preset=:button_preset,updated_by=:updated_by WHERE id=:id AND church_id=:church_id AND deleted_at IS NULL');
        return $statement->execute($design+['updated_by'=>$userId,'id'=>$id,'church_id'=>$tenant->churchId()]);
    }
    public function countCreatedThisMonth(int $churchId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM invitations WHERE church_id=:church_id AND created_at >= DATE_FORMAT(NOW(), \'%Y-%m-01\')');
        $statement->execute(['church_id' => $churchId]);
        return (int) $statement->fetchColumn();
    }

    public function countPublished(int $churchId): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM invitations WHERE church_id=:church_id AND status='published' AND deleted_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())");
        $statement->execute(['church_id' => $churchId]);
        return (int) $statement->fetchColumn();
    }

    private function parameters(int $churchId, array $data, int $userId): array
    {
        return [
            'church_id' => $churchId, 'slug' => $data['slug'], 'title' => $data['title'],
            'event_type' => $data['event_type'], 'template_code' => $data['template_code'],'visibility'=>$data['visibility'],'access_password_hash'=>$data['access_password_hash']??null,'share_media_id'=>(int)$data['share_media_id']?:null,
            'summary' => $data['summary'] ?: null, 'body' => $data['body'] ?: null,
            'event_at' => $data['event_at'] ?: null,'registration_starts_at'=>$data['registration_starts_at']?:null,'registration_ends_at'=>$data['registration_ends_at']?:null,'capacity'=>(int)$data['capacity']?:null,'waitlist_enabled'=>!empty($data['waitlist_enabled'])?1:0, 'venue_name' => $data['venue_name'] ?: null,
            'venue_address' => $data['venue_address'] ?: null, 'map_url' => $data['map_url'] ?: null,
            'youtube_url' => $data['youtube_url'] ?: null, 'contact_name' => $data['contact_name'] ?: null,
            'contact_phone' => $data['contact_phone'] ?: null,
            'publish_at' => $data['publish_at'] ?: null, 'expires_at' => $data['expires_at'] ?: null,
            'updated_by' => $userId,
        ];
    }
}
