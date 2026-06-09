<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        extract($data);
        $viewsPath = dirname(__DIR__, 2) . '/views';

        ob_start();
        require $viewsPath . '/' . $template . '.php';
        $content = ob_get_clean();

        if ($layout) {
            require $viewsPath . '/' . $layout . '.php';
        } else {
            echo $content;
        }
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
