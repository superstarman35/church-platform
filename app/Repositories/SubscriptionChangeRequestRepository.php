<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
use PDO;
use RuntimeException;

final class SubscriptionChangeRequestRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function availableInvitationPlans(): array
    {
        return $this->pdo->query("SELECT p.id,p.code,p.name,p.price_krw FROM plans p JOIN products pr ON pr.id=p.product_id WHERE pr.product_family='invitation' AND p.billing_cycle='monthly' AND p.status='active' AND p.price_krw > 0 AND p.trial_days IS NULL ORDER BY p.price_krw,p.id")->fetchAll();
    }

    public function create(TenantContext $tenant, int $userId, int $planId, string $reason): int
    {
        if (!in_array($tenant->role(), ['owner','admin'], true)) throw new RuntimeException('대표 관리자 또는 관리자 권한이 필요합니다.');
        if (mb_strlen($reason) > 500) throw new RuntimeException('요청 메모는 500자 이하로 입력해 주세요.');
        $plan=$this->pdo->prepare("SELECT COUNT(*) FROM plans p JOIN products pr ON pr.id=p.product_id WHERE p.id=:id AND p.status='active' AND p.billing_cycle='monthly' AND p.price_krw > 0 AND p.trial_days IS NULL AND pr.product_family='invitation'");
        $plan->execute(['id'=>$planId]);
        if ((int)$plan->fetchColumn()!==1) throw new RuntimeException('선택할 수 없는 구독 상품입니다.');
        $this->pdo->beginTransaction();
        try {
            $lock=$this->pdo->prepare('SELECT id FROM subscriptions WHERE church_id=:church_id ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $lock->execute(['church_id'=>$tenant->churchId()]);
            if ($lock->fetchColumn()===false) throw new RuntimeException('현재 구독 정보를 찾을 수 없습니다.');
            $pending=$this->pdo->prepare("SELECT COUNT(*) FROM subscription_change_requests WHERE church_id=:church_id AND status IN ('pending','awaiting_payment')");
            $pending->execute(['church_id'=>$tenant->churchId()]);
            if ((int)$pending->fetchColumn()>0) throw new RuntimeException('처리 중인 구독 전환 요청이 이미 있습니다.');
            $statement=$this->pdo->prepare('INSERT INTO subscription_change_requests(church_id,requested_by,requested_plan_id,reason) VALUES(:church_id,:requested_by,:plan_id,:reason)');
            $statement->execute(['church_id'=>$tenant->churchId(),'requested_by'=>$userId,'plan_id'=>$planId,'reason'=>$reason!==''?$reason:null]);
            $id=(int)$this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function historyForTenant(TenantContext $tenant): array
    {
        $statement=$this->pdo->prepare('SELECT r.id,r.status,r.reason,r.review_note,r.created_at,r.reviewed_at,p.name plan_name,p.price_krw FROM subscription_change_requests r JOIN plans p ON p.id=r.requested_plan_id WHERE r.church_id=:church_id ORDER BY r.id DESC LIMIT 20');
        $statement->execute(['church_id'=>$tenant->churchId()]);
        return $statement->fetchAll();
    }

    public function pendingForPlatform(): array
    {
        return $this->pdo->query("SELECT r.id,r.church_id,r.status,r.reason,r.review_note,r.created_at,c.name church_name,u.name requester_name,p.name plan_name,p.price_krw FROM subscription_change_requests r JOIN churches c ON c.id=r.church_id JOIN users u ON u.id=r.requested_by JOIN plans p ON p.id=r.requested_plan_id WHERE r.status IN ('pending','awaiting_payment') ORDER BY r.created_at,r.id")->fetchAll();
    }

    public function review(int $id,int $reviewerId,string $decision,string $note): array
    {
        if(!in_array($decision,['awaiting_payment','rejected'],true)) throw new RuntimeException('지원하지 않는 처리 상태입니다.');
        if(mb_strlen($note)>500) throw new RuntimeException('처리 메모는 500자 이하로 입력해 주세요.');
        $statement=$this->pdo->prepare("UPDATE subscription_change_requests SET status=:status,reviewed_by=:reviewer,reviewed_at=NOW(),review_note=:note WHERE id=:id AND status='pending'");
        $statement->execute(['status'=>$decision,'reviewer'=>$reviewerId,'note'=>$note!==''?$note:null,'id'=>$id]);
        if($statement->rowCount()!==1) throw new RuntimeException('이미 처리되었거나 존재하지 않는 요청입니다.');
        $find=$this->pdo->prepare('SELECT church_id,requested_plan_id FROM subscription_change_requests WHERE id=:id');
        $find->execute(['id'=>$id]); return (array)$find->fetch();
    }

    public function complete(int $id,int $reviewerId,string $paymentReference,string $note): array
    {
        $paymentReference=trim($paymentReference);
        if($paymentReference===''||mb_strlen($paymentReference)>100)throw new RuntimeException('결제 확인 참조값은 1~100자로 입력해 주세요.');
        if(mb_strlen($note)>500)throw new RuntimeException('처리 메모는 500자 이하로 입력해 주세요.');
        $this->pdo->beginTransaction();
        try{
            $request=$this->pdo->prepare("SELECT r.id,r.church_id,r.requested_plan_id,r.status,p.billing_cycle,p.status plan_status FROM subscription_change_requests r JOIN plans p ON p.id=r.requested_plan_id WHERE r.id=:id FOR UPDATE");
            $request->execute(['id'=>$id]);$row=$request->fetch();
            if(!$row||$row['status']!=='awaiting_payment')throw new RuntimeException('결제 확인 대기 중인 요청만 완료할 수 있습니다.');
            if($row['billing_cycle']!=='monthly'||$row['plan_status']!=='active')throw new RuntimeException('현재 적용할 수 없는 상품입니다.');
            $current=$this->pdo->prepare('SELECT id FROM subscriptions WHERE church_id=:church_id ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $current->execute(['church_id'=>$row['church_id']]);$previousId=$current->fetchColumn();
            if($previousId===false)throw new RuntimeException('기존 구독 정보를 찾을 수 없습니다.');
            $close=$this->pdo->prepare("UPDATE subscriptions SET status=CASE WHEN status='trialing' THEN 'expired' ELSE 'cancelled' END,cancelled_at=CASE WHEN status='trialing' THEN cancelled_at ELSE NOW() END,current_period_ends_at=LEAST(current_period_ends_at,NOW()) WHERE id=:id AND church_id=:church_id");
            $close->execute(['id'=>$previousId,'church_id'=>$row['church_id']]);
            $insert=$this->pdo->prepare("INSERT INTO subscriptions(church_id,plan_id,status,starts_at,current_period_starts_at,current_period_ends_at) VALUES(:church_id,:plan_id,'active',NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 1 MONTH))");
            $insert->execute(['church_id'=>$row['church_id'],'plan_id'=>$row['requested_plan_id']]);$subscriptionId=(int)$this->pdo->lastInsertId();
            $church=$this->pdo->prepare("UPDATE churches SET status='active',product_family='invitation' WHERE id=:church_id");$church->execute(['church_id'=>$row['church_id']]);
            $done=$this->pdo->prepare("UPDATE subscription_change_requests SET status='completed',reviewed_by=:reviewer,reviewed_at=NOW(),review_note=:note,payment_confirmed_by=:reviewer,payment_confirmed_at=NOW(),payment_reference=:reference WHERE id=:id AND status='awaiting_payment'");
            $done->execute(['reviewer'=>$reviewerId,'note'=>$note!==''?$note:null,'reference'=>$paymentReference,'id'=>$id]);
            if($done->rowCount()!==1)throw new RuntimeException('구독 전환 요청 상태가 변경되었습니다.');
            $this->pdo->commit();return ['church_id'=>(int)$row['church_id'],'requested_plan_id'=>(int)$row['requested_plan_id'],'previous_subscription_id'=>(int)$previousId,'subscription_id'=>$subscriptionId,'payment_reference'=>$paymentReference];
        }catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
    }
}
