<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\InvitationApplicationRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\InvitationStatsRepository;
use App\Services\SubscriptionEntitlementService;
use PDO;
use RuntimeException;

final class PublicInvitationController
{
    private InvitationRepository $invitations;
    private InvitationStatsRepository $stats;

    public function __construct(private readonly PDO $pdo)
    {
        $this->invitations = new InvitationRepository($pdo);
        $this->stats = new InvitationStatsRepository($pdo);
    }

    public function show(string $churchSlug, string $slug): void
    {
        $item = $this->published($churchSlug, $slug);
        $this->stats->increment((int) $item['church_id'], (int) $item['id'], 'views', 24576);
        View::render('public.invitation', [
            'title' => $item['title'], 'item' => $item,
            'success' => Session::pullFlash('public_success'), 'error' => Session::pullFlash('public_error'),
        ], 'public.layout');
    }

    public function apply(string $churchSlug, string $slug): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $item = $this->published($churchSlug, $slug);
        $data = [
            'applicant_name' => trim((string) ($_POST['applicant_name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
            'attendee_count' => max(1, min(100, (int) ($_POST['attendee_count'] ?? 1))),
            'message' => trim((string) ($_POST['message'] ?? '')),
        ];
        $consent = ($_POST['privacy_consent'] ?? '') === '1';
        if (!Validator::text($data['applicant_name'], 2, 100) || ($data['email'] !== '' && !Validator::email($data['email'])) || mb_strlen($data['message']) > 1000 || !$consent) {
            Session::flash('public_error', '신청자 이름과 개인정보 수집 동의를 확인해 주세요.');
            Response::redirect("/i/{$churchSlug}/{$slug}#apply");
        }
        try {
            $entitlements = new SubscriptionEntitlementService($this->pdo);
            $snapshot = $entitlements->snapshot((int) $item['church_id']);
            $entitlements->assertUsable($snapshot);
            $apps = new InvitationApplicationRepository($this->pdo);
            $current = $apps->countForPeriod((int) $item['church_id'], (string) $snapshot['period_starts_at'], (string) $snapshot['period_ends_at']);
            $entitlements->assertBelow($snapshot, 'application.max_count', $current, '신청 접수 한도가 모두 사용되었습니다.');
            $apps->create($item, $data);
            $this->stats->increment((int) $item['church_id'], (int) $item['id'], 'applications', 2048);
            Session::flash('public_success', '신청이 접수되었습니다. 감사합니다.');
        } catch (RuntimeException $e) {
            Session::flash('public_error', $e->getMessage());
        }
        Response::redirect("/i/{$churchSlug}/{$slug}#apply");
    }

    public function share(string $churchSlug, string $slug): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $item = $this->published($churchSlug, $slug);
        $this->stats->increment((int) $item['church_id'], (int) $item['id'], 'shares', 512);
        http_response_code(204);
    }

    private function published(string $churchSlug, string $slug): array
    {
        $item = $this->invitations->findPublished($churchSlug, $slug);
        if ($item === null) {
            Response::abort(404, '게시 중인 초대장을 찾을 수 없습니다.');
        }
        return $item;
    }
}

