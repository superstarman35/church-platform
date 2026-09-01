<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Session;use App\Core\TenantContext;use App\Core\View;use App\Repositories\InvitationStatsRepository;use App\Repositories\QuotaChangeRequestRepository;use App\Services\SubscriptionEntitlementService;use PDO;
final class TrafficAdminController
{
 public function __construct(private readonly PDO $pdo){}
 public function index():void
 {
  $tenant=TenantContext::fromSession();$stats=new InvitationStatsRepository($this->pdo);$entitlements=new SubscriptionEntitlementService($this->pdo);$snapshot=$entitlements->snapshot($tenant->churchId());$effective=(int)($entitlements->limit($snapshot,'traffic.monthly_bytes')??0);$extra=$stats->trafficExtraLimit($tenant);$used=(int)($stats->usage($tenant->churchId())['traffic_bytes']??0);$percent=$effective>0?(int)floor($used/$effective*100):0;
  View::render('admin.traffic.index',['title'=>'트래픽 상세','tenant'=>$tenant,'snapshot'=>$snapshot,'used'=>$used,'effective'=>$effective,'base'=>max(0,$effective-$extra),'extra'=>$extra,'remaining'=>max(0,$effective-$used),'percent'=>$percent,'level'=>$percent>=100?'blocked':($percent>=85?'danger':($percent>=70?'warning':'normal')),'nextReset'=>(new \DateTimeImmutable('first day of next month'))->format('Y-m-d'),'monthly'=>$stats->monthlyTraffic($tenant->churchId(),12),'daily'=>$stats->dailyTrafficForTenant($tenant,31),'top'=>$stats->topTrafficInvitationsForTenant($tenant,10),'requests'=>(new QuotaChangeRequestRepository($this->pdo))->historyForTenant($tenant,30),'success'=>Session::pullFlash('success'),'error'=>Session::pullFlash('error')]);
 }
}
