<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Session;
use App\Core\TenantContext;
use App\Core\View;
use App\Repositories\ChurchRepository;
use App\Repositories\ChurchUserRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\InvitationStatsRepository;
use App\Repositories\QuotaChangeRequestRepository;
use App\Repositories\AuditLogRepository;
use App\Services\SubscriptionEntitlementService;
use PDO;

final class TenantDashboardController
{
    public function __construct(private readonly PDO $pdo) {}

    public function index(): void
    {
        $tenant = TenantContext::fromSession();
        $churchRepository = new ChurchRepository($this->pdo);
        $church = $churchRepository->findForTenant($tenant->churchId(), $tenant->churchId());
        if ($church === null) {
            Response::abort(403, '현재 교회 정보를 조회할 수 없습니다.');
        }
        $subscription = (new SubscriptionEntitlementService($this->pdo))->snapshot($tenant->churchId());
        $items = (new InvitationRepository($this->pdo))->listForTenant($tenant);
        $statsRepository = new InvitationStatsRepository($this->pdo);
        $usage = $statsRepository->usage($tenant->churchId());
        $storage = $this->pdo->prepare('SELECT COALESCE(SUM(file_bytes),0) FROM invitation_media WHERE church_id=:church_id');
        $storage->execute(['church_id'=>$tenant->churchId()]);
        $usage['storage_bytes'] = (int)$storage->fetchColumn();
        $trafficLimit = (int) ($subscription['features']['traffic.monthly_bytes']['limit'] ?? 0);
        $trafficPercent = $trafficLimit > 0 ? (int) floor((int) $usage['traffic_bytes'] / $trafficLimit * 100) : 0;
        $trafficLevel = $trafficPercent >= 100 ? 'blocked' : ($trafficPercent >= 85 ? 'danger' : ($trafficPercent >= 70 ? 'warning' : 'normal'));
        $profile = $churchRepository->profileForTenant($tenant) ?? [];
        $setupChecklist = [
            ['label'=>'교회·단체명 등록', 'done'=>!empty($profile['name'])],
            ['label'=>'담당자 연락처 등록', 'done'=>!empty($profile['contact_email']) || !empty($profile['contact_phone'])],
            ['label'=>'소개와 주소 등록', 'done'=>!empty($profile['short_description']) && !empty($profile['address_line1'])],
            ['label'=>'첫 초대장 만들기', 'done'=>$items !== []],
        ];
        $setupPercent = (int)round(count(array_filter($setupChecklist, static fn(array $item): bool => $item['done'])) / count($setupChecklist) * 100);
        View::render('admin.dashboard', [
            'title'=>'초대장 관리자','church'=>$church,'admins'=>(new ChurchUserRepository($this->pdo))->listForTenant($tenant),
            'tenant'=>$tenant,'subscription'=>$subscription,'items'=>$items,'usage'=>$usage,
            'trafficHistory'=>$statsRepository->monthlyTraffic($tenant->churchId()),
            'trafficPercent'=>$trafficPercent,'trafficLevel'=>$trafficLevel,
            'trafficResetsAt'=>(new \DateTimeImmutable('first day of next month'))->format('Y-m-d'),
            'quotaRequests'=>(new QuotaChangeRequestRepository($this->pdo))->pendingForTenant($tenant),
            'setupChecklist'=>$setupChecklist,'setupPercent'=>$setupPercent,
        ]);
    }

    public function requestQuotaChange(): void
    {
        $tenant=TenantContext::fromSession();
        $auth=Session::get('auth',[]);
        if(!in_array($tenant->role(),['owner','admin'],true)) Response::abort(403,'대표 관리자 또는 관리자 권한이 필요합니다.');
        $type=(string)($_POST['request_type']??'');
        $reason=trim((string)($_POST['reason']??''));
        $gb=(int)($_POST['requested_gb']??0);
        try {
            $id=(new QuotaChangeRequestRepository($this->pdo))->create($tenant,(int)($auth['user_id']??0),$type,$gb>0?$gb*1073741824:null,$reason);
            (new AuditLogRepository($this->pdo))->record((int)($auth['user_id']??0),$tenant->churchId(),'quota_change.requested','quota_change_request',$id,['request_type'=>$type,'requested_gb'=>$gb?:null]);
            Session::flash('success','요청을 접수했습니다. 플랫폼 운영자 승인 후 반영됩니다.');
        } catch(\RuntimeException $e) { Session::flash('error',$e->getMessage()); }
        Response::redirect('/admin/traffic');
    }
}
