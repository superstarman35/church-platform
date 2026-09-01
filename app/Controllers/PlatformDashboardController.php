<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\AuditLogRepository;
use App\Repositories\QuotaChangeRequestRepository;
use App\Repositories\SubscriptionChangeRequestRepository;
use App\Repositories\PlatformOperationsRepository;
use PDO;
use RuntimeException;

final class PlatformDashboardController
{
    public function __construct(private readonly PDO $pdo) {}

    public function index(): void
    {
        $query=mb_substr(trim((string)($_GET['q']??'')),0,100);
        $churchStatus=(string)($_GET['church_status']??'');
        $family=(string)($_GET['product_family']??'');
        $subscriptionStatus=(string)($_GET['subscription_status']??'');
        $churchCounts=$this->pdo->query("SELECT COUNT(*) total, SUM(status = 'trial') trials, SUM(status = 'active') active FROM churches")->fetch();
        $userCount=(int)$this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        View::render('control.dashboard',['title'=>'플랫폼 대시보드','churchCounts'=>$churchCounts?:[],'userCount'=>$userCount,'operations'=>(new PlatformOperationsRepository($this->pdo))->search($query,$churchStatus,$family,$subscriptionStatus),'filters'=>compact('query','churchStatus','family','subscriptionStatus'),'quotaRequests'=>(new QuotaChangeRequestRepository($this->pdo))->pendingForPlatform(),'subscriptionRequests'=>(new SubscriptionChangeRequestRepository($this->pdo))->pendingForPlatform(),'success'=>Session::pullFlash('success'),'error'=>Session::pullFlash('error')]);
    }

    public function reviewQuotaRequest(int $id,string $decision): void
    {
        Csrf::verify($_POST['_token']??null);
        $auth=Session::get('auth',[]);
        try {
            $result=(new QuotaChangeRequestRepository($this->pdo))->review($id,(int)($auth['user_id']??0),$decision,trim((string)($_POST['review_note']??'')));
            (new AuditLogRepository($this->pdo))->record((int)($auth['user_id']??0),$result['church_id'],'quota_change.'.$decision,'quota_change_request',$id,$result);
            Session::flash('success',$decision==='approved'?'한도 요청을 승인하고 반영했습니다.':'한도 요청을 반려했습니다.');
        } catch(RuntimeException $e) { Session::flash('error',$e->getMessage()); }
        Response::redirect('/control');
    }

    public function reviewSubscriptionRequest(int $id, string $decision): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $auth = Session::get('auth', []);
        try {
            $result = (new SubscriptionChangeRequestRepository($this->pdo))->review($id, (int) ($auth['user_id'] ?? 0), $decision, trim((string) ($_POST['review_note'] ?? '')));
            (new AuditLogRepository($this->pdo))->record((int) ($auth['user_id'] ?? 0), (int) $result['church_id'], 'subscription_change.' . $decision, 'subscription_change_request', $id, ['requested_plan_id' => (int) $result['requested_plan_id']]);
            Session::flash('success', $decision === 'awaiting_payment' ? '검토를 완료하고 결제 확인 대기로 전환했습니다. 현재 구독은 변경하지 않았습니다.' : '유료 전환 요청을 반려했습니다.');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/control');
    }

    public function completeSubscriptionRequest(int $id): void
    {
        Csrf::verify($_POST['_token']??null);$auth=Session::get('auth',[]);
        try{$result=(new SubscriptionChangeRequestRepository($this->pdo))->complete($id,(int)($auth['user_id']??0),trim((string)($_POST['payment_reference']??'')),trim((string)($_POST['review_note']??'')));
            (new AuditLogRepository($this->pdo))->record((int)($auth['user_id']??0),(int)$result['church_id'],'subscription_change.completed','subscription_change_request',$id,$result);
            Session::flash('success','결제 확인 기록과 함께 유료 구독 전환을 완료했습니다.');
        }catch(RuntimeException $e){Session::flash('error',$e->getMessage());}
        Response::redirect('/control');
    }
}
