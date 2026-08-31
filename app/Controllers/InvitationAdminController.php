<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\TenantContext;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\AuditLogRepository;
use App\Repositories\InvitationApplicationRepository;
use App\Repositories\InvitationMediaRepository;
use App\Repositories\InvitationRepository;
use App\Services\InvitationImageService;
use App\Services\SubscriptionEntitlementService;
use PDO;
use RuntimeException;

final class InvitationAdminController
{
    private InvitationRepository $invitations;
    private SubscriptionEntitlementService $entitlements;
    private AuditLogRepository $audit;

    public function __construct(private readonly PDO $pdo)
    {
        $this->invitations = new InvitationRepository($pdo);
        $this->entitlements = new SubscriptionEntitlementService($pdo);
        $this->audit = new AuditLogRepository($pdo);
    }

    public function index(): void
    {
        $tenant = TenantContext::fromSession();
        View::render('admin.invitations.index', [
            'title' => '초대장 관리', 'items' => $this->invitations->listForTenant($tenant),
            'success' => Session::pullFlash('success'), 'error' => Session::pullFlash('error'),
        ]);
    }

    public function create(): void
    {
        View::render('admin.invitations.form', [
            'title' => '초대장 만들기', 'item' => Session::pullFlash('old', []),
            'error' => Session::pullFlash('error'), 'action' => '/admin/invitations', 'editing' => false,
        ]);
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $tenant = TenantContext::fromSession();
        $data = $this->input();
        if (!$this->valid($data)) {
            $this->fail('/admin/invitations/create', $data, '필수 항목과 URL 형식을 확인해 주세요.');
        }
        try {
            $snapshot = $this->entitlements->snapshot($tenant->churchId());
            $this->entitlements->assertUsable($snapshot);
            $this->entitlements->assertBelow($snapshot, 'invitation.monthly_create_count', $this->invitations->countCreatedThisMonth($tenant->churchId()), '이번 달 초대장 생성 한도를 모두 사용했습니다.');
            if ($this->invitations->slugExists($tenant->churchId(), $data['slug'])) {
                throw new RuntimeException('이미 사용 중인 초대장 주소입니다.');
            }
            $id = $this->invitations->create($tenant, $data, $this->userId());
            $created = $this->invitations->findForTenant($tenant, $id);
            (new InvitationImageService(new InvitationMediaRepository($this->pdo), $this->entitlements))->uploadHero($tenant, $created, $_FILES['hero_image'] ?? []);
            $this->audit->record($this->userId(), $tenant->churchId(), 'invitation.created', 'invitation', $id);
            Session::flash('success', '초대장을 임시저장했습니다.');
            Response::redirect('/admin/invitations/' . $id . '/edit');
        } catch (RuntimeException $e) {
            $this->fail('/admin/invitations/create', $data, $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $tenant = TenantContext::fromSession();
        $item = $this->invitations->findForTenant($tenant, $id);
        if ($item === null) {
            Response::abort(404, '초대장을 찾을 수 없습니다.');
        }
        View::render('admin.invitations.form', [
            'title' => '초대장 수정', 'item' => $item, 'error' => Session::pullFlash('error'),
            'success' => Session::pullFlash('success'), 'action' => "/admin/invitations/{$id}", 'editing' => true,
        ]);
    }

    public function update(int $id): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $tenant = TenantContext::fromSession();
        $data = $this->input();
        if (!$this->valid($data)) {
            $this->fail("/admin/invitations/{$id}/edit", $data, '필수 항목과 URL 형식을 확인해 주세요.');
        }
        if ($this->invitations->findForTenant($tenant, $id) === null) {
            Response::abort(404, '초대장을 찾을 수 없습니다.');
        }
        try {
            $this->entitlements->assertUsable($this->entitlements->snapshot($tenant->churchId()));
            if ($this->invitations->slugExists($tenant->churchId(), $data['slug'], $id)) {
                throw new RuntimeException('이미 사용 중인 초대장 주소입니다.');
            }
            $this->invitations->update($tenant, $id, $data, $this->userId());
            $updated = $this->invitations->findForTenant($tenant, $id);
            (new InvitationImageService(new InvitationMediaRepository($this->pdo), $this->entitlements))->uploadHero($tenant, $updated, $_FILES['hero_image'] ?? []);
            $this->audit->record($this->userId(), $tenant->churchId(), 'invitation.updated', 'invitation', $id);
            Session::flash('success', '초대장을 저장했습니다.');
            Response::redirect("/admin/invitations/{$id}/edit");
        } catch (RuntimeException $e) {
            $this->fail("/admin/invitations/{$id}/edit", $data, $e->getMessage());
        }
    }

    public function clone(int $id): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $tenant = TenantContext::fromSession();
        try {
            $snapshot = $this->entitlements->snapshot($tenant->churchId());
            $this->entitlements->assertUsable($snapshot);
            $this->entitlements->assertBelow($snapshot, 'invitation.monthly_create_count', $this->invitations->countCreatedThisMonth($tenant->churchId()), '이번 달 초대장 생성 한도를 모두 사용했습니다.');
            $slug = 'copy-' . date('ymd-His');
            $newId = $this->invitations->cloneForTenant($tenant, $id, $slug, $this->userId());
            if ($newId === null) {
                Response::abort(404, '초대장을 찾을 수 없습니다.');
            }
            $this->audit->record($this->userId(), $tenant->churchId(), 'invitation.cloned', 'invitation', $newId, ['source_id' => $id]);
            Session::flash('success', '초대장을 복제했습니다. 주소를 확인해 주세요.');
            Response::redirect("/admin/invitations/{$newId}/edit");
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/admin/invitations');
        }
    }

