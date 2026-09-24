<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Services\InventoryService;

class InventoryController
{
    public function index(): void
    {
        Auth::requireAdmin();
        InventoryService::ensureSchema();

        $items = array_map(static function (array $item): array {
            $item['image_url'] = InventoryService::imageUrl($item['primary_image_path'] ?? null);

            return $item;
        }, InventoryService::all());

        View::render('admin/inventory/index', [
            'title' => 'Inventory',
            'items' => $items,
            'success' => $_GET['added'] ?? null,
            'error' => $_GET['error'] ?? null,
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        Auth::requireAdmin();
        $item = InventoryService::find((int) $id);
        if (!$item) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Item not found'], 'layouts/admin');

            return;
        }

        View::render('admin/inventory/show', [
            'title' => (string) ($item['name'] ?? 'Inventory item'),
            'item' => $item,
            'images' => InventoryService::images((int) $id),
            'statuses' => InventoryService::STATUSES,
            'conditions' => InventoryService::CONDITIONS,
            'success' => $_GET['saved'] ?? $_GET['photo'] ?? null,
            'error' => $_GET['error'] ?? null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        try {
            $id = InventoryService::create($_POST);
            View::redirect('/admin/inventory/' . $id . '?added=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/inventory?error=' . urlencode($e->getMessage()));
        }
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        $itemId = (int) $id;
        if (!InventoryService::find($itemId)) {
            View::redirect('/admin/inventory?error=' . urlencode('Item not found.'));

            return;
        }

        try {
            InventoryService::update($itemId, $_POST);
            View::redirect('/admin/inventory/' . $itemId . '?saved=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/inventory/' . $itemId . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        try {
            InventoryService::delete((int) $id);
        } catch (\Throwable) {
            // still redirect
        }
        View::redirect('/admin/inventory');
    }

    public function uploadImages(string $id): void
    {
        Auth::requireAdmin();
        $itemId = (int) $id;
        if (!InventoryService::find($itemId)) {
            View::redirect('/admin/inventory?error=' . urlencode('Item not found.'));

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
                    InventoryService::addImage($itemId, [
                        'name' => $files['name'][$i] ?? '',
                        'type' => $files['type'][$i] ?? '',
                        'tmp_name' => $files['tmp_name'][$i] ?? '',
                        'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $files['size'][$i] ?? 0,
                    ], $caption !== '' ? $caption : null);
                    $uploaded++;
                }
            } elseif (is_array($files) && !empty($files['name'])) {
                InventoryService::addImage($itemId, $files, $caption !== '' ? $caption : null);
                $uploaded = 1;
            }

            if ($uploaded === 0) {
                throw new \RuntimeException('Choose at least one image to upload.');
            }

            View::redirect('/admin/inventory/' . $itemId . '?photo=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/inventory/' . $itemId . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function setPrimaryImage(string $id, string $imageId): void
    {
        Auth::requireAdmin();
        $itemId = (int) $id;
        try {
            InventoryService::setPrimaryImage($itemId, (int) $imageId);
            View::redirect('/admin/inventory/' . $itemId . '?photo=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/inventory/' . $itemId . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteImage(string $id, string $imageId): void
    {
        Auth::requireAdmin();
        $itemId = (int) $id;
        try {
            InventoryService::deleteImage($itemId, (int) $imageId);
            View::redirect('/admin/inventory/' . $itemId . '?photo=1');
        } catch (\Throwable $e) {
            View::redirect('/admin/inventory/' . $itemId . '?error=' . urlencode($e->getMessage()));
        }
    }
}
