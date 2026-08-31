<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\ChurchRepository;
use App\Repositories\ChurchUserRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\UserRepository;
use PDO;
use RuntimeException;
use Throwable;

final class ChurchProvisioningService
{
    private ChurchRepository $churches;
    private UserRepository $users;
    private ChurchUserRepository $memberships;
    private SubscriptionRepository $subscriptions;
    private AuditLogRepository $audit;

    public function __construct(private readonly PDO $pdo)
    {
        $this->churches = new ChurchRepository($pdo);
        $this->users = new UserRepository($pdo);
        $this->memberships = new ChurchUserRepository($pdo);
        $this->subscriptions = new SubscriptionRepository($pdo);
        $this->audit = new AuditLogRepository($pdo);
    }

    public function createChurchWithOwner(array $churchData, array $ownerData, int $actorUserId): int
    {
        if ($this->churches->slugExists($churchData['slug'])) {
            throw new RuntimeException('이미 사용 중인 서비스 주소입니다.');
        }
        if ($this->users->findByEmail($ownerData['email']) !== null) {
            throw new RuntimeException('이미 등록된 관리자 이메일입니다.');
        }

        $this->pdo->beginTransaction();
        try {
            $churchId = $this->churches->create($churchData);
            $userId = $this->users->create($ownerData['name'], $ownerData['email'], password_hash($ownerData['password'], PASSWORD_DEFAULT));
            $this->memberships->add($churchId, $userId, 'owner');
            $subscriptionId = $this->subscriptions->createInvitationTrial($churchId);
            $this->audit->record($actorUserId, $churchId, 'church.created', 'church', $churchId, ['owner_user_id' => $userId, 'subscription_id' => $subscriptionId, 'product_family' => 'invitation']);
            $this->pdo->commit();
            return $churchId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function addAdministrator(int $churchId, array $adminData, string $role, int $actorUserId): int
    {
        if (!in_array($role, ['owner', 'admin', 'content_manager'], true)) {
            throw new RuntimeException('허용되지 않은 관리자 역할입니다.');
        }

        $church = $this->churches->findForPlatform($churchId);
        if ($church === null) {
            throw new RuntimeException('교회 또는 단체를 찾을 수 없습니다.');
        }

        $this->pdo->beginTransaction();
        try {
            $user = $this->users->findByEmail($adminData['email']);
            $userId = $user === null
                ? $this->users->create($adminData['name'], $adminData['email'], password_hash($adminData['password'], PASSWORD_DEFAULT))
                : (int) $user['id'];
            if ($this->memberships->exists($churchId, $userId)) {
                throw new RuntimeException('이미 이 교회에 등록된 관리자입니다.');
            }
            $this->memberships->add($churchId, $userId, $role);
            $this->audit->record($actorUserId, $churchId, 'church_admin.created', 'user', $userId, ['role' => $role]);
            $this->pdo->commit();
            return $userId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
