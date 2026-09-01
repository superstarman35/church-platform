<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\TenantContext;
use App\Core\Uuid;
use App\Repositories\InvitationMediaRepository;
use RuntimeException;

final class InvitationImageService
{
    private const MAX_UPLOAD_BYTES=1048576;
    public function __construct(private readonly InvitationMediaRepository $media,private readonly SubscriptionEntitlementService $entitlements) {}

    public function uploadHero(TenantContext $tenant,array $invitation,array $upload):void
    {
        if(($upload['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return;
        if(($upload['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($upload['tmp_name']??'')))throw new RuntimeException('이미지 업로드에 실패했습니다.');
        if((int)($upload['size']??0)>self::MAX_UPLOAD_BYTES)throw new RuntimeException('원본 이미지는 1MB 이하여야 합니다.');
        if(!extension_loaded('gd'))throw new RuntimeException('서버에 PHP GD 이미지 확장이 필요합니다.');
        $bytes=file_get_contents((string)$upload['tmp_name']);
        $source=$bytes===false?false:@imagecreatefromstring($bytes);
        if($source===false)throw new RuntimeException('JPG, PNG, WebP 이미지만 업로드할 수 있습니다.');
        $sourceWidth=imagesx($source);$sourceHeight=imagesy($source);
        $scale=min(1,1080/$sourceWidth,1350/$sourceHeight);
        $width=max(1,(int)floor($sourceWidth*$scale));$height=max(1,(int)floor($sourceHeight*$scale));
        $canvas=imagecreatetruecolor($width,$height);
        imagealphablending($canvas,false);imagesavealpha($canvas,true);
        imagecopyresampled($canvas,$source,0,0,0,0,$width,$height,$sourceWidth,$sourceHeight);
        $uuid=Uuid::v4();$relative=$tenant->churchId().'/'.$invitation['uuid'].'/'.$uuid.'.webp';
        $root=dirname(__DIR__,2).'/storage/uploads';$absolute=$root.'/'.$relative;$directory=dirname($absolute);
        if(!is_dir($directory)&&!mkdir($directory,0750,true)&&!is_dir($directory))throw new RuntimeException('이미지 저장 폴더를 만들 수 없습니다.');
        if(!imagewebp($canvas,$absolute,78)){throw new RuntimeException('이미지를 WebP로 변환하지 못했습니다.');}
        imagedestroy($source);imagedestroy($canvas);
        $fileBytes=(int)filesize($absolute);
        $snapshot=$this->entitlements->snapshot($tenant->churchId());$this->entitlements->assertUsable($snapshot);
        $current=$this->media->heroForTenant($tenant,(int)$invitation['id']);
        $used=$this->media->storageBytes($tenant->churchId())-(int)($current['file_bytes']??0);
        $limit=$this->entitlements->limit($snapshot,'storage.total_bytes');
        if($limit!==null&&$used+$fileBytes>$limit){@unlink($absolute);throw new RuntimeException('구독 저장 용량을 초과합니다.');}
        try{$old=$this->media->replaceHero($tenant,(int)$invitation['id'],['uuid'=>$uuid,'file_path'=>$relative,'original_file_bytes'=>(int)($upload['size']??0),'file_bytes'=>$fileBytes,'width'=>$width,'height'=>$height]);}
        catch(\Throwable $e){@unlink($absolute);throw $e;}
        if($old!==null){$oldPath=$root.'/'.ltrim((string)$old['file_path'],'/');if(is_file($oldPath))@unlink($oldPath);}
    }
    public function uploadGallery(TenantContext $tenant,array $invitation,array $uploads):void
    {
        $names=$uploads['name']??[];
        if(!is_array($names)||$names===[])return;
        $snapshot=$this->entitlements->snapshot($tenant->churchId());
        $this->entitlements->assertUsable($snapshot);
        $current=count($this->media->galleryForTenant($tenant,(int)$invitation['id']));
        $limit=$this->entitlements->limit($snapshot,'invitation.photos_per_item');
        $files=[];
        foreach(array_keys($names) as $index){if(($uploads['error'][$index]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$files[]=['name'=>$names[$index]??'','type'=>$uploads['type'][$index]??'','tmp_name'=>$uploads['tmp_name'][$index]??'','error'=>$uploads['error'][$index]??UPLOAD_ERR_NO_FILE,'size'=>$uploads['size'][$index]??0];}
        if($limit!==null&&$current+count($files)>$limit)throw new RuntimeException('초대장당 사진 수 한도를 초과합니다.');
        foreach($files as $upload){$this->uploadGalleryFile($tenant,$invitation,$upload,$snapshot);}
    }

    public function deleteGallery(TenantContext $tenant, int $invitationId, int $mediaId): bool
    {
        $deleted = $this->media->deleteGallery($tenant, $invitationId, $mediaId);
        if ($deleted === null) return false;
        return true;
    }
    private function uploadGalleryFile(TenantContext $tenant,array $invitation,array $upload,array $snapshot):void
    {
        if(($upload['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($upload['tmp_name']??'')))throw new RuntimeException('갤러리 이미지 업로드에 실패했습니다.');
        if((int)($upload['size']??0)>self::MAX_UPLOAD_BYTES)throw new RuntimeException('원본 이미지는 장당 1MB 이하여야 합니다.');
        if(!extension_loaded('gd'))throw new RuntimeException('서버에 PHP GD 이미지 확장이 필요합니다.');
        $bytes=file_get_contents((string)$upload['tmp_name']);$source=$bytes===false?false:@imagecreatefromstring($bytes);
        if($source===false)throw new RuntimeException('JPG, PNG, WebP 이미지만 업로드할 수 있습니다.');
        $sourceWidth=imagesx($source);$sourceHeight=imagesy($source);$scale=min(1,1080/$sourceWidth,1440/$sourceHeight);
        $width=max(1,(int)floor($sourceWidth*$scale));$height=max(1,(int)floor($sourceHeight*$scale));$canvas=imagecreatetruecolor($width,$height);
        imagealphablending($canvas,false);imagesavealpha($canvas,true);imagecopyresampled($canvas,$source,0,0,0,0,$width,$height,$sourceWidth,$sourceHeight);
        $uuid=Uuid::v4();$relative=$tenant->churchId().'/'.$invitation['uuid'].'/'.$uuid.'.webp';$root=dirname(__DIR__,2).'/storage/uploads';$absolute=$root.'/'.$relative;$directory=dirname($absolute);
        if(!is_dir($directory)&&!mkdir($directory,0750,true)&&!is_dir($directory))throw new RuntimeException('이미지 저장 폴더를 만들 수 없습니다.');
        if(!imagewebp($canvas,$absolute,78)){imagedestroy($source);imagedestroy($canvas);throw new RuntimeException('이미지를 WebP로 변환하지 못했습니다.');}
        imagedestroy($source);imagedestroy($canvas);$fileBytes=(int)filesize($absolute);$storageLimit=$this->entitlements->limit($snapshot,'storage.total_bytes');
        if($storageLimit!==null&&$this->media->storageBytes($tenant->churchId())+$fileBytes>$storageLimit){@unlink($absolute);throw new RuntimeException('구독 저장 용량을 초과합니다.');}
        try{$this->media->addGallery($tenant,(int)$invitation['id'],['uuid'=>$uuid,'file_path'=>$relative,'original_file_bytes'=>(int)($upload['size']??0),'file_bytes'=>$fileBytes,'width'=>$width,'height'=>$height]);}catch(\Throwable $e){@unlink($absolute);throw $e;}
    }
}