    public function publish(int $id): void
    {
        $this->changeStatus($id, 'published');
    }

    public function end(int $id): void
    {
        $this->changeStatus($id, 'ended');
    }

    public function applications(int $id): void
    {
        $tenant = TenantContext::fromSession();
        $item = $this->invitations->findForTenant($tenant, $id);
        if ($item === null) {
            Response::abort(404, '초대장을 찾을 수 없습니다.');
        }
        View::render('admin.invitations.applications', [
            'title' => '신청자 관리', 'item' => $item,
            'applications' => (new InvitationApplicationRepository($this->pdo))->listForTenant($tenant, $id),
        ]);
    }

    private function changeStatus(int $id, string $status): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $tenant = TenantContext::fromSession();
        try {
            $snapshot = $this->entitlements->snapshot($tenant->churchId());
            $this->entitlements->assertUsable($snapshot);
            $item = $this->invitations->findForTenant($tenant, $id);
            if ($item === null) {
                Response::abort(404, '초대장을 찾을 수 없습니다.');
            }
            if ($status === 'published' && $item['status'] !== 'published') {
                $this->entitlements->assertBelow($snapshot, 'invitation.active_count', $this->invitations->countPublished($tenant->churchId()), '동시 게시 가능한 초대장 수를 초과했습니다.');
            }
            $this->invitations->setStatus($tenant, $id, $status, $this->userId());
            $this->audit->record($this->userId(), $tenant->churchId(), 'invitation.' . $status, 'invitation', $id);
            Session::flash('success', $status === 'published' ? '초대장을 게시했습니다.' : '초대장을 종료했습니다.');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/admin/invitations');
    }

    private function input(): array
    {
        $fields = ['slug','title','event_type','template_code','summary','body','event_at','venue_name','venue_address','map_url','youtube_url','contact_name','contact_phone'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $data['slug'] = mb_strtolower($data['slug']);
        $data['event_at'] = $data['event_at'] === '' ? '' : str_replace('T', ' ', $data['event_at']) . (strlen($data['event_at']) === 16 ? ':00' : '');
        return $data;
    }

    private function valid(array $d): bool
    {
        $types = ['worship','evangelism','conference','education','volunteer','community','other'];
        $templates = ['portrait','square','landscape'];
        return Validator::slug($d['slug']) && Validator::text($d['title'], 2, 150)
            && in_array($d['event_type'], $types, true) && in_array($d['template_code'], $templates, true)
            && mb_strlen($d['summary']) <= 255 && mb_strlen($d['body']) <= 10000
            && $this->urlAllowed($d['map_url']) && $this->youtubeAllowed($d['youtube_url']);
    }

    private function urlAllowed(string $url): bool
    {
        return $url === '' || (filter_var($url, FILTER_VALIDATE_URL) !== false && str_starts_with($url, 'https://'));
    }

    private function youtubeAllowed(string $url): bool
    {
        if ($url === '') return true;
        if (!$this->urlAllowed($url)) return false;
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
        return in_array($host, ['youtube.com','www.youtube.com','youtu.be','m.youtube.com'], true);
    }

    private function userId(): int
    {
        $auth = Session::get('auth', []);
        return (int) ($auth['user_id'] ?? 0);
    }

    private function fail(string $path, array $old, string $message): never
    {
        Session::flash('old', $old);
        Session::flash('error', $message);
        Response::redirect($path);
    }
}
