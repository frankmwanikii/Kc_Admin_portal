<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\Installer;

class App
{
    public function run(): void
    {
        date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Africa/Nairobi');

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $isSetupRoute = $uri === '/setup' || $uri === '/setup/';

        if (!Installer::isInstalled()) {
            if (!$isSetupRoute) {
                header('Location: /setup');
                exit;
            }
        } elseif ($isSetupRoute) {
            header('Location: /');
            exit;
        }

        Auth::start();

        $router = new Router();
        require dirname(__DIR__, 2) . '/routes/web.php';
        $router->dispatch($method, $_SERVER['REQUEST_URI'] ?? '/');
    }
}
