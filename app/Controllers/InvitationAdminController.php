<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Config;
use App\Core\Response;
use App\Core\Session;
use App\Core\TenantContext;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\AuditLogRepository;
use App\Repositories\InvitationApplicationRepository;
use App\Repositories\InvitationMediaRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\InvitationStatsRepository;
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
            if($data['visibility']==='password'&&$data['access_password_hash']===null)throw new RuntimeException('비밀번호 공개에는 8자 이상 비밀번호가 필요합니다.');
            $snapshot = $this->entitlements->snapshot($tenant->churchId());
            $this->entitlements->assertUsable($snapshot);
            if ($this->hasImageUpload()) { $this->assertTrafficAllowsUploads($tenant->churchId(), $snapshot); }
            $this->entitlements->assertBelow($snapshot, 'invitation.monthly_create_count', $this->invitations->countCreatedThisMonth($tenant->churchId()), '이번 달 초대장 생성 한도를 모두 사용했습니다.');
            if ($this->invitations->slugExists($tenant->churchId(), $data['slug'])) {
                throw new RuntimeException('이미 사용 중인 초대장 주소입니다.');
            }
            $id = $this->invitations->create($tenant, $data, $this->userId());
            $created = $this->invitations->findForTenant($tenant, $id);
            (new InvitationImageService(new InvitationMediaRepository($this->pdo), $this->entitlements))->uploadHero($tenant, $created, $_FILES['hero_image'] ?? []);
            (new InvitationImageService(new InvitationMediaRepository($this->pdo), $this->entitlements))->uploadGallery($tenant, $created, $_FILES['gallery_images'] ?? []);
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
            'gallery' => (new InvitationMediaRepository($this->pdo))->galleryForTenant($tenant, $id),
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
        $existing=$this->invitations->findForTenant($tenant, $id);if ($existing === null) {
            Response::abort(404, '초대장을 찾을 수 없습니다.');
        }
        try {
            if($data['visibility']==='password'&&$data['access_password_hash']===null&&empty($existing['access_password_hash']))throw new RuntimeException('비밀번호 공개로 변경하려면 8자 이상 비밀번호를 입력해 주세요.');
            if((int)$data['share_media_id']>0&&!(new InvitationMediaRepository($this->pdo))->activeBelongsToInvitation($tenant,$id,(int)$data['share_media_id']))throw new RuntimeException('공유 이미지는 이 초대장에 등록된 활성 이미지만 선택할 수 있습니다.');
            $snapshot = $this->entitlements->snapshot($tenant->churchId());
            $this->entitlements->assertUsable($snapshot);
            if ($this->hasImageUpload()) { $this->assertTrafficAllowsUploads($tenant->churchId(), $snapshot); }
            if ($this->invitations->slugExists($tenant->churchId(), $data['slug'], $id)) {
                throw new RuntimeException('이미 사용 중인 초대장 주소입니다.');
            }
            $this->invitations->update($tenant, $id, $data, $this->userId());
            $updated = $this->invitations->findForTenant($tenant, $id);
            (new InvitationImageService(new InvitationMediaRepository($this->pdo), $this->entitlements))->uploadHero($tenant, $updated, $_FILES['hero_image'] ?? []);
            (new InvitationImageService(new InvitationMediaRepository($this->pdo), $this->entitlements))->uploadGallery($tenant, $updated, $_FILES['gallery_images'] ?? []);
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

    public function trash(int $id): void { $this->changeDeleted($id, true); }
    public function restore(int $id): void { $this->changeDeleted($id, false); }
    public function deleteGallery(int $invitationId, int $mediaId): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $tenant = TenantContext::fromSession();
        if ($this->invitations->findForTenant($tenant, $invitationId) === null) Response::abort(404, '초대장을 찾을 수 없습니다.');
        $service = new InvitationImageService(new InvitationMediaRepository($this->pdo), $this->entitlements);
        if (!$service->deleteGallery($tenant, $invitationId, $mediaId)) Response::abort(404, '사진을 찾을 수 없습니다.');
        $this->audit->record($this->userId(), $tenant->churchId(), 'invitation.gallery_deleted', 'invitation_media', $mediaId, ['invitation_id'=>$invitationId]);
        Session::flash('success', '갤러리 사진을 삭제했습니다.');
        Response::redirect('/admin/invitations/' . $invitationId . '/edit');
    }
    public function applications(int $id): void
    {
        $tenant = TenantContext::fromSession();
        $this->authorizeApplicantData($tenant);
        $item = $this->invitations->findForTenant($tenant, $id);
        if ($item === null) Response::abort(404, '초대장을 찾을 수 없습니다.');
        $filters = $this->applicationFilters();
        View::render('admin.invitations.applications', [
            'title' => '신청자 관리', 'item' => $item,
            'applications' => (new InvitationApplicationRepository($this->pdo))->listForTenant($tenant, $id, $filters),
            'filters' => $filters, 'success' => Session::pullFlash('success'), 'error' => Session::pullFlash('error'),
        ]);
    }

    public function shareTools(int $id): void
    {
        $tenant = TenantContext::fromSession();
        $item = $this->invitations->findForTenant($tenant, $id);
        if ($item === null) Response::abort(404, '초대장을 찾을 수 없습니다.');
        $publicUrl = rtrim((string) Config::get('app.url', ''), '/') . '/i/'
            . rawurlencode((string) $item['church_slug']) . '/' . rawurlencode((string) $item['slug']);
        View::render('admin.invitations.share', [
            'title' => '초대장 QR·공유', 'item' => $item, 'publicUrl' => $publicUrl,
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&format=png&data=' . rawurlencode($publicUrl),
        ]);
    }

    public function stats(int $id): void
    {
        $tenant = TenantContext::fromSession();
        $item = $this->invitations->findForTenant($tenant, $id);
        if ($item === null) Response::abort(404, '초대장을 찾을 수 없습니다.');

        $snapshot = $this->entitlements->snapshot($tenant->churchId());
        $retentionDays = $this->entitlements->limit($snapshot, 'analytics.retention_days');
        [$from, $to] = $this->statisticsPeriod($retentionDays);
        $repository = new InvitationStatsRepository($this->pdo);
        $daily = $this->completeDailyStatistics($repository->dailyForTenant($tenant, $id, $from, $to), $from, $to);
        View::render('admin.invitations.stats', [
            'title'=>'초대장 통계', 'item'=>$item, 'from'=>$from, 'to'=>$to,
            'retentionDays'=>$retentionDays,
            'summary'=>$repository->summaryForTenant($tenant, $id, $from, $to), 'daily'=>$daily,
        ]);
    }
    public function updateApplicationStatus(int $invitationId, int $applicationId): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $tenant = TenantContext::fromSession();
        $this->authorizeApplicantData($tenant);
        if ($this->invitations->findForTenant($tenant, $invitationId) === null) Response::abort(404, '초대장을 찾을 수 없습니다.');
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['new', 'confirmed', 'cancelled'], true)) Response::abort(422, '올바르지 않은 신청 상태입니다.');
        if (!(new InvitationApplicationRepository($this->pdo))->updateStatusForTenant($tenant, $invitationId, $applicationId, $status)) {
            Response::abort(404, '신청 정보를 찾을 수 없거나 상태가 이미 같습니다.');
        }
        $this->audit->record($this->userId(), $tenant->churchId(), 'invitation.application_status_updated', 'invitation_application', $applicationId, ['invitation_id' => $invitationId, 'status' => $status]);
        Session::flash('success', '신청 상태를 변경했습니다.');
        Response::redirect('/admin/invitations/' . $invitationId . '/applications');
    }

    public function exportApplications(int $id): never
    {
        $tenant = TenantContext::fromSession();
        $this->authorizeApplicantData($tenant);
        $item = $this->invitations->findForTenant($tenant, $id);
        if ($item === null) Response::abort(404, '초대장을 찾을 수 없습니다.');
        $filters = $this->applicationFilters();
        $applications = (new InvitationApplicationRepository($this->pdo))->listForTenant($tenant, $id, $filters);
        $this->audit->record($this->userId(), $tenant->churchId(), 'invitation.applications_exported', 'invitation', $id, ['row_count' => count($applications), 'filters' => $filters]);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="invitation-applications-' . $id . '-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $output = fopen('php://output', 'wb');
        if ($output === false) Response::abort(500, 'CSV 파일을 만들 수 없습니다.');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['접수일', '이름', '연락처', '이메일', '인원', '메시지', '상태']);
        foreach ($applications as $application) {
            fputcsv($output, [
                $this->csvCell((string) $application['created_at']), $this->csvCell((string) $application['applicant_name']),
                $this->csvCell((string) ($application['phone'] ?? '')), $this->csvCell((string) ($application['email'] ?? '')),
                (int) $application['attendee_count'], $this->csvCell((string) ($application['message'] ?? '')),
                $this->csvCell((string) $application['status']),
            ]);
        }
        fclose($output);
        exit;
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

    private function changeDeleted(int $id, bool $deleted): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $tenant = TenantContext::fromSession();
        if (!$this->invitations->setDeleted($tenant, $id, $deleted, $this->userId())) Response::abort(404, '초대장을 찾을 수 없거나 이미 처리되었습니다.');
        $action = $deleted ? 'trashed' : 'restored';
        $this->audit->record($this->userId(), $tenant->churchId(), 'invitation.' . $action, 'invitation', $id);
        Session::flash('success', $deleted ? '초대장을 휴지통으로 이동했습니다.' : '초대장을 임시저장 상태로 복원했습니다.');
        Response::redirect('/admin/invitations');
    }
    private function hasImageUpload(): bool
    {
        if ((int) ($_FILES['hero_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) return true;
        foreach ((array) ($_FILES['gallery_images']['error'] ?? []) as $error) {
            if ((int) $error !== UPLOAD_ERR_NO_FILE) return true;
        }
        return false;
    }

    private function statisticsPeriod(?int $retentionDays): array
    {
        $today = new \DateTimeImmutable('today');
        $defaultFrom = $today->modify('-29 days');
        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', trim((string)($_GET['from'] ?? ''))) ?: $defaultFrom;
        $to = \DateTimeImmutable::createFromFormat('!Y-m-d', trim((string)($_GET['to'] ?? ''))) ?: $today;
        if ($to > $today) $to = $today;
        $retentionStart = $retentionDays !== null && $retentionDays > 0 ? $today->modify('-' . ($retentionDays - 1) . ' days') : null;
        if ($retentionStart !== null && $from < $retentionStart) $from = $retentionStart;
        if ($from > $to) $from = $to;
        return [$from->format('Y-m-d'), $to->format('Y-m-d')];
    }

    private function completeDailyStatistics(array $rows, string $from, string $to): array
    {
        $indexed=[];
        foreach($rows as $row) $indexed[(string)$row['stat_date']]=$row;
        $result=[]; $day=new \DateTimeImmutable($from); $end=new \DateTimeImmutable($to);
        while($day <= $end){
            $date=$day->format('Y-m-d');
            $result[]=$indexed[$date] ?? ['stat_date'=>$date,'views'=>0,'shares'=>0,'applications'=>0,'traffic_bytes'=>0];
            $day=$day->modify('+1 day');
        }
        return $result;
    }
    private function assertTrafficAllowsUploads(int $churchId, array $snapshot): void
    {
        $limit = $this->entitlements->limit($snapshot, 'traffic.monthly_bytes');
        if ($limit === null || $limit <= 0) return;
        $usage = (new InvitationStatsRepository($this->pdo))->usage($churchId);
        if ((int) $usage['traffic_bytes'] >= $limit) {
            throw new RuntimeException('이번 달 트래픽 한도를 모두 사용해 신규 이미지 업로드가 제한됩니다. 다음 월 초기화 후 다시 시도하거나 증액을 요청해 주세요.');
        }
    }
    private function input(): array
    {
        $fields = ['slug','title','event_type','template_code','visibility','share_media_id','summary','body','event_at','registration_starts_at','registration_ends_at','capacity','venue_name','venue_address','map_url','youtube_url','contact_name','contact_phone','publish_at','expires_at'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $data['slug'] = mb_strtolower($data['slug']);
        $data['event_at'] = $data['event_at'] === '' ? '' : str_replace('T', ' ', $data['event_at']) . (strlen($data['event_at']) === 16 ? ':00' : '');
        foreach (['registration_starts_at','registration_ends_at','publish_at','expires_at'] as $field) {
            $data[$field] = $data[$field] === '' ? '' : str_replace('T', ' ', $data[$field]) . (strlen($data[$field]) === 16 ? ':00' : '');
        }
        $data['waitlist_enabled']=($_POST['waitlist_enabled']??'')==='1';
        $password=(string)($_POST['access_password']??'');$data['access_password_valid']=$password===''||strlen($password)>=8;$data['access_password_hash']=$password===''?null:password_hash($password,PASSWORD_DEFAULT);
        return $data;
    }

    private function valid(array $d): bool
    {
        $types = ['worship','evangelism','conference','education','volunteer','community','other'];
        $templates = ['portrait','square','landscape'];
        return Validator::slug($d['slug']) && Validator::text($d['title'], 2, 150)
            && in_array($d['event_type'], $types, true) && in_array($d['template_code'], $templates, true)&&in_array($d['visibility'],['public','password','private'],true)&&$d['access_password_valid']
            && mb_strlen($d['summary']) <= 255 && mb_strlen($d['body']) <= 10000
            && $this->urlAllowed($d['map_url']) && $this->youtubeAllowed($d['youtube_url'])
            && ((int)$d['capacity']>=0) && ($d['registration_starts_at']===''||$d['registration_ends_at']===''||strtotime($d['registration_ends_at'])>strtotime($d['registration_starts_at'])) && ($d['publish_at'] === '' || $d['expires_at'] === '' || strtotime($d['expires_at']) > strtotime($d['publish_at']));
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

    private function applicationFilters(): array
    {
        $status = (string) ($_GET['status'] ?? '');
        return [
            'q' => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100),
            'status' => in_array($status, ['new', 'confirmed', 'cancelled'], true) ? $status : '',
            'date_from' => mb_substr(trim((string) ($_GET['date_from'] ?? '')), 0, 10),
            'date_to' => mb_substr(trim((string) ($_GET['date_to'] ?? '')), 0, 10),
        ];
    }

    private function csvCell(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        return preg_match('/^[=+\-@\t]/u', $value) === 1 ? "'" . $value : $value;
    }

    private function authorizeApplicantData(TenantContext $tenant): void
    {
        if (!in_array($tenant->role(), ['owner', 'admin'], true)) Response::abort(403, '신청자 개인정보 열람 권한이 없습니다.');
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
