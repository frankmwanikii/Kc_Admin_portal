<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class InventoryService
{
    private static bool $schemaReady = false;

    public const STATUSES = [
        'available' => 'Available',
        'in_use' => 'In use',
        'reserved' => 'Reserved',
        'maintenance' => 'Maintenance',
        'retired' => 'Retired',
        'missing' => 'Missing',
    ];

    public const CONDITIONS = [
        'excellent' => 'Excellent',
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
        'damaged' => 'Damaged',
        'needs_repair' => 'Needs repair',
    ];

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        FormSubmissionService::ensureFinanceTables();
        $db = Database::connection();

        $existing = [];
        foreach ($db->query('SHOW COLUMNS FROM inventory_items')->fetchAll(PDO::FETCH_COLUMN) as $col) {
            $existing[(string) $col] = true;
        }

        $alters = [
            'description' => "ADD COLUMN description TEXT NULL AFTER notes",
            'sku' => "ADD COLUMN sku VARCHAR(80) NULL AFTER description",
            'barcode' => "ADD COLUMN barcode VARCHAR(80) NULL AFTER sku",
            'brand' => "ADD COLUMN brand VARCHAR(120) NULL AFTER barcode",
            'model_number' => "ADD COLUMN model_number VARCHAR(120) NULL AFTER brand",
            'serial_number' => "ADD COLUMN serial_number VARCHAR(120) NULL AFTER model_number",
            'condition_status' => "ADD COLUMN condition_status VARCHAR(40) NULL DEFAULT 'good' AFTER serial_number",
            'status' => "ADD COLUMN status VARCHAR(40) NOT NULL DEFAULT 'available' AFTER condition_status",
            'campus' => "ADD COLUMN campus VARCHAR(50) NULL AFTER status",
            'department' => "ADD COLUMN department VARCHAR(120) NULL AFTER campus",
            'assigned_to' => "ADD COLUMN assigned_to VARCHAR(150) NULL AFTER department",
            'purchase_date' => "ADD COLUMN purchase_date DATE NULL AFTER assigned_to",
            'purchase_price' => "ADD COLUMN purchase_price DECIMAL(12,2) NULL AFTER purchase_date",
            'current_value' => "ADD COLUMN current_value DECIMAL(12,2) NULL AFTER purchase_price",
            'supplier' => "ADD COLUMN supplier VARCHAR(150) NULL AFTER current_value",
            'warranty_expires' => "ADD COLUMN warranty_expires DATE NULL AFTER supplier",
            'min_quantity' => "ADD COLUMN min_quantity INT NOT NULL DEFAULT 0 AFTER warranty_expires",
            'primary_image_path' => "ADD COLUMN primary_image_path VARCHAR(255) NULL AFTER min_quantity",
            'last_checked_at' => "ADD COLUMN last_checked_at DATE NULL AFTER primary_image_path",
            'checked_by' => "ADD COLUMN checked_by VARCHAR(150) NULL AFTER last_checked_at",
        ];

        foreach ($alters as $column => $sql) {
            if (!isset($existing[$column])) {
                $db->exec('ALTER TABLE inventory_items ' . $sql);
            }
        }

        $db->exec("
            CREATE TABLE IF NOT EXISTS inventory_item_images (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                item_id INT UNSIGNED NOT NULL,
                path VARCHAR(255) NOT NULL,
                caption VARCHAR(255) NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_inventory_images_item (item_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        self::$schemaReady = true;
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query('SELECT * FROM inventory_items ORDER BY name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM inventory_items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        self::ensureSchema();
        $fields = self::normalizeFields($data);
        $stmt = Database::connection()->prepare('
            INSERT INTO inventory_items (
                name, category, quantity, unit, location, notes, description,
                sku, barcode, brand, model_number, serial_number,
                condition_status, status, campus, department, assigned_to,
                purchase_date, purchase_price, current_value, supplier, warranty_expires,
                min_quantity, last_checked_at, checked_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?
            )
        ');
        $stmt->execute([
            $fields['name'],
            $fields['category'],
            $fields['quantity'],
            $fields['unit'],
            $fields['location'],
            $fields['notes'],
            $fields['description'],
            $fields['sku'],
            $fields['barcode'],
            $fields['brand'],
            $fields['model_number'],
            $fields['serial_number'],
            $fields['condition_status'],
            $fields['status'],
            $fields['campus'],
            $fields['department'],
            $fields['assigned_to'],
            $fields['purchase_date'],
            $fields['purchase_price'],
            $fields['current_value'],
            $fields['supplier'],
            $fields['warranty_expires'],
            $fields['min_quantity'],
            $fields['last_checked_at'],
            $fields['checked_by'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        self::ensureSchema();
        $existing = self::find($id);
        if (!$existing) {
            throw new \RuntimeException('Item not found.');
        }

        // Quick-edit forms only send core fields — keep detailed profile values.
        $detailKeys = [
            'description', 'sku', 'barcode', 'brand', 'model_number', 'serial_number',
            'condition_status', 'status', 'campus', 'department', 'assigned_to',
            'purchase_date', 'purchase_price', 'current_value', 'supplier', 'warranty_expires',
            'min_quantity', 'last_checked_at', 'checked_by',
        ];
        foreach ($detailKeys as $key) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $existing[$key] ?? null;
            }
        }

        $fields = self::normalizeFields($data);
        $stmt = Database::connection()->prepare('
            UPDATE inventory_items SET
                name = ?, category = ?, quantity = ?, unit = ?, location = ?, notes = ?, description = ?,
                sku = ?, barcode = ?, brand = ?, model_number = ?, serial_number = ?,
                condition_status = ?, status = ?, campus = ?, department = ?, assigned_to = ?,
                purchase_date = ?, purchase_price = ?, current_value = ?, supplier = ?, warranty_expires = ?,
                min_quantity = ?, last_checked_at = ?, checked_by = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $fields['name'],
            $fields['category'],
            $fields['quantity'],
            $fields['unit'],
            $fields['location'],
            $fields['notes'],
            $fields['description'],
            $fields['sku'],
            $fields['barcode'],
            $fields['brand'],
            $fields['model_number'],
            $fields['serial_number'],
            $fields['condition_status'],
            $fields['status'],
            $fields['campus'],
            $fields['department'],
            $fields['assigned_to'],
            $fields['purchase_date'],
            $fields['purchase_price'],
            $fields['current_value'],
            $fields['supplier'],
            $fields['warranty_expires'],
            $fields['min_quantity'],
            $fields['last_checked_at'],
            $fields['checked_by'],
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        self::ensureSchema();
        foreach (self::images($id) as $image) {
            self::deleteImageFile((string) ($image['path'] ?? ''));
        }
        Database::connection()->prepare('DELETE FROM inventory_item_images WHERE item_id = ?')->execute([$id]);

        $item = self::find($id);
        if ($item && !empty($item['primary_image_path'])) {
            self::deleteImageFile((string) $item['primary_image_path']);
        }

        Database::connection()->prepare('DELETE FROM inventory_items WHERE id = ?')->execute([$id]);
    }

    /** @return list<array<string, mixed>> */
    public static function images(int $itemId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('
            SELECT * FROM inventory_item_images
            WHERE item_id = ?
            ORDER BY is_primary DESC, sort_order ASC, id ASC
        ');
        $stmt->execute([$itemId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $file
     */
    public static function addImage(int $itemId, array $file, ?string $caption = null): int
    {
        self::ensureSchema();
        $path = self::storeUploadedImage($itemId, $file);
        $images = self::images($itemId);
        $isPrimary = $images === [] ? 1 : 0;
        $sort = count($images);

        $stmt = Database::connection()->prepare('
            INSERT INTO inventory_item_images (item_id, path, caption, is_primary, sort_order)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $itemId,
            $path,
            $caption !== null && trim($caption) !== '' ? trim($caption) : null,
            $isPrimary,
            $sort,
        ]);

        if ($isPrimary) {
            self::setPrimaryImagePath($itemId, $path);
        }

        return (int) Database::connection()->lastInsertId();
    }

    public static function setPrimaryImage(int $itemId, int $imageId): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM inventory_item_images WHERE id = ? AND item_id = ?');
        $stmt->execute([$imageId, $itemId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$image) {
            throw new \RuntimeException('Image not found.');
        }

        $db = Database::connection();
        $db->prepare('UPDATE inventory_item_images SET is_primary = 0 WHERE item_id = ?')->execute([$itemId]);
        $db->prepare('UPDATE inventory_item_images SET is_primary = 1 WHERE id = ?')->execute([$imageId]);
        self::setPrimaryImagePath($itemId, (string) $image['path']);
    }

    public static function deleteImage(int $itemId, int $imageId): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM inventory_item_images WHERE id = ? AND item_id = ?');
        $stmt->execute([$imageId, $itemId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$image) {
            return;
        }

        Database::connection()->prepare('DELETE FROM inventory_item_images WHERE id = ?')->execute([$imageId]);
        self::deleteImageFile((string) $image['path']);

        $remaining = self::images($itemId);
        if ((int) ($image['is_primary'] ?? 0) === 1) {
            if ($remaining !== []) {
                self::setPrimaryImage($itemId, (int) $remaining[0]['id']);
            } else {
                self::setPrimaryImagePath($itemId, null);
            }
        }
    }

    public static function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        $relative = ltrim($path, '/');
        $full = dirname(__DIR__, 2) . '/public/' . $relative;
        if (!is_file($full)) {
            return null;
        }

        return '/' . $relative . '?v=' . (int) filemtime($full);
    }

    public static function statusLabel(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return self::STATUSES[$status] ?? ($status !== '' ? ucwords(str_replace('_', ' ', $status)) : '—');
    }

    public static function conditionLabel(?string $condition): string
    {
        $condition = strtolower(trim((string) $condition));

        return self::CONDITIONS[$condition] ?? ($condition !== '' ? ucwords(str_replace('_', ' ', $condition)) : '—');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function normalizeFields(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Item name is required.');
        }

        $status = strtolower(trim((string) ($data['status'] ?? 'available')));
        if (!isset(self::STATUSES[$status])) {
            $status = 'available';
        }

        $condition = strtolower(trim((string) ($data['condition_status'] ?? 'good')));
        if (!isset(self::CONDITIONS[$condition])) {
            $condition = 'good';
        }

        $money = static function ($value): ?float {
            if ($value === null || $value === '') {
                return null;
            }

            return round((float) $value, 2);
        };

        $date = static function ($value): ?string {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }
            $ts = strtotime($value);

            return $ts ? date('Y-m-d', $ts) : null;
        };

        $nullable = static function ($value): ?string {
            $value = trim((string) $value);

            return $value !== '' ? $value : null;
        };

        return [
            'name' => $name,
            'category' => $nullable($data['category'] ?? null),
            'quantity' => max(0, (int) ($data['quantity'] ?? 0)),
            'unit' => $nullable($data['unit'] ?? null) ?? 'pcs',
            'location' => $nullable($data['location'] ?? null),
            'notes' => $nullable($data['notes'] ?? null),
            'description' => $nullable($data['description'] ?? null),
            'sku' => $nullable($data['sku'] ?? null),
            'barcode' => $nullable($data['barcode'] ?? null),
            'brand' => $nullable($data['brand'] ?? null),
            'model_number' => $nullable($data['model_number'] ?? null),
            'serial_number' => $nullable($data['serial_number'] ?? null),
            'condition_status' => $condition,
            'status' => $status,
            'campus' => $nullable($data['campus'] ?? null),
            'department' => $nullable($data['department'] ?? null),
            'assigned_to' => $nullable($data['assigned_to'] ?? null),
            'purchase_date' => $date($data['purchase_date'] ?? null),
            'purchase_price' => $money($data['purchase_price'] ?? null),
            'current_value' => $money($data['current_value'] ?? null),
            'supplier' => $nullable($data['supplier'] ?? null),
            'warranty_expires' => $date($data['warranty_expires'] ?? null),
            'min_quantity' => max(0, (int) ($data['min_quantity'] ?? 0)),
            'last_checked_at' => $date($data['last_checked_at'] ?? null),
            'checked_by' => $nullable($data['checked_by'] ?? null),
        ];
    }

    private static function setPrimaryImagePath(int $itemId, ?string $path): void
    {
        Database::connection()
            ->prepare('UPDATE inventory_items SET primary_image_path = ? WHERE id = ?')
            ->execute([$path, $itemId]);
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function storeUploadedImage(int $itemId, array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(match ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image is too large. Maximum size is 5 MB.',
                UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
                default => 'Image upload failed. Please try again.',
            });
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Images must be JPG, PNG, WebP, or GIF.');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Each image must be under 5 MB.');
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $dir = dirname(__DIR__, 2) . '/public/uploads/inventory/' . $itemId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the inventory image folder.');
        }
        @chmod($dir, 0755);

        $filename = 'img-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Failed to save image.');
        }
        @chmod($dest, 0644);

        return 'uploads/inventory/' . $itemId . '/' . $filename;
    }

    private static function deleteImageFile(string $path): void
    {
        $path = trim($path);
        if ($path === '' || str_contains($path, '..')) {
            return;
        }
        $full = dirname(__DIR__, 2) . '/public/' . ltrim($path, '/');
        if (is_file($full)) {
            unlink($full);
        }
    }
}
