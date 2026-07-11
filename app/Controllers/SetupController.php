<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\Installer;

class SetupController
{
    public function show(): void
    {
        if (Installer::isInstalled()) {
            View::redirect('/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $defaultUrl = $scheme . '://' . $host;

        View::render('setup/index', [
            'title' => 'System Setup',
            'defaults' => [
                'db_host' => '127.0.0.1',
                'db_port' => '3306',
                'db_name' => 'church_mis',
                'db_username' => 'root',
                'db_password' => '',
                'church_name' => 'Grace Community Church',
                'admin_email' => 'admin@church.local',
                'admin_password' => '',
                'app_url' => $defaultUrl,
                'forms_db_host' => '127.0.0.1',
                'forms_db_port' => '3306',
                'forms_db_name' => 'kingdomcity_forms',
                'forms_db_username' => 'root',
                'forms_db_password' => '',
            ],
            'data' => [],
            'error' => null,
        ], 'layouts/setup');
    }

    public function install(): void
    {
        if (Installer::isInstalled()) {
            View::redirect('/');
        }

        $data = [
            'db_host' => trim($_POST['db_host'] ?? '127.0.0.1'),
            'db_port' => trim($_POST['db_port'] ?? '3306'),
            'db_name' => trim($_POST['db_name'] ?? ''),
            'db_username' => trim($_POST['db_username'] ?? 'root'),
            'db_password' => $_POST['db_password'] ?? '',
            'church_name' => trim($_POST['church_name'] ?? ''),
            'admin_email' => trim($_POST['admin_email'] ?? ''),
            'admin_password' => $_POST['admin_password'] ?? '',
            'app_url' => rtrim(trim($_POST['app_url'] ?? ''), '/'),
            'forms_db_host' => trim($_POST['forms_db_host'] ?? ''),
            'forms_db_port' => trim($_POST['forms_db_port'] ?? ''),
            'forms_db_name' => trim($_POST['forms_db_name'] ?? ''),
            'forms_db_username' => trim($_POST['forms_db_username'] ?? ''),
            'forms_db_password' => $_POST['forms_db_password'] ?? '',
        ];

        $error = $this->validate($data);
        if ($error) {
            View::render('setup/index', [
                'title' => 'System Setup',
                'defaults' => $data,
                'data' => $data,
                'error' => $error,
            ], 'layouts/setup');
            return;
        }

        try {
            Installer::testConnection(
                $data['db_host'],
                $data['db_port'],
                $data['db_name'],
                $data['db_username'],
                $data['db_password']
            );
            Installer::install($data);
            View::redirect('/login?setup=1');
        } catch (\Throwable $e) {
            View::render('setup/index', [
                'title' => 'System Setup',
                'defaults' => $data,
                'data' => $data,
                'error' => $e->getMessage(),
            ], 'layouts/setup');
        }
    }

  /** @param array<string, string> $data */
    private function validate(array $data): ?string
    {
        if ($data['db_name'] === '') {
            return 'Database name is required.';
        }
        if ($data['church_name'] === '') {
            return 'Church name is required.';
        }
        if ($data['admin_email'] === '' || !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            return 'A valid admin email is required.';
        }
        if (strlen($data['admin_password']) < 8) {
            return 'Admin password must be at least 8 characters.';
        }
        if ($data['app_url'] === '') {
            return 'Application URL is required.';
        }
        return null;
    }
}
