<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $uri, array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, array $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = $this->normalizeUri($uri);
        $route = $this->routes[$method][$uri] ?? null;

        if ($route === null) {
            http_response_code(404);
            echo 'Ruta no encontrada.';
            return;
        }

        [$controllerClass, $controllerMethod] = $route;
        $controller = new $controllerClass();
        $controller->{$controllerMethod}();
    }

    private function addRoute(string $method, string $uri, array $action): void
    {
        $this->routes[$method][$this->normalizeUri($uri)] = $action;
    }

    private function normalizeUri(string $uri): string
    {
        $normalized = '/' . trim($uri, '/');
        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }
}
