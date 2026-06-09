<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\Core\Database;

$db = Database::connection();
$schema = file_get_contents(__DIR__ . '/schema.sql');
$db->exec($schema);

$seed = file_get_contents(__DIR__ . '/seed.sql');
$db->exec($seed);

echo "Database migrated and seeded successfully.\n";
