<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class InventoryController
{
    public function index(): void
    {
        Auth::requireAdmin();
        \App\Services\FormSubmissionService::ensureFinanceTables();

        $items = Database::connection()->query('
            SELECT * FROM inventory_items ORDER BY name ASC
        ')->fetchAll();

        View::render('admin/inventory/index', [
            'title' => 'Inventory',
            'items' => $items,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        \App\Services\FormSubmissionService::ensureFinanceTables();

        $stmt = Database::connection()->prepare('
            INSERT INTO inventory_items (name, category, quantity, unit, location, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['category'] ?? '') ?: null,
            (int) ($_POST['quantity'] ?? 0),
            trim($_POST['unit'] ?? 'pcs') ?: 'pcs',
            trim($_POST['location'] ?? '') ?: null,
            trim($_POST['notes'] ?? '') ?: null,
        ]);

        View::redirect('/admin/inventory');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        \App\Services\FormSubmissionService::ensureFinanceTables();

        $stmt = Database::connection()->prepare('
            UPDATE inventory_items
            SET name = ?, category = ?, quantity = ?, unit = ?, location = ?, notes = ?
            WHERE id = ?
        ');
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['category'] ?? '') ?: null,
            (int) ($_POST['quantity'] ?? 0),
            trim($_POST['unit'] ?? 'pcs') ?: 'pcs',
            trim($_POST['location'] ?? '') ?: null,
            trim($_POST['notes'] ?? '') ?: null,
            (int) $id,
        ]);

        View::redirect('/admin/inventory');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Database::connection()->prepare('DELETE FROM inventory_items WHERE id = ?')->execute([(int) $id]);
        View::redirect('/admin/inventory');
    }
}
