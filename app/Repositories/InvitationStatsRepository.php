<?php

declare(strict_types=1);

namespace App\Repositories;

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

    public function usage(int $churchId): array
    {
        $sql = "SELECT
            COALESCE(SUM(CASE WHEN stat_date >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01') THEN traffic_bytes ELSE 0 END),0) traffic_bytes,
            COALESCE(SUM(views),0) views, COALESCE(SUM(shares),0) shares, COALESCE(SUM(applications),0) applications
            FROM invitation_daily_stats WHERE church_id=:church_id";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_id' => $churchId]);
        return (array) $statement->fetch();
    }
}

