<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class SubscriptionEntitlementService
{
    public function __construct(private readonly PDO $pdo) {}

    public function snapshot(int $churchId): array
    {
        $sql = "SELECT s.status, s.trial_ends_at, s.current_period_starts_at, s.current_period_ends_at, p.name plan_name, p.code plan_code, pf.feature_code, pf.enabled, pf.limit_value
                FROM subscriptions s
                JOIN plans p ON p.id = s.plan_id
                LEFT JOIN plan_features pf ON pf.plan_id = p.id
                WHERE s.church_id = :church_id
                ORDER BY s.id DESC";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['church_id' => $churchId]);
        $rows = $statement->fetchAll();
        if ($rows === []) {
            throw new RuntimeException('사용 중인 구독 정보를 찾을 수 없습니다.');
        }
        $subscriptionId = null;
        $features = [];
        foreach ($rows as $row) {
            $subscriptionId ??= $row['plan_code'];
            if ($row['plan_code'] !== $subscriptionId) {
                continue;
            }
            if ($row['feature_code'] !== null) {
                $features[(string) $row['feature_code']] = [
                    'enabled' => (bool) $row['enabled'],
                    'limit' => $row['limit_value'] === null ? null : (int) $row['limit_value'],
                ];
            }
        }
        $first = $rows[0];
        $expired = $first['status'] === 'expired'
            || ($first['status'] === 'trialing' && $first['trial_ends_at'] !== null && strtotime((string) $first['trial_ends_at']) < time());
        return [
            'status' => $expired ? 'expired' : (string) $first['status'],
            'plan_name' => (string) $first['plan_name'],
            'plan_code' => (string) $first['plan_code'],
            'trial_ends_at' => $first['trial_ends_at'],
            'period_starts_at' => $first['current_period_starts_at'],
            'period_ends_at' => $first['current_period_ends_at'],
            'features' => $features,
        ];
    }

    public function limit(array $snapshot, string $feature): ?int
    {
        return isset($snapshot['features'][$feature]) ? $snapshot['features'][$feature]['limit'] : null;
    }

    public function assertUsable(array $snapshot): void
    {
        if (!in_array($snapshot['status'], ['trialing', 'active'], true)) {
            throw new RuntimeException('구독이 만료되었거나 중지되어 변경할 수 없습니다.');
        }
        if (!($snapshot['features']['invitation.enabled']['enabled'] ?? false)) {
            throw new RuntimeException('현재 구독에서 초대장 기능을 사용할 수 없습니다.');
        }
    }

    public function assertBelow(array $snapshot, string $feature, int $current, string $message): void
    {
        $limit = $this->limit($snapshot, $feature);
        if ($limit !== null && $current >= $limit) {
            throw new RuntimeException($message);
        }
    }
}

