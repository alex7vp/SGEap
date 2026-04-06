<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        $viewPath = BASE_PATH . '/app/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo 'La vista solicitada no existe.';
            return;
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
    }

    protected function redirect(string $path): void
    {
        redirect($path);
    }
}
