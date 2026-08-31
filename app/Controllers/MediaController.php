<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Repositories\InvitationMediaRepository;
use PDO;

final class MediaController
{
    public function __construct(private readonly PDO $pdo) {}
    public function show(string $uuid):void
    {
        if(preg_match('/^[0-9a-f-]{36}$/i',$uuid)!==1)Response::abort(404,'이미지를 찾을 수 없습니다.');
        $media=(new InvitationMediaRepository($this->pdo))->findPublic($uuid);
        if($media===null)Response::abort(404,'이미지를 찾을 수 없습니다.');
        $root=realpath(dirname(__DIR__,2).'/storage/uploads');
        $file=realpath(dirname(__DIR__,2).'/storage/uploads/'.ltrim((string)$media['file_path'],'/'));
        if($root===false||$file===false||!str_starts_with($file,$root.DIRECTORY_SEPARATOR)||!is_file($file))Response::abort(404,'이미지 파일을 찾을 수 없습니다.');
        header('Content-Type: image/webp');header('Content-Length: '.filesize($file));header('Cache-Control: public, max-age=86400, immutable');
        readfile($file);
    }
}
