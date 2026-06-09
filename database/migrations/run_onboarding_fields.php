<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->safeLoad();

use App\Core\Database;

$db = Database::connection();

$memberColumns = [
    'residence' => 'VARCHAR(255) NULL AFTER phone',
    'county' => 'VARCHAR(100) NULL AFTER residence',
    'spouse_name' => 'VARCHAR(150) NULL AFTER marital_status',
    'employer' => 'VARCHAR(150) NULL AFTER occupation',
    'emergency_contact_name' => 'VARCHAR(150) NULL AFTER employer',
    'emergency_contact_phone' => 'VARCHAR(30) NULL AFTER emergency_contact_name',
    'how_heard_about_us' => 'VARCHAR(100) NULL AFTER emergency_contact_phone',
    'previous_church' => 'VARCHAR(200) NULL AFTER how_heard_about_us',
    'baptized' => 'TINYINT(1) DEFAULT 0 AFTER previous_church',
    'baptism_date' => 'DATE NULL AFTER baptized',
    'wish_to_be_baptized' => 'TINYINT(1) DEFAULT 0 AFTER baptism_date',
    'ministry_interests' => 'TEXT NULL AFTER wish_to_be_baptized',
    'skills_talents' => 'TEXT NULL AFTER ministry_interests',
    'member_notes' => 'TEXT NULL AFTER skills_talents',
];

$existing = $db->query('SHOW COLUMNS FROM members')->fetchAll(PDO::FETCH_COLUMN);

foreach ($memberColumns as $name => $definition) {
    if (!in_array($name, $existing, true)) {
        $db->exec("ALTER TABLE members ADD COLUMN {$name} {$definition}");
        echo "Added members.{$name}\n";
    }
}

$db->exec("
    CREATE TABLE IF NOT EXISTS household_children (
        id INT AUTO_INCREMENT PRIMARY KEY,
        household_id INT NOT NULL,
        name VARCHAR(150) NOT NULL,
        age INT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

echo "Onboarding migration complete.\n";
