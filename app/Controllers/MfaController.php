<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use PDO;

final class MfaController
{
    private Auth $auth;

    public function __construct(PDO $pdo)
    {
        $this->auth = new Auth($pdo);
    }

    public function show(): void
    {
        if (!$this->auth->hasPendingMfa()) {
            Response::redirect('/login');
        }
        View::render('auth.mfa', ['title' => '2단계 인증', 'error' => Session::pullFlash('error')]);
    }

    public function verify(): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        if (!$this->auth->completePlatformMfa((string) ($_POST['code'] ?? ''))) {
            Session::flash('error', '인증번호가 올바르지 않거나 만료되었습니다.');
            Response::redirect('/mfa');
        }
        Response::redirect('/control');
    }
}
