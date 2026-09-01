<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\TenantContext;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\InvitationStatsRepository;
use App\Repositories\QuotaChangeRequestRepository;
use App\Repositories\SubscriptionChangeRequestRepository;
use App\Repositories\AuditLogRepository;
use App\Services\SubscriptionEntitlementService;
use PDO;
use RuntimeException;

final class SubscriptionController
{
    public function __construct(private readonly PDO $pdo) {}

    public function index(): void
    {
        $tenant = TenantContext::fromSession();
        $subscription = (new SubscriptionEntitlementService($this->pdo))->snapshot($tenant->churchId());
        $usage = (new InvitationStatsRepository($this->pdo))->usage($tenant->churchId());
        $storage = $this->pdo->prepare('SELECT COALESCE(SUM(file_bytes),0) FROM invitation_media WHERE church_id=:church_id');
        $storage->execute(['church_id' => $tenant->churchId()]);
        $usage['storage_bytes'] = (int) $storage->fetchColumn();
        $changes = new SubscriptionChangeRequestRepository($this->pdo);
        View::render('admin.subscription', [
            'title' => '구독·사용 한도', 'subscription' => $subscription, 'usage' => $usage,
            'requests' => (new QuotaChangeRequestRepository($this->pdo))->historyForTenant($tenant),
            'plans' => $changes->availableInvitationPlans(),
            'changeRequests' => $changes->historyForTenant($tenant),
            'canRequestChange' => in_array($tenant->role(), ['owner', 'admin'], true),
            'success' => Session::pullFlash('success'), 'error' => Session::pullFlash('error'),
        ]);
    }

    public function requestChange(): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $tenant = TenantContext::fromSession();
        $auth = Session::get('auth', []);
        try {
            $id = (new SubscriptionChangeRequestRepository($this->pdo))->create(
                $tenant,
                (int) ($auth['user_id'] ?? 0),
                (int) ($_POST['plan_id'] ?? 0),
                trim((string) ($_POST['reason'] ?? ''))
            );
            (new AuditLogRepository($this->pdo))->record((int) ($auth['user_id'] ?? 0), $tenant->churchId(), 'subscription_change.requested', 'subscription_change_request', $id);
            Session::flash('success', '유료 전환 검토를 요청했습니다. 결제 확인 전에는 현재 요금제가 유지됩니다.');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/admin/subscription');
    }
}
