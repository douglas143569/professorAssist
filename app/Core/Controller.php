<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View nao encontrada: {$view}");
        }

        require __DIR__ . '/../Views/layouts/start.php';
        require $viewPath;
        require __DIR__ . '/../Views/layouts/end.php';
    }

    /**
     * Renderiza uma view SEM o layout do dashboard (sidebar/topbar).
     * Usado por telas que ficam fora da area logada, como o login.
     */
    protected function viewSemLayout(string $view, array $data = []): void
    {
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View nao encontrada: {$view}");
        }

        require $viewPath;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
