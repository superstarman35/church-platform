<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable $handler, array $middleware): void
    {
        $names = [];
        $pattern = preg_replace_callback('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', static function (array $match) use (&$names): string {
            $names[] = $match[1];
            return '([^/]+)';
        }, $path);
        $this->routes[] = compact('method', 'path', 'handler', 'middleware', 'pattern', 'names');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }
            if (!preg_match('#^' . $route['pattern'] . '$#', $path, $matches)) {
                continue;
            }
            array_shift($matches);
            $params = [];
            foreach ($route['names'] as $index => $name) {
                $params[$name] = urldecode($matches[$index] ?? '');
            }
            foreach ($route['middleware'] as $middleware) {
                $middleware();
            }
            ($route['handler'])($params);
            return;
        }
        Response::abort(404, '요청한 페이지를 찾을 수 없습니다.');
    }
}
