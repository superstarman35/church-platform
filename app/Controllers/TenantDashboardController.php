<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\TenantContext;
use App\Core\View;
use App\Repositories\ChurchRepository;
use App\Repositories\ChurchUserRepository;
use PDO;

final class TenantDashboardController
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function index(): void
    {
        $tenant = TenantContext::fromSession();
        $church = (new ChurchRepository($this->pdo))->findForTenant($tenant->churchId(), $tenant->churchId());
        if ($church === null) {
            Response::abort(403, '현재 교회 정보를 조회할 수 없습니다.');
        }
        $admins = (new ChurchUserRepository($this->pdo))->listForTenant($tenant);
        View::render('admin.dashboard', ['title' => '초대장 관리자', 'church' => $church, 'admins' => $admins, 'tenant' => $tenant]);
    }
}
