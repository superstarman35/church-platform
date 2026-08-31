<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use PDO;

final class PlatformDashboardController
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function index(): void
    {
        $churchCounts = $this->pdo->query("SELECT COUNT(*) total, SUM(status = 'trial') trials, SUM(status = 'active') active FROM churches")->fetch();
        $userCount = (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        View::render('control.dashboard', ['title' => '플랫폼 대시보드', 'churchCounts' => $churchCounts ?: [], 'userCount' => $userCount]);
    }
}
