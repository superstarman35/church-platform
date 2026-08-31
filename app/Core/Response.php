<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        View::render('errors.http', ['status' => $status, 'message' => $message]);
        exit;
    }
}
