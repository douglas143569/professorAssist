<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $uri, string $action): void
    {
        $this->add('GET', $uri, $action);
    }

    public function post(string $uri, string $action): void
    {
        $this->add('POST', $uri, $action);
    }

    private function add(string $method, string $uri, string $action): void
    {
        $pattern = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', trim($uri, '/'));
        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'action' => $action,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);
                [$controllerName, $action] = explode('@', $route['action']);

                $controllerClass = "App\\Controllers\\{$controllerName}";

                if (!class_exists($controllerClass)) {
                    $this->abort(500, "Controller nao encontrado: {$controllerClass}");
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $action)) {
                    $this->abort(500, "Metodo nao encontrado: {$controllerClass}::{$action}");
                }

                call_user_func_array([$controller, $action], $matches);
                return;
            }
        }

        $this->abort(404, 'Pagina nao encontrada');
    }

    private function abort(int $status, string $message): void
    {
        http_response_code($status);
        echo $message;
        exit;
    }
}
