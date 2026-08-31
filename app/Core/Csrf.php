<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf_token', $token);
        }
        return $token;
    }

    public static function verify(?string $token): void
    {
        $stored = Session::get('_csrf_token');
        if (!is_string($stored) || !is_string($token) || !hash_equals($stored, $token)) {
            Response::abort(419, '보안 토큰이 만료되었습니다. 페이지를 새로고침해 주세요.');
        }
    }
}
