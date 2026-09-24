<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class StaffService
{
    private static bool $schemaReady = false;

    public const STATUSES = [
        'active' => 'Active',
        'on_leave' => 'On leave',
        'inactive' => 'Inactive',
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'volunteer' => 'Volunteer',
        'contractor' => 'Contractor',
        'intern' => 'Intern',
    ];

    public const GENDERS = [
        'female' => 'Female',
        'male' => 'Male',
        'other' => 'Other / prefer not to say',
    ];

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        FormSubmissionService::ensureFinanceTables();
        $db = Database::connection();

        $existing = [];
        foreach ($db->query('SHOW COLUMNS FROM staff_members')->fetchAll(PDO::FETCH_COLUMN) as $col) {
            $existing[(string) $col] = true;
        }

        $alters = [
            'bio' => "ADD COLUMN bio TEXT NULL AFTER notes",
            'staff_code' => "ADD COLUMN staff_code VARCHAR(80) NULL AFTER bio",
            'gender' => "ADD COLUMN gender VARCHAR(30) NULL AFTER staff_code",
            'date_of_birth' => "ADD COLUMN date_of_birth DATE NULL AFTER gender",
            'national_id' => "ADD COLUMN national_id VARCHAR(80) NULL AFTER date_of_birth",
            'marital_status' => "ADD COLUMN marital_status VARCHAR(40) NULL AFTER national_id",
            'address' => "ADD COLUMN address VARCHAR(255) NULL AFTER marital_status",
            'city' => "ADD COLUMN city VARCHAR(120) NULL AFTER address",
            'campus' => "ADD COLUMN campus VARCHAR(50) NULL AFTER city",
            'employment_type' => "ADD COLUMN employment_type VARCHAR(40) NULL DEFAULT 'full_time' AFTER campus",
            'hire_date' => "ADD COLUMN hire_date DATE NULL AFTER employment_type",
            'end_date' => "ADD COLUMN end_date DATE NULL AFTER hire_date",
            'reports_to' => "ADD COLUMN reports_to VARCHAR(150) NULL AFTER end_date",
            'secondary_phone' => "ADD COLUMN secondary_phone VARCHAR(64) NULL AFTER reports_to",
            'emergency_contact_name' => "ADD COLUMN emergency_contact_name VARCHAR(150) NULL AFTER secondary_phone",
            'emergency_contact_phone' => "ADD COLUMN emergency_contact_phone VARCHAR(64) NULL AFTER emergency_contact_name",
            'emergency_contact_relation' => "ADD COLUMN emergency_contact_relation VARCHAR(80) NULL AFTER emergency_contact_phone",
            'skills' => "ADD COLUMN skills TEXT NULL AFTER emergency_contact_relation",
            'ministries' => "ADD COLUMN ministries TEXT NULL AFTER skills",
            'education' => "ADD COLUMN education TEXT NULL AFTER ministries",
            'languages' => "ADD COLUMN languages VARCHAR(255) NULL AFTER education",
            'work_schedule' => "ADD COLUMN work_schedule VARCHAR(255) NULL AFTER languages",
            'office_location' => "ADD COLUMN office_location VARCHAR(255) NULL AFTER work_schedule",
            'photo_path' => "ADD COLUMN photo_path VARCHAR(255) NULL AFTER office_location",
        ];

        foreach ($alters as $column => $sql) {
            if (!isset($existing[$column])) {
                $db->exec('ALTER TABLE staff_members ' . $sql);
            }
        }

        $db->exec("
            CREATE TABLE IF NOT EXISTS staff_member_images (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                staff_id INT UNSIGNED NOT NULL,
                path VARCHAR(255) NOT NULL,
                caption VARCHAR(255) NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_staff_images_staff (staff_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        self::$schemaReady = true;
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        self::ensureSchema();

        return Database::connection()
            ->query('SELECT * FROM staff_members ORDER BY name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM staff_members WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        self::ensureSchema();
        $fields = self::normalizeFields($data);
        $stmt = Database::connection()->prepare('
            INSERT INTO staff_members (
                name, role_title, department, phone, email, status, notes, bio,
                staff_code, gender, date_of_birth, national_id, marital_status,
                address, city, campus, employment_type, hire_date, end_date, reports_to,
                secondary_phone, emergency_contact_name, emergency_contact_phone, emergency_contact_relation,
                skills, ministries, education, languages, work_schedule, office_location
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )
        ');
        $stmt->execute(self::fieldValues($fields));

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        self::ensureSchema();
        $existing = self::find($id);
        if (!$existing) {
            throw new \RuntimeException('Staff member not found.');
        }

        $detailKeys = [
            'bio', 'staff_code', 'gender', 'date_of_birth', 'national_id', 'marital_status',
            'address', 'city', 'campus', 'employment_type', 'hire_date', 'end_date', 'reports_to',
            'secondary_phone', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'skills', 'ministries', 'education', 'languages', 'work_schedule', 'office_location',
        ];
        foreach ($detailKeys as $key) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $existing[$key] ?? null;
            }
        }

        $fields = self::normalizeFields($data);
        $stmt = Database::connection()->prepare('
            UPDATE staff_members SET
                name = ?, role_title = ?, department = ?, phone = ?, email = ?, status = ?, notes = ?, bio = ?,
                staff_code = ?, gender = ?, date_of_birth = ?, national_id = ?, marital_status = ?,
                address = ?, city = ?, campus = ?, employment_type = ?, hire_date = ?, end_date = ?, reports_to = ?,
                secondary_phone = ?, emergency_contact_name = ?, emergency_contact_phone = ?, emergency_contact_relation = ?,
                skills = ?, ministries = ?, education = ?, languages = ?, work_schedule = ?, office_location = ?
            WHERE id = ?
        ');
        $stmt->execute([...self::fieldValues($fields), $id]);
    }

    public static function delete(int $id): void
    {
        self::ensureSchema();
        foreach (self::images($id) as $image) {
            self::deleteImageFile((string) ($image['path'] ?? ''));
        }
        Database::connection()->prepare('DELETE FROM staff_member_images WHERE staff_id = ?')->execute([$id]);

        $person = self::find($id);
        if ($person && !empty($person['photo_path'])) {
            self::deleteImageFile((string) $person['photo_path']);
        }

        Database::connection()->prepare('DELETE FROM staff_members WHERE id = ?')->execute([$id]);
    }

    /** @return list<array<string, mixed>> */
    public static function images(int $staffId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('
            SELECT * FROM staff_member_images
            WHERE staff_id = ?
            ORDER BY is_primary DESC, sort_order ASC, id ASC
        ');
        $stmt->execute([$staffId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array<string, mixed> $file */
    public static function addImage(int $staffId, array $file, ?string $caption = null): int
    {
        self::ensureSchema();
        $path = self::storeUploadedImage($staffId, $file);
        $images = self::images($staffId);
        $isPrimary = $images === [] ? 1 : 0;
        $sort = count($images);

        $stmt = Database::connection()->prepare('
            INSERT INTO staff_member_images (staff_id, path, caption, is_primary, sort_order)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $staffId,
            $path,
            $caption !== null && trim($caption) !== '' ? trim($caption) : null,
            $isPrimary,
            $sort,
        ]);

        if ($isPrimary) {
            self::setPhotoPath($staffId, $path);
        }

        return (int) Database::connection()->lastInsertId();
    }

    public static function setPrimaryImage(int $staffId, int $imageId): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM staff_member_images WHERE id = ? AND staff_id = ?');
        $stmt->execute([$imageId, $staffId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$image) {
            throw new \RuntimeException('Image not found.');
        }

        $db = Database::connection();
        $db->prepare('UPDATE staff_member_images SET is_primary = 0 WHERE staff_id = ?')->execute([$staffId]);
        $db->prepare('UPDATE staff_member_images SET is_primary = 1 WHERE id = ?')->execute([$imageId]);
        self::setPhotoPath($staffId, (string) $image['path']);
    }

    public static function deleteImage(int $staffId, int $imageId): void
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM staff_member_images WHERE id = ? AND staff_id = ?');
        $stmt->execute([$imageId, $staffId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$image) {
            return;
        }

        Database::connection()->prepare('DELETE FROM staff_member_images WHERE id = ?')->execute([$imageId]);
        self::deleteImageFile((string) $image['path']);

        $remaining = self::images($staffId);
        if ((int) ($image['is_primary'] ?? 0) === 1) {
            if ($remaining !== []) {
                self::setPrimaryImage($staffId, (int) $remaining[0]['id']);
            } else {
                self::setPhotoPath($staffId, null);
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

    public static function employmentLabel(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return self::EMPLOYMENT_TYPES[$type] ?? ($type !== '' ? ucwords(str_replace('_', ' ', $type)) : '—');
    }

    public static function genderLabel(?string $gender): string
    {
        $gender = strtolower(trim((string) $gender));

        return self::GENDERS[$gender] ?? ($gender !== '' ? ucfirst($gender) : '—');
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : '?';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function normalizeFields(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Full name is required.');
        }

        $status = strtolower(trim((string) ($data['status'] ?? 'active')));
        if (!isset(self::STATUSES[$status])) {
            $status = 'active';
        }

        $employment = strtolower(trim((string) ($data['employment_type'] ?? 'full_time')));
        if (!isset(self::EMPLOYMENT_TYPES[$employment])) {
            $employment = 'full_time';
        }

        $gender = strtolower(trim((string) ($data['gender'] ?? '')));
        if ($gender !== '' && !isset(self::GENDERS[$gender])) {
            $gender = '';
        }

        $nullable = static function ($value): ?string {
            $value = trim((string) $value);

            return $value !== '' ? $value : null;
        };

        $date = static function ($value): ?string {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }
            $ts = strtotime($value);

            return $ts ? date('Y-m-d', $ts) : null;
        };

        $email = $nullable($data['email'] ?? null);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        return [
            'name' => $name,
            'role_title' => $nullable($data['role_title'] ?? null),
            'department' => $nullable($data['department'] ?? null),
            'phone' => $nullable($data['phone'] ?? null),
            'email' => $email,
            'status' => $status,
            'notes' => $nullable($data['notes'] ?? null),
            'bio' => $nullable($data['bio'] ?? null),
            'staff_code' => $nullable($data['staff_code'] ?? null),
            'gender' => $gender !== '' ? $gender : null,
            'date_of_birth' => $date($data['date_of_birth'] ?? null),
            'national_id' => $nullable($data['national_id'] ?? null),
            'marital_status' => $nullable($data['marital_status'] ?? null),
            'address' => $nullable($data['address'] ?? null),
            'city' => $nullable($data['city'] ?? null),
            'campus' => $nullable($data['campus'] ?? null),
            'employment_type' => $employment,
            'hire_date' => $date($data['hire_date'] ?? null),
            'end_date' => $date($data['end_date'] ?? null),
            'reports_to' => $nullable($data['reports_to'] ?? null),
            'secondary_phone' => $nullable($data['secondary_phone'] ?? null),
            'emergency_contact_name' => $nullable($data['emergency_contact_name'] ?? null),
            'emergency_contact_phone' => $nullable($data['emergency_contact_phone'] ?? null),
            'emergency_contact_relation' => $nullable($data['emergency_contact_relation'] ?? null),
            'skills' => $nullable($data['skills'] ?? null),
            'ministries' => $nullable($data['ministries'] ?? null),
            'education' => $nullable($data['education'] ?? null),
            'languages' => $nullable($data['languages'] ?? null),
            'work_schedule' => $nullable($data['work_schedule'] ?? null),
            'office_location' => $nullable($data['office_location'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $fields
     * @return list<mixed>
     */
    private static function fieldValues(array $fields): array
    {
        return [
            $fields['name'],
            $fields['role_title'],
            $fields['department'],
            $fields['phone'],
            $fields['email'],
            $fields['status'],
            $fields['notes'],
            $fields['bio'],
            $fields['staff_code'],
            $fields['gender'],
            $fields['date_of_birth'],
            $fields['national_id'],
            $fields['marital_status'],
            $fields['address'],
            $fields['city'],
            $fields['campus'],
            $fields['employment_type'],
            $fields['hire_date'],
            $fields['end_date'],
            $fields['reports_to'],
            $fields['secondary_phone'],
            $fields['emergency_contact_name'],
            $fields['emergency_contact_phone'],
            $fields['emergency_contact_relation'],
            $fields['skills'],
            $fields['ministries'],
            $fields['education'],
            $fields['languages'],
            $fields['work_schedule'],
            $fields['office_location'],
        ];
    }

    private static function setPhotoPath(int $staffId, ?string $path): void
    {
        Database::connection()
            ->prepare('UPDATE staff_members SET photo_path = ? WHERE id = ?')
            ->execute([$path, $staffId]);
    }

    /** @param array<string, mixed> $file */
    private static function storeUploadedImage(int $staffId, array $file): string
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

        $dir = dirname(__DIR__, 2) . '/public/uploads/staff/' . $staffId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the staff photo folder.');
        }
        @chmod($dir, 0755);

        $filename = 'photo-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Failed to save photo.');
        }
        @chmod($dest, 0644);

        return 'uploads/staff/' . $staffId . '/' . $filename;
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
