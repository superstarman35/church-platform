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
            $insert=$this->pdo->prepare("INSERT INTO invitation_media (uuid,church_id,invitation_id,kind,file_path,mime_type,original_file_bytes,file_bytes,width,height) VALUES (:uuid,:church_id,:invitation_id,'hero',:file_path,'image/webp',:original_file_bytes,:file_bytes,:width,:height)");
            $insert->execute(['uuid'=>$file['uuid'],'church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId,'file_path'=>$file['file_path'],'original_file_bytes'=>$file['original_file_bytes'],'file_bytes'=>$file['file_bytes'],'width'=>$file['width'],'height'=>$file['height']]);
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
        $sql="SELECT m.* FROM invitation_media m
              JOIN invitations i ON i.id=m.invitation_id AND i.church_id=m.church_id
              JOIN churches c ON c.id=m.church_id
              WHERE m.uuid=:uuid
                AND m.deleted_at IS NULL AND i.status='published'
                AND i.deleted_at IS NULL
                AND (i.publish_at IS NULL OR i.publish_at <= NOW())
                AND (i.expires_at IS NULL OR i.expires_at > NOW())
                AND c.status IN ('trial','active')
              LIMIT 1";
        $statement=$this->pdo->prepare($sql);$statement->execute(['uuid'=>$uuid]);$row=$statement->fetch();
        return is_array($row)?$row:null;
    }

    public function galleryForTenant(TenantContext $tenant,int $invitationId):array
    {
        $statement=$this->pdo->prepare("SELECT m.* FROM invitation_media m JOIN invitations i ON i.id=m.invitation_id AND i.church_id=m.church_id WHERE m.church_id=:church_id AND m.invitation_id=:invitation_id AND m.kind='gallery' AND m.deleted_at IS NULL ORDER BY m.sort_order,m.id");
        $statement->execute(['church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId]);
        return $statement->fetchAll();
    }

    public function galleryPublic(int $churchId,int $invitationId):array
    {
        $statement=$this->pdo->prepare("SELECT uuid,width,height,alt_text FROM invitation_media WHERE church_id=:church_id AND invitation_id=:invitation_id AND kind='gallery' AND deleted_at IS NULL ORDER BY sort_order,id");
        $statement->execute(['church_id'=>$churchId,'invitation_id'=>$invitationId]);
        return $statement->fetchAll();
    }

    public function addGallery(TenantContext $tenant,int $invitationId,array $file):void
    {
        $statement=$this->pdo->prepare("INSERT INTO invitation_media (uuid,church_id,invitation_id,kind,file_path,mime_type,original_file_bytes,file_bytes,width,height,sort_order) SELECT :uuid,:church_id,:invitation_id,'gallery',:file_path,'image/webp',:original_file_bytes,:file_bytes,:width,:height,COALESCE(MAX(m.sort_order),-1)+1 FROM invitation_media m WHERE m.church_id=:church_id_order AND m.invitation_id=:invitation_id_order AND m.kind='gallery'");
        $statement->execute(['uuid'=>$file['uuid'],'church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId,'file_path'=>$file['file_path'],'original_file_bytes'=>$file['original_file_bytes'],'file_bytes'=>$file['file_bytes'],'width'=>$file['width'],'height'=>$file['height'],'church_id_order'=>$tenant->churchId(),'invitation_id_order'=>$invitationId]);
    }
    public function deleteGallery(TenantContext $tenant, int $invitationId, int $mediaId): ?array
    {
        $select = $this->pdo->prepare("SELECT m.* FROM invitation_media m JOIN invitations i ON i.id=m.invitation_id AND i.church_id=m.church_id WHERE m.id=:id AND m.church_id=:church_id AND m.invitation_id=:invitation_id AND m.kind='gallery' LIMIT 1");
        $select->execute(['id'=>$mediaId,'church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId]);
        $media = $select->fetch();
        if (!is_array($media)) return null;
        $delete = $this->pdo->prepare("UPDATE invitation_media SET deleted_at=NOW() WHERE id=:id AND church_id=:church_id AND invitation_id=:invitation_id AND kind='gallery' AND deleted_at IS NULL");
        $delete->execute(['id'=>$mediaId,'church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId]);
        return $delete->rowCount() === 1 ? $media : null;
    }
    public function storageBytes(int $churchId):int
    {
        $statement=$this->pdo->prepare('SELECT COALESCE(SUM(file_bytes),0) FROM invitation_media WHERE church_id=:church_id');
        $statement->execute(['church_id'=>$churchId]);return (int)$statement->fetchColumn();
    }
    public function activeBelongsToInvitation(TenantContext $tenant,int $invitationId,int $mediaId):bool{$s=$this->pdo->prepare('SELECT COUNT(*) FROM invitation_media WHERE id=:id AND church_id=:church_id AND invitation_id=:invitation_id AND deleted_at IS NULL');$s->execute(['id'=>$mediaId,'church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId]);return(int)$s->fetchColumn()===1;}
    public function listForTenant(TenantContext $tenant,array $filters):array
    {
        $state=in_array($filters['state']??'active',['active','trash','unused','all'],true)?$filters['state']:'active';$sort=in_array($filters['sort']??'newest',['newest','oldest','size_desc','name'],true)?$filters['sort']:'newest';$where=['m.church_id=:church_id'];
        if($state==='active')$where[]='m.deleted_at IS NULL';elseif($state==='trash')$where[]='m.deleted_at IS NOT NULL';elseif($state==='unused')$where[]='m.deleted_at IS NULL AND i.deleted_at IS NOT NULL';if(($filters['size']??'all')==='large')$where[]='m.file_bytes>=524288';
        $params=['church_id'=>$tenant->churchId()];$q=trim((string)($filters['q']??''));if($q!==''){$where[]='(i.title LIKE :q OR m.alt_text LIKE :q)';$params['q']='%'.str_replace(['%','_'],['\\%','\\_'],$q).'%';}
        $order=['newest'=>'m.created_at DESC','oldest'=>'m.created_at ASC','size_desc'=>'m.file_bytes DESC','name'=>'i.title ASC,m.id ASC'][$sort];$s=$this->pdo->prepare('SELECT m.*,i.title invitation_title,i.deleted_at invitation_deleted_at FROM invitation_media m JOIN invitations i ON i.id=m.invitation_id AND i.church_id=m.church_id WHERE '.implode(' AND ',$where).' ORDER BY '.$order.' LIMIT 200');$s->execute($params);return $s->fetchAll();
    }
    public function storageSummary(TenantContext $tenant):array{$s=$this->pdo->prepare('SELECT COALESCE(SUM(file_bytes),0) total_bytes,COALESCE(SUM(CASE WHEN deleted_at IS NULL THEN file_bytes ELSE 0 END),0) active_bytes,COALESCE(SUM(CASE WHEN deleted_at IS NOT NULL THEN file_bytes ELSE 0 END),0) trash_bytes,COALESCE(SUM(original_file_bytes),0) original_upload_bytes,COUNT(*) file_count FROM invitation_media WHERE church_id=:church_id');$s->execute(['church_id'=>$tenant->churchId()]);return $s->fetch()?:[];}
    public function updateMetadata(TenantContext $tenant,int $id,string $alt,bool $consent):bool{$s=$this->pdo->prepare('UPDATE invitation_media SET alt_text=:alt,usage_consent=:consent WHERE id=:id AND church_id=:church_id AND deleted_at IS NULL');$s->execute(['alt'=>$alt?:null,'consent'=>$consent?1:0,'id'=>$id,'church_id'=>$tenant->churchId()]);return $s->rowCount()===1;}
    public function trash(TenantContext $tenant,int $id,int $userId):bool{$s=$this->pdo->prepare('UPDATE invitation_media SET deleted_at=NOW(),deleted_by=:user_id WHERE id=:id AND church_id=:church_id AND deleted_at IS NULL');$s->execute(['user_id'=>$userId,'id'=>$id,'church_id'=>$tenant->churchId()]);return $s->rowCount()===1;}
    public function restore(TenantContext $tenant,int $id):bool{$s=$this->pdo->prepare('UPDATE invitation_media SET deleted_at=NULL,deleted_by=NULL WHERE id=:id AND church_id=:church_id AND deleted_at IS NOT NULL');$s->execute(['id'=>$id,'church_id'=>$tenant->churchId()]);return $s->rowCount()===1;}
}
