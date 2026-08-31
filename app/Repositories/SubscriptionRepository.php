<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;

final class SubscriptionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createInvitationTrial(int $churchId): int
    {
        $statement = $this->pdo->query("SELECT id, trial_days FROM plans WHERE code = 'invitation-trial' AND status = 'active' ORDER BY version DESC LIMIT 1");
        $plan = $statement->fetch();
        if (!is_array($plan)) {
            throw new RuntimeException('Invitation trial plan is not installed. Run migrations first.');
        }

        $days = (int) ($plan['trial_days'] ?? 30);
        $insert = $this->pdo->prepare('INSERT INTO subscriptions (church_id, plan_id, status, starts_at, trial_ends_at, current_period_starts_at, current_period_ends_at) VALUES (:church_id, :plan_id, \'trialing\', NOW(), DATE_ADD(NOW(), INTERVAL :trial_days DAY), NOW(), DATE_ADD(NOW(), INTERVAL :period_days DAY))');
        $insert->bindValue('church_id', $churchId, PDO::PARAM_INT);
        $insert->bindValue('plan_id', (int) $plan['id'], PDO::PARAM_INT);
        $insert->bindValue('trial_days', $days, PDO::PARAM_INT);
        $insert->bindValue('period_days', $days, PDO::PARAM_INT);
        $insert->execute();
        return (int) $this->pdo->lastInsertId();
    }
}
