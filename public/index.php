<?php

declare(strict_types=1);

use App\Core\App;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

try {
    $app = new App();
    $app->run();
} catch (Throwable $e) {
    http_response_code(500);
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo '<h1>Application Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo 'Server error. Check cPanel error logs or visit /diagnose.php for help.';
    }
}
