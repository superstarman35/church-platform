<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;
use App\Core\Session;

require_once dirname(__DIR__) . '/app/Core/Env.php';
Env::load(dirname(__DIR__) . '/.env');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $path = dirname(__DIR__) . '/app/' . $relative . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

Config::load([
    'app' => require dirname(__DIR__) . '/config/app.php',
    'database' => require dirname(__DIR__) . '/config/database.php',
]);

date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Seoul'));

Session::start((array) Config::get('app.session', []));

set_exception_handler(static function (Throwable $exception): void {
    http_response_code(500);
    $debug = (bool) Config::get('app.debug', false);
    error_log(sprintf('[%s] %s in %s:%d', date('c'), $exception->getMessage(), $exception->getFile(), $exception->getLine()));
    if ($debug) {
        echo '<h1>Application error</h1><pre>' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
        return;
    }
    echo '<h1>서비스 오류</h1><p>잠시 후 다시 시도해 주세요.</p>';
});

return Database::connection();
