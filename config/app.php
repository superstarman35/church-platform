<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'Church Invitation Platform'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) Env::get('APP_URL', 'http://127.0.0.1:8080'), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'Asia/Seoul'),
    'key' => Env::get('APP_KEY'),
    'session' => [
        'name' => Env::get('SESSION_NAME', 'church_platform_session'),
        'secure' => filter_var(Env::get('SESSION_SECURE', 'true'), FILTER_VALIDATE_BOOL),
        'same_site' => Env::get('SESSION_SAMESITE', 'Lax'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', '120'),
    ],
    'login' => [
        'max_attempts' => (int) Env::get('LOGIN_MAX_ATTEMPTS', '5'),
        'lock_minutes' => (int) Env::get('LOGIN_LOCK_MINUTES', '15'),
    ],
];
