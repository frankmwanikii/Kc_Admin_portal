<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $routes = [];

    public function get(string $path, array $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): self
    {
        $this->routes[$method][$this->normalize($path)] = $handler;
        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler && $method === 'HEAD') {
            $handler = $this->routes['GET'][$path] ?? null;
        }

        if (!$handler) {
            $methods = $method === 'HEAD' ? ['HEAD', 'GET'] : [$method];
            foreach ($methods as $tryMethod) {
                foreach ($this->routes[$tryMethod] ?? [] as $route => $h) {
                    $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $route);
                    if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                        array_shift($matches);
                        [$class, $action] = $h;
                        if ($method === 'HEAD') {
                            ob_start();
                            (new $class())->$action(...$matches);
                            ob_end_clean();
                        } else {
                            (new $class())->$action(...$matches);
                        }
                        return;
                    }
                }
            }
            http_response_code(404);
            View::render('errors/404', ['title' => 'Page Not Found']);
            return;
        }

        [$class, $action] = $handler;
        if ($method === 'HEAD') {
            ob_start();
            (new $class())->$action();
            ob_end_clean();
            return;
        }
        (new $class())->$action();
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
