<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
use PDO;
use RuntimeException;
use Throwable;

final class QuotaChangeRequestRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function pendingForTenant(TenantContext $tenant): array
    {
        $statement=$this->pdo->prepare("SELECT id,request_type,requested_bytes,reason,status,created_at FROM quota_change_requests WHERE church_id=:church_id AND status='pending' ORDER BY id DESC");
        $statement->execute(['church_id'=>$tenant->churchId()]);
        return $statement->fetchAll();
    }

    public function historyForTenant(TenantContext $tenant, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->pdo->prepare("SELECT id,request_type,requested_bytes,reason,status,review_note,created_at,reviewed_at FROM quota_change_requests WHERE church_id=:church_id ORDER BY id DESC LIMIT {$limit}");
        $statement->execute(['church_id' => $tenant->churchId()]);
        return $statement->fetchAll();
    }
    public function pendingForPlatform(): array
    {
        return $this->pdo->query("SELECT q.id,q.church_id,q.request_type,q.requested_bytes,q.reason,q.created_at,c.name church_name,u.name requester_name FROM quota_change_requests q JOIN churches c ON c.id=q.church_id JOIN users u ON u.id=q.requested_by WHERE q.status='pending' ORDER BY q.created_at ASC,q.id ASC")->fetchAll();
    }

    public function create(TenantContext $tenant,int $userId,string $type,?int $bytes,string $reason): int
    {
        if(!in_array($type,['traffic_reset','traffic_increase'],true)) throw new RuntimeException('지원하지 않는 요청 유형입니다.');
        if($type==='traffic_increase'&&($bytes===null||$bytes<1073741824||$bytes>1073741824000)) throw new RuntimeException('증액 요청은 1~1000GB 범위로 입력해 주세요.');
        if(mb_strlen($reason)<5||mb_strlen($reason)>500) throw new RuntimeException('요청 사유를 5~500자로 입력해 주세요.');
        $check=$this->pdo->prepare("SELECT COUNT(*) FROM quota_change_requests WHERE church_id=:church_id AND request_type=:request_type AND status='pending'");
        $check->execute(['church_id'=>$tenant->churchId(),'request_type'=>$type]);
        if((int)$check->fetchColumn()>0) throw new RuntimeException('같은 유형의 처리 대기 요청이 이미 있습니다.');
        $statement=$this->pdo->prepare("INSERT INTO quota_change_requests(church_id,requested_by,request_type,requested_bytes,reason) VALUES(:church_id,:requested_by,:request_type,:requested_bytes,:reason)");
        $statement->execute(['church_id'=>$tenant->churchId(),'requested_by'=>$userId,'request_type'=>$type,'requested_bytes'=>$type==='traffic_increase'?$bytes:null,'reason'=>$reason]);
        return (int)$this->pdo->lastInsertId();
    }

    public function review(int $requestId,int $reviewerId,string $decision,string $note): array
    {
        if(!in_array($decision,['approved','rejected'],true)) throw new RuntimeException('지원하지 않는 처리 상태입니다.');
        if(mb_strlen($note)>500) throw new RuntimeException('처리 메모는 500자 이하로 입력해 주세요.');
        try {
            $this->pdo->beginTransaction();
            $find=$this->pdo->prepare("SELECT * FROM quota_change_requests WHERE id=:id FOR UPDATE");
            $find->execute(['id'=>$requestId]);
            $request=$find->fetch();
            if(!$request||$request['status']!=='pending') throw new RuntimeException('이미 처리되었거나 존재하지 않는 요청입니다.');
            $before=0;
            $after=0;
            if($decision==='approved'&&$request['request_type']==='traffic_increase') {
                $current=$this->pdo->prepare("SELECT extra_limit FROM quota_overrides WHERE church_id=:church_id AND feature_code='traffic.monthly_bytes' FOR UPDATE");
                $current->execute(['church_id'=>$request['church_id']]);
                $before=(int)($current->fetchColumn()?:0);
                $after=$before+(int)$request['requested_bytes'];
                $save=$this->pdo->prepare("INSERT INTO quota_overrides(church_id,feature_code,extra_limit,updated_by,reason) VALUES(:church_id,'traffic.monthly_bytes',:extra_limit,:updated_by,:reason) ON DUPLICATE KEY UPDATE extra_limit=VALUES(extra_limit),updated_by=VALUES(updated_by),reason=VALUES(reason)");
                $save->execute(['church_id'=>$request['church_id'],'extra_limit'=>$after,'updated_by'=>$reviewerId,'reason'=>$note!==''?$note:$request['reason']]);
            }
            if($decision==='approved'&&$request['request_type']==='traffic_reset') {
                $raw=$this->pdo->prepare("SELECT COALESCE(SUM(traffic_bytes),0) FROM invitation_daily_stats WHERE church_id=:church_id AND stat_date>=DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01')");
                $raw->execute(['church_id'=>$request['church_id']]);
                $before=(int)$raw->fetchColumn();
                $reset=$this->pdo->prepare("INSERT INTO traffic_reset_logs(church_id,request_id,previous_bytes,processed_by,reason) VALUES(:church_id,:request_id,:previous_bytes,:processed_by,:reason)");
                $reset->execute(['church_id'=>$request['church_id'],'request_id'=>$requestId,'previous_bytes'=>$before,'processed_by'=>$reviewerId,'reason'=>$note!==''?$note:$request['reason']]);
            }
            $update=$this->pdo->prepare("UPDATE quota_change_requests SET status=:status,reviewed_by=:reviewed_by,reviewed_at=NOW(),review_note=:review_note WHERE id=:id AND status='pending'");
            $update->execute(['status'=>$decision,'reviewed_by'=>$reviewerId,'review_note'=>$note!==''?$note:null,'id'=>$requestId]);
            $this->pdo->commit();
            return ['id'=>$requestId,'church_id'=>(int)$request['church_id'],'request_type'=>$request['request_type'],'decision'=>$decision,'previous_value'=>$before,'new_value'=>$after];
        } catch(Throwable $e) {
            if($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
