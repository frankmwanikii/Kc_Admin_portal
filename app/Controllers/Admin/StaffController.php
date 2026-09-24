<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Services\StaffService;

class StaffController
{
    public function index(): void
    {
        Auth::requireAdmin();
        StaffService::ensureSchema();

        $error = $_GET['error'] ?? null;
        $staff = [];
        try {
            $staff = array_map(static function (array $person): array {
                $person['photo_url'] = StaffService::imageUrl($person['photo_path'] ?? null);

                return $person;
            }, StaffService::all());
        } catch (\Throwable $e) {
            $error = $error ?: ('Could not load staff: ' . $e->getMessage());
        }

        View::render('admin/staff/index', [
            'title' => 'Staff',
            'staff' => $staff,
            'error' => $error,
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        Auth::requireAdmin();
        $person = StaffService::find((int) $id);
        if (!$person) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Staff not found']);

            return;
        }

        View::render('admin/staff/show', [
            'title' => (string) ($person['name'] ?? 'Staff'),
            'person' => $person,
            'images' => StaffService::images((int) $id),
            'statuses' => StaffService::STATUSES,
            'employmentTypes' => StaffService::EMPLOYMENT_TYPES,
            'genders' => StaffService::GENDERS,
            'success' => $_GET['saved'] ?? $_GET['photo'] ?? $_GET['added'] ?? null,
            'error' => $_GET['error'] ?? null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        try {
            $id = StaffService::create($_POST);
            View::redirect('/admin/staff/' . $id . '?added=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/staff?error=' . urlencode($e->getMessage()));
        }
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        $staffId = (int) $id;
        if (!StaffService::find($staffId)) {
            View::redirect('/admin/staff?error=' . urlencode('Staff member not found.'));

            return;
        }

        try {
            StaffService::update($staffId, $_POST);
            View::redirect('/admin/staff/' . $staffId . '?saved=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/staff/' . $staffId . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        try {
            StaffService::delete((int) $id);
        } catch (\Throwable) {
            // still redirect
        }
        View::redirect('/admin/staff');
    }

    public function uploadImages(string $id): void
    {
        Auth::requireAdmin();
        $staffId = (int) $id;
        if (!StaffService::find($staffId)) {
            View::redirect('/admin/staff?error=' . urlencode('Staff member not found.'));

            return;
        }

        try {
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $uploaded = 0;
            $files = $_FILES['images'] ?? null;

            if (is_array($files) && isset($files['name']) && is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    StaffService::addImage($staffId, [
                        'name' => $files['name'][$i] ?? '',
                        'type' => $files['type'][$i] ?? '',
                        'tmp_name' => $files['tmp_name'][$i] ?? '',
                        'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $files['size'][$i] ?? 0,
                    ], $caption !== '' ? $caption : null);
                    $uploaded++;
                }
            } elseif (is_array($files) && !empty($files['name'])) {
                StaffService::addImage($staffId, $files, $caption !== '' ? $caption : null);
                $uploaded = 1;
            }

            if ($uploaded === 0) {
                throw new \RuntimeException('Choose at least one photo to upload.');
            }

            View::redirect('/admin/staff/' . $staffId . '?photo=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/staff/' . $staffId . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function setPrimaryImage(string $id, string $imageId): void
    {
        Auth::requireAdmin();
        $staffId = (int) $id;
        try {
            StaffService::setPrimaryImage($staffId, (int) $imageId);
            View::redirect('/admin/staff/' . $staffId . '?photo=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/staff/' . $staffId . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteImage(string $id, string $imageId): void
    {
        Auth::requireAdmin();
        $staffId = (int) $id;
        try {
            StaffService::deleteImage($staffId, (int) $imageId);
            View::redirect('/admin/staff/' . $staffId . '?photo=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/staff/' . $staffId . '?error=' . urlencode($e->getMessage()));
        }
    }
}
