<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\ChurchRepository;
use App\Services\ChurchProvisioningService;
use PDO;
use RuntimeException;

final class ChurchController
{
    private ChurchRepository $churches;
    private ChurchProvisioningService $provisioning;

    public function __construct(private readonly PDO $pdo)
    {
        $this->churches = new ChurchRepository($pdo);
        $this->provisioning = new ChurchProvisioningService($pdo);
    }

    public function index(): void
    {
        View::render('control.churches.index', ['title' => '교회·단체 관리', 'churches' => $this->churches->allForPlatform(), 'success' => Session::pullFlash('success')]);
    }

    public function create(): void
    {
        View::render('control.churches.create', ['title' => '교회·단체 생성', 'error' => Session::pullFlash('error'), 'old' => Session::pullFlash('old', [])]);
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'slug' => mb_strtolower(trim((string) ($_POST['slug'] ?? ''))),
            'organization_type' => (string) ($_POST['organization_type'] ?? 'church'),
            'contact_name' => trim((string) ($_POST['contact_name'] ?? '')),
            'contact_email' => mb_strtolower(trim((string) ($_POST['contact_email'] ?? ''))),
            'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
        ];
        $owner = [
            'name' => trim((string) ($_POST['owner_name'] ?? '')),
            'email' => mb_strtolower(trim((string) ($_POST['owner_email'] ?? ''))),
            'password' => (string) ($_POST['owner_password'] ?? ''),
        ];

        if (!Validator::text($data['name'], 2, 150) || !Validator::slug($data['slug']) || !in_array($data['organization_type'], ['church', 'organization'], true) || !Validator::email($data['contact_email']) || !Validator::text($owner['name'], 2, 100) || !Validator::email($owner['email']) || !Validator::password($owner['password'])) {
            Session::flash('old', array_merge($data, ['owner_name' => $owner['name'], 'owner_email' => $owner['email']]));
            Session::flash('error', '입력값을 확인해 주세요. 비밀번호는 영문과 숫자를 포함한 10자 이상이어야 합니다.');
            Response::redirect('/control/churches/create');
        }

        try {
            $auth = Session::get('auth', []);
            $churchId = $this->provisioning->createChurchWithOwner($data, $owner, (int) ($auth['user_id'] ?? 0));
            Session::flash('success', "교회·단체 #{$churchId}와 30일 초대장 체험 계정을 생성했습니다.");
            Response::redirect('/control/churches');
        } catch (RuntimeException $exception) {
            Session::flash('old', array_merge($data, ['owner_name' => $owner['name'], 'owner_email' => $owner['email']]));
            Session::flash('error', $exception->getMessage());
            Response::redirect('/control/churches/create');
        }
    }

    public function createAdmin(int $churchId): void
    {
        $church = $this->churches->findForPlatform($churchId);
        if ($church === null) {
            Response::abort(404, '교회 또는 단체를 찾을 수 없습니다.');
        }
        View::render('control.churches.admin_create', ['title' => '교회 관리자 추가', 'church' => $church, 'error' => Session::pullFlash('error')]);
    }

    public function storeAdmin(int $churchId): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
            'password' => (string) ($_POST['password'] ?? ''),
        ];
        $role = (string) ($_POST['role'] ?? 'admin');
        if (!Validator::text($data['name'], 2, 100) || !Validator::email($data['email']) || !Validator::password($data['password']) || !in_array($role, ['owner', 'admin', 'content_manager'], true)) {
            Session::flash('error', '관리자 정보를 확인해 주세요.');
            Response::redirect("/control/churches/{$churchId}/admins/create");
        }

        try {
            $auth = Session::get('auth', []);
            $this->provisioning->addAdministrator($churchId, $data, $role, (int) ($auth['user_id'] ?? 0));
            Session::flash('success', '교회 관리자를 추가했습니다.');
            Response::redirect('/control/churches');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            Response::redirect("/control/churches/{$churchId}/admins/create");
        }
    }
}
