<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layout'): void
    {
        $base = dirname(__DIR__, 2) . '/resources/views/';
        $viewPath = $base . str_replace('.', '/', $view) . '.php';
        $layoutPath = $base . str_replace('.', '/', $layout) . '.php';
        if (!is_file($viewPath) || !is_file($layoutPath)) {
            throw new RuntimeException('View not found.');
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = (string) ob_get_clean();
        require $layoutPath;
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
