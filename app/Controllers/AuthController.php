<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use PDO;

final class AuthController
{
    private Auth $auth;

    public function __construct(PDO $pdo)
    {
        $this->auth = new Auth($pdo);
    }

    public function showLogin(): void
    {
        $this->auth->requireGuest();
        View::render('auth.login', ['title' => '관리자 로그인', 'error' => Session::pullFlash('error')]);
    }

    public function login(): void
    {
        $this->auth->requireGuest();
        Csrf::verify($_POST['_token'] ?? null);
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $result = Validator::email($email) && $password !== '' ? $this->auth->attempt($email, $password) : Auth::FAILED;
        if ($result === Auth::FAILED) {
            Session::flash('error', '이메일 또는 비밀번호를 확인해 주세요. 반복 실패 시 계정이 잠길 수 있습니다.');
            Response::redirect('/login');
        }
        Response::redirect($result === Auth::MFA_REQUIRED ? '/mfa' : '/admin');
    }

    public function logout(): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $this->auth->logout();
        Response::redirect('/login');
    }
}
