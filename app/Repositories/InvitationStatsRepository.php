<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\TenantContext;
use PDO;

final class InvitationStatsRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function increment(int $churchId, int $invitationId, string $metric, int $trafficBytes = 0): void
    {
        if (!in_array($metric, ['views', 'shares', 'applications'], true)) {
            throw new \InvalidArgumentException('Unsupported statistic.');
        }
        $sql = "INSERT INTO invitation_daily_stats (church_id, invitation_id, stat_date, {$metric}, traffic_bytes)
                VALUES (:church_id, :invitation_id, CURRENT_DATE(), 1, :traffic_bytes)
                ON DUPLICATE KEY UPDATE {$metric}={$metric}+1, traffic_bytes=traffic_bytes+VALUES(traffic_bytes)";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_id' => $churchId, 'invitation_id' => $invitationId, 'traffic_bytes' => max(0, $trafficBytes)]);
    }

    public function recordTraffic(int $churchId, int $invitationId, int $trafficBytes): void
    {
        if ($trafficBytes <= 0) return;
        $sql = "INSERT INTO invitation_daily_stats (church_id, invitation_id, stat_date, traffic_bytes)
                VALUES (:church_id, :invitation_id, CURRENT_DATE(), :traffic_bytes)
                ON DUPLICATE KEY UPDATE traffic_bytes=traffic_bytes+VALUES(traffic_bytes)";
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'church_id' => $churchId,
            'invitation_id' => $invitationId,
            'traffic_bytes' => $trafficBytes,
        ]);
    }
    public function usage(int $churchId): array
    {
        $sql = "SELECT
            GREATEST(0, COALESCE(SUM(CASE WHEN stat_date >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') THEN traffic_bytes ELSE 0 END),0) - COALESCE((SELECT previous_bytes FROM traffic_reset_logs r WHERE r.church_id=:reset_church_id AND r.reset_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') ORDER BY r.reset_at DESC LIMIT 1),0)) traffic_bytes,
            COALESCE(SUM(views),0) views, COALESCE(SUM(shares),0) shares, COALESCE(SUM(applications),0) applications
            FROM invitation_daily_stats WHERE church_id=:church_id";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_id' => $churchId, 'reset_church_id' => $churchId]);
        return (array) $statement->fetch();
    }

    public function rawMonthlyTraffic(int $churchId): int
    {
        $statement = $this->pdo->prepare("SELECT COALESCE(SUM(traffic_bytes),0) FROM invitation_daily_stats WHERE church_id=:church_id AND stat_date >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')");
        $statement->execute(['church_id' => $churchId]);
        return (int) $statement->fetchColumn();
    }
    public function monthlyTraffic(int $churchId, int $months = 6): array
    {
        $months = max(1, min(24, $months));
        $sql = "SELECT DATE_FORMAT(stat_date, '%Y-%m') usage_month, COALESCE(SUM(traffic_bytes),0) traffic_bytes
                FROM invitation_daily_stats
                WHERE church_id=:church_id
                  AND stat_date >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL " . ($months - 1) . " MONTH), '%Y-%m-01')
                GROUP BY DATE_FORMAT(stat_date, '%Y-%m')
                ORDER BY usage_month DESC";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_id' => $churchId]);
        return $statement->fetchAll();
    }
    public function dailyTrafficForTenant(TenantContext $tenant,int $days=31):array
    {
        $days=max(1,min(93,$days));$s=$this->pdo->prepare("SELECT stat_date,COALESCE(SUM(traffic_bytes),0) traffic_bytes FROM invitation_daily_stats WHERE church_id=:church_id AND stat_date>=DATE_SUB(CURRENT_DATE(),INTERVAL ".($days-1)." DAY) GROUP BY stat_date ORDER BY stat_date DESC");$s->execute(['church_id'=>$tenant->churchId()]);return $s->fetchAll();
    }
    public function topTrafficInvitationsForTenant(TenantContext $tenant,int $limit=10):array
    {
        $limit=max(1,min(50,$limit));$s=$this->pdo->prepare("SELECT i.id,i.title,s.traffic_bytes,COALESCE(m.media_count,0) media_count,COALESCE(m.media_bytes,0) media_bytes FROM invitations i JOIN (SELECT invitation_id,SUM(traffic_bytes) traffic_bytes FROM invitation_daily_stats WHERE church_id=:stats_church_id AND stat_date>=DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') GROUP BY invitation_id) s ON s.invitation_id=i.id LEFT JOIN (SELECT invitation_id,COUNT(*) media_count,SUM(file_bytes) media_bytes FROM invitation_media WHERE church_id=:media_church_id AND deleted_at IS NULL GROUP BY invitation_id) m ON m.invitation_id=i.id WHERE i.church_id=:church_id ORDER BY s.traffic_bytes DESC LIMIT {$limit}");$s->execute(['stats_church_id'=>$tenant->churchId(),'media_church_id'=>$tenant->churchId(),'church_id'=>$tenant->churchId()]);return $s->fetchAll();
    }
    public function trafficExtraLimit(TenantContext $tenant):int{$s=$this->pdo->prepare("SELECT COALESCE(extra_limit,0) FROM quota_overrides WHERE church_id=:church_id AND feature_code='traffic.monthly_bytes'");$s->execute(['church_id'=>$tenant->churchId()]);return (int)$s->fetchColumn();}
    public function aggregateForTenant(TenantContext $tenant,string $from,string $to):array{$s=$this->pdo->prepare('SELECT COALESCE(SUM(views),0) views,COALESCE(SUM(shares),0) shares,COALESCE(SUM(applications),0) applications,COALESCE(SUM(traffic_bytes),0) traffic_bytes FROM invitation_daily_stats WHERE church_id=:church_id AND stat_date BETWEEN :date_from AND :date_to');$s->execute(['church_id'=>$tenant->churchId(),'date_from'=>$from,'date_to'=>$to]);return (array)$s->fetch();}
    public function dailyAggregateForTenant(TenantContext $tenant,string $from,string $to,int $limit=366):array{$limit=max(1,min(366,$limit));$s=$this->pdo->prepare("SELECT stat_date,SUM(views) views,SUM(shares) shares,SUM(applications) applications,SUM(traffic_bytes) traffic_bytes FROM invitation_daily_stats WHERE church_id=:church_id AND stat_date BETWEEN :date_from AND :date_to GROUP BY stat_date ORDER BY stat_date ASC LIMIT {$limit}");$s->execute(['church_id'=>$tenant->churchId(),'date_from'=>$from,'date_to'=>$to]);return $s->fetchAll();}
    public function popularForTenant(TenantContext $tenant,string $from,string $to,int $limit=20):array{$limit=max(1,min(50,$limit));$s=$this->pdo->prepare("SELECT i.id,i.title,SUM(s.views) views,SUM(s.shares) shares,SUM(s.applications) applications FROM invitation_daily_stats s JOIN invitations i ON i.id=s.invitation_id AND i.church_id=s.church_id WHERE s.church_id=:church_id AND s.stat_date BETWEEN :date_from AND :date_to GROUP BY i.id,i.title ORDER BY views DESC,applications DESC LIMIT {$limit}");$s->execute(['church_id'=>$tenant->churchId(),'date_from'=>$from,'date_to'=>$to]);return $s->fetchAll();}
    public function periodAggregateForTenant(TenantContext $tenant,string $from,string $to,string $period):array
    {
        $format=$period==='month'?'%Y-%m':'%x-W%v';$statement=$this->pdo->prepare("SELECT DATE_FORMAT(stat_date,'{$format}') period_label,SUM(views) views,SUM(shares) shares,SUM(applications) applications FROM invitation_daily_stats WHERE church_id=:church_id AND stat_date BETWEEN :date_from AND :date_to GROUP BY period_label ORDER BY period_label DESC LIMIT 60");$statement->execute(['church_id'=>$tenant->churchId(),'date_from'=>$from,'date_to'=>$to]);return $statement->fetchAll();
    }
    public function summaryForTenant(TenantContext $tenant, int $invitationId, string $from, string $to): array
    {
        $sql = "SELECT COALESCE(SUM(s.views),0) views, COALESCE(SUM(s.shares),0) shares,
                       COALESCE(SUM(s.applications),0) applications, COALESCE(SUM(s.traffic_bytes),0) traffic_bytes
                FROM invitation_daily_stats s
                JOIN invitations i ON i.id=s.invitation_id AND i.church_id=s.church_id
                WHERE s.church_id=:church_id AND s.invitation_id=:invitation_id
                  AND s.stat_date BETWEEN :date_from AND :date_to";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId,'date_from'=>$from,'date_to'=>$to]);
        return (array) $statement->fetch();
    }

    public function dailyForTenant(TenantContext $tenant, int $invitationId, string $from, string $to): array
    {
        $sql = "SELECT s.stat_date, s.views, s.shares, s.applications, s.traffic_bytes
                FROM invitation_daily_stats s
                JOIN invitations i ON i.id=s.invitation_id AND i.church_id=s.church_id
                WHERE s.church_id=:church_id AND s.invitation_id=:invitation_id
                  AND s.stat_date BETWEEN :date_from AND :date_to
                ORDER BY s.stat_date ASC";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_id'=>$tenant->churchId(),'invitation_id'=>$invitationId,'date_from'=>$from,'date_to'=>$to]);
        return $statement->fetchAll();
    }
}

