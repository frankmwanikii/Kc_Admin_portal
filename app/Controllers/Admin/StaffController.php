<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class StaffController
{
    public function index(): void
    {
        Auth::requireAdmin();
        \App\Services\FormSubmissionService::ensureFinanceTables();

        $error = $_GET['error'] ?? null;
        $staff = [];
        try {
            $staff = Database::connection()->query('
                SELECT * FROM staff_members ORDER BY name ASC
            ')->fetchAll();
        } catch (\Throwable $e) {
            $error = $error ?: ('Could not load staff: ' . $e->getMessage());
        }

        View::render('admin/staff/index', [
            'title' => 'Staff',
            'staff' => $staff,
            'error' => $error,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        \App\Services\FormSubmissionService::ensureFinanceTables();

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            View::redirect('/admin/staff');
        }

        try {
            $stmt = Database::connection()->prepare('
                INSERT INTO staff_members (name, role_title, department, phone, email, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $name,
                trim($_POST['role_title'] ?? '') ?: null,
                trim($_POST['department'] ?? '') ?: null,
                trim($_POST['phone'] ?? '') ?: null,
                $this->normalizeEmail($_POST['email'] ?? ''),
                $this->normalizeStatus($_POST['status'] ?? 'active'),
                trim($_POST['notes'] ?? '') ?: null,
            ]);
        } catch (\Throwable $e) {
            View::redirect('/admin/staff?error=' . urlencode('Could not save staff: ' . $e->getMessage()));
        }

        View::redirect('/admin/staff');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        \App\Services\FormSubmissionService::ensureFinanceTables();

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            View::redirect('/admin/staff');
        }

        $stmt = Database::connection()->prepare('
            UPDATE staff_members
            SET name = ?, role_title = ?, department = ?, phone = ?, email = ?, status = ?, notes = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $name,
            trim($_POST['role_title'] ?? '') ?: null,
            trim($_POST['department'] ?? '') ?: null,
            trim($_POST['phone'] ?? '') ?: null,
            $this->normalizeEmail($_POST['email'] ?? ''),
            $this->normalizeStatus($_POST['status'] ?? 'active'),
            trim($_POST['notes'] ?? '') ?: null,
            (int) $id,
        ]);

        View::redirect('/admin/staff');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        \App\Services\FormSubmissionService::ensureFinanceTables();
        Database::connection()->prepare('DELETE FROM staff_members WHERE id = ?')->execute([(int) $id]);
        View::redirect('/admin/staff');
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizeStatus(mixed $status): string
    {
        $status = strtolower(trim((string) $status));

        return in_array($status, ['active', 'inactive', 'on_leave'], true) ? $status : 'active';
    }
}
