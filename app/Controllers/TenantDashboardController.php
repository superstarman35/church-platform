<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\TenantContext;
use App\Core\View;
use App\Repositories\ChurchRepository;
use App\Repositories\ChurchUserRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\InvitationStatsRepository;
use App\Services\SubscriptionEntitlementService;
use PDO;

final class TenantDashboardController
{
    public function __construct(private readonly PDO $pdo) {}

    public function index(): void
    {
        $tenant = TenantContext::fromSession();
        $church = (new ChurchRepository($this->pdo))->findForTenant($tenant->churchId(), $tenant->churchId());
        if ($church === null) {
            Response::abort(403, '현재 교회 정보를 조회할 수 없습니다.');
        }
        $subscription = (new SubscriptionEntitlementService($this->pdo))->snapshot($tenant->churchId());
        $items = (new InvitationRepository($this->pdo))->listForTenant($tenant);
        $usage = (new InvitationStatsRepository($this->pdo))->usage($tenant->churchId());
        $storage = $this->pdo->prepare('SELECT COALESCE(SUM(file_bytes),0) FROM invitation_media WHERE church_id=:church_id');
        $storage->execute(['church_id'=>$tenant->churchId()]);
        $usage['storage_bytes'] = (int)$storage->fetchColumn();
        View::render('admin.dashboard', [
            'title'=>'초대장 관리자','church'=>$church,'admins'=>(new ChurchUserRepository($this->pdo))->listForTenant($tenant),
            'tenant'=>$tenant,'subscription'=>$subscription,'items'=>$items,'usage'=>$usage,
        ]);
    }
}


