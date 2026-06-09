<?php

declare(strict_types=1);

use App\Core\App;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$app = new App();
$app->run();
