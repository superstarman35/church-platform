<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
use App\Core\Uuid;
use PDO;

final class InvitationMediaRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function replaceHero(TenantContext $tenant, int $invitationId, array $file): ?array
    {
        $current=$this->heroForTenant($tenant,$invitationId);
        $this->pdo->beginTransaction();
        try {
            $delete=$this->pdo->prepare("DELETE FROM invitation_media WHERE church_id=:church_id AND invitation_id=:invitation_id AND kind='hero'");
            $delete->execute(['church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId]);
            $insert=$this->pdo->prepare("INSERT INTO invitation_media (uuid,church_id,invitation_id,kind,file_path,mime_type,file_bytes,width,height) VALUES (:uuid,:church_id,:invitation_id,'hero',:file_path,'image/webp',:file_bytes,:width,:height)");
            $insert->execute(['uuid'=>$file['uuid'],'church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId,'file_path'=>$file['file_path'],'file_bytes'=>$file['file_bytes'],'width'=>$file['width'],'height'=>$file['height']]);
            $this->pdo->commit();
            return $current;
        } catch(\Throwable $e) {
            if($this->pdo->inTransaction())$this->pdo->rollBack();
            throw $e;
        }
    }

    public function heroForTenant(TenantContext $tenant,int $invitationId):?array
    {
        $statement=$this->pdo->prepare("SELECT m.* FROM invitation_media m JOIN invitations i ON i.id=m.invitation_id AND i.church_id=m.church_id WHERE m.church_id=:church_id AND m.invitation_id=:invitation_id AND m.kind='hero' LIMIT 1");
        $statement->execute(['church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId]);
        $row=$statement->fetch(); return is_array($row)?$row:null;
    }

    public function findPublic(string $uuid):?array
    {
        $sql="SELECT m.* FROM invitation_media m JOIN invitations i ON i.id=m.invitation_id AND i.church_id=m.church_id JOIN churches c ON c.id=m.church_id WHERE m.uuid=:uuid AND i.status='published' AND c.status IN ('trial','active') LIMIT 1";
        $statement=$this->pdo->prepare($sql);$statement->execute(['uuid'=>$uuid]);$row=$statement->fetch();
        return is_array($row)?$row:null;
    }

    public function storageBytes(int $churchId):int
    {
        $statement=$this->pdo->prepare('SELECT COALESCE(SUM(file_bytes),0) FROM invitation_media WHERE church_id=:church_id');
        $statement->execute(['church_id'=>$churchId]);return (int)$statement->fetchColumn();
    }
}
