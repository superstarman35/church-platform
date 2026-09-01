<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\Response;
use App\Core\View;
use App\Repositories\AuditLogRepository;
use App\Repositories\PublicContactRequestRepository;
use PDO;

final class PlatformPublicContactController
{
    public function __construct(private readonly PDO $pdo) {}

    public function index(): void
    {
        $filters = [
            'category' => (string)($_GET['category'] ?? ''),
            'status' => (string)($_GET['status'] ?? ''),
        ];

        View::render(
            'control.public-contacts.index',
            [
                'title' => '공개 문의 관리',
                'requests' => (new PublicContactRequestRepository($this->pdo))->listForPlatform($filters),
                'filters' => $filters,
                'success' => Session::pullFlash('success'),
                'error' => Session::pullFlash('error'),
            ]
        );
    }

    public function export(): void
    {
        $filters = [
            'category' => (string)($_GET['category'] ?? ''),
            'status' => (string)($_GET['status'] ?? ''),
        ];
        $requests = (new PublicContactRequestRepository($this->pdo))->listForPlatform($filters);

        $auth = Session::get('auth', []);
        $userId = (int)($auth['user_id'] ?? 0);
        (new AuditLogRepository($this->pdo))->record($userId, null, 'public_contact_requests.exported', 'public_contact_request', null, ['category' => $filters['category'], 'status' => $filters['status'], 'count' => count($requests)]);

        $filename = 'public-contact-requests-' . date('Ymd-Hi') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            Response::abort(500, 'CSV 파일을 생성할 수 없습니다.');
        }

        $columns = ['접수일', '문의유형', '상태', '이름', '이메일', '연락처', '단체명', '제목', '문의내용', '동의', '처리일', '담당자ID', '처리메모', 'IP', 'User-Agent'];
        fputcsv($out, $columns);

        $categoryLabel = static function (string $category): string {
            return match ($category) {
                'general' => '일반',
                'subscription' => '요금·구독',
                'technical' => '기능 오류/사용법',
                'policy' => '개인정보·보안',
                default => $category,
            };
        };

        foreach ($requests as $request) {
            fputcsv($out, [
                $this->cell((string)$request['created_at']),
                $this->cell($categoryLabel((string)$request['category'])),
                $this->cell((string)($request['status'] ?? 'open')),
                $this->cell((string)$request['name']),
                $this->cell((string)$request['email']),
                $this->cell((string)$request['phone']),
                $this->cell((string)($request['church_name'] ?? '')),
                $this->cell((string)$request['subject']),
                $this->cell((string)$request['message']),
                $this->cell((string)(($request['agreed_terms'] ?? 0) ? '동의' : '미동의')),
                $this->cell((string)($request['handled_at'] ?? '')),
                $this->cell((string)($request['handled_by_user_id'] ?? '')),
                $this->cell((string)($request['handled_note'] ?? '')),
                $this->cell((string)($request['ip_address'] ?? '')),
                $this->cell((string)($request['user_agent'] ?? '')),
            ]);
        }

        fclose($out);
        exit;
    }

    private function cell(string $value): string
    {
        return preg_match('/^[=+\\-@\\t]/', $value) === 1 ? "'".$value : $value;
    }

    public function show(int $id): void
    {
        $request = (new PublicContactRequestRepository($this->pdo))->find($id);
        if ($request === null) {
            Response::abort(404, '요청을 찾을 수 없습니다.');
        }

        View::render(
            'control.public-contacts.show',
            [
                'title' => '공개 문의 상세',
                'request' => $request,
                'success' => Session::pullFlash('success'),
                'error' => Session::pullFlash('error'),
            ]
        );
    }

    public function updateStatus(int $id): void
    {
        Csrf::verify($_POST['_token'] ?? null);

        $status = (string)($_POST['status'] ?? '');
        $note = trim((string)($_POST['handled_note'] ?? ''));

        if (mb_strlen($note) > 4000) {
            Response::abort(422, '처리 메모는 4,000자 이내로 입력해 주세요.');
        }

        if (!in_array($status, ['open', 'in_progress', 'answered', 'closed'], true)) {
            Response::abort(422, '처리 상태가 유효하지 않습니다.');
        }

        $auth = Session::get('auth', []);
        $userId = (int)($auth['user_id'] ?? 0);

        $repo = new PublicContactRequestRepository($this->pdo);
        $previous = $repo->updateStatus($id, $status, $note, $userId);
        if ($previous === null) {
            Session::flash('error', '요청을 찾을 수 없거나 처리할 수 없습니다.');
            Response::redirect('/control/public-contacts');
        }

        (new AuditLogRepository($this->pdo))->record(
            $userId,
            null,
            'public_contact_request.status_updated',
            'public_contact_request',
            $id,
            [
                'previous_status' => $previous['status'],
                'status' => $status,
                'previous_note' => (string)($previous['note'] ?? ''),
                'note' => $note,
            ]
        );

        Session::flash('success', '요청 상태를 저장했습니다.');
        Response::redirect('/control/public-contacts/' . $id);
    }
}
