<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\PublicContactRequestRepository;
use PDO;

final class PublicContactController
{
    public function __construct(private readonly PDO $pdo) {}

    public function index(array $uiContext = []): void
    {
        $dashboardUrl = isset($uiContext['dashboardUrl']) && is_string($uiContext['dashboardUrl']) ? $uiContext['dashboardUrl'] : null;

        View::render('home.contact', [
            'title' => '문의하기',
            'dashboardUrl' => $dashboardUrl,
            'pageCss' => '/assets/info.css?v=20260902-1',
            'success' => Session::pullFlash('success'),
            'error' => Session::pullFlash('error'),
            'old' => Session::pullFlash('old') ?? [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? null);

        $data = [
            'category' => (string)($_POST['category'] ?? ''),
            'name' => trim((string)($_POST['name'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'church_name' => trim((string)($_POST['church_name'] ?? '')),
            'subject' => trim((string)($_POST['subject'] ?? '')),
            'message' => trim((string)($_POST['message'] ?? '')),
            'agreed_terms' => ($_POST['agree_terms'] ?? '') === '1',
        ];

        $valid = in_array($data['category'], ['general', 'subscription', 'technical', 'policy'], true)
            && Validator::text($data['name'], 2, 80)
            && Validator::email($data['email'])
            && ($data['church_name'] === '' || Validator::text($data['church_name'], 1, 120))
            && ($data['phone'] === '' || Validator::text($data['phone'], 5, 30))
            && Validator::text($data['subject'], 2, 120)
            && Validator::text($data['message'], 10, 3000)
            && $data['agreed_terms'];

        if (!$valid) {
            Session::flash('error', '문의 유형, 이름, 이메일, 제목, 내용, 개인정보 동의를 정확히 입력해 주세요.');
            Session::flash('old', $data);
            Response::redirect('/contact');
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : null;

        $payload = $data + [
            'ip_address' => $ip !== null && $ip !== '' ? (string) @inet_pton($ip) : null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];

        (new PublicContactRequestRepository($this->pdo))->create($payload);

        Session::flash('success', '문의가 접수되었습니다. 빠른 시일 내로 안내드리겠습니다.');
        Response::redirect('/contact');
    }
}
