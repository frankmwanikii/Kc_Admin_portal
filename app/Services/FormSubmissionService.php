<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class FormSubmissionService
{
    private static bool $tableChecked = false;

    /** Connect With Us forms from Kc_website — all appear in admin Members. */
    public const MEMBER_FORM_TYPES = ['join', 'new-beginning', 'new-here', 'kingdom-groups', 'manual'];

    /** @return array<string, string> */
    public static function formTypeLabels(): array
    {
        return [
            'join' => 'Join Our Church Family',
            'new-beginning' => 'New Beginning',
            'new-here' => 'New Here',
            'kingdom-groups' => 'Kingdom Groups',
            'manual' => 'Added manually',
        ];
    }

    public static function formTypeLabel(string $formType): string
    {
        $formType = strtolower(trim($formType));

        return self::formTypeLabels()[$formType] ?? ucwords(str_replace('-', ' ', $formType));
    }

    /**
     * @return array{
     *   configured: bool,
     *   database: string,
     *   connected: bool,
     *   total_submissions: int,
     *   member_submissions: int,
     *   warning: ?string,
     *   error: ?string
     * }
     */
    public static function formsDatabaseStatus(): array
    {
        $configured = trim($_ENV['FORMS_DB_DATABASE_NAME'] ?? '') !== '';
        $database = $configured
            ? trim($_ENV['FORMS_DB_DATABASE_NAME'])
            : trim($_ENV['DB_DATABASE_NAME'] ?? '');

        try {
            self::ensureTable();
            $total = (int) self::db()->query('SELECT COUNT(*) FROM form_submissions')->fetchColumn();
            $placeholders = implode(',', array_fill(0, count(self::MEMBER_FORM_TYPES), '?'));
            $stmt = self::db()->prepare("SELECT COUNT(*) FROM form_submissions WHERE form_type IN ($placeholders)");
            $stmt->execute(self::MEMBER_FORM_TYPES);
            $members = (int) $stmt->fetchColumn();

            $warning = null;
            if (!$configured) {
                $warning = 'The shared forms database is not configured (FORMS_DB_DATABASE_NAME in .env). '
                    . 'The portal may be reading a different database than the website. '
                    . 'Set it to match Kc_website includes/database-config.php (e.g. kingdomcity_forms).';
            }

            return [
                'configured' => $configured,
                'database' => $database,
                'connected' => true,
                'total_submissions' => $total,
                'member_submissions' => $members,
                'warning' => $warning,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'configured' => $configured,
                'database' => $database,
                'connected' => false,
                'total_submissions' => 0,
                'member_submissions' => 0,
                'warning' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function ensureTable(): void
    {
        if (self::$tableChecked || ($_ENV['APP_INSTALLED'] ?? 'false') !== 'true') {
            return;
        }

        $sql = file_get_contents(dirname(__DIR__, 2) . '/database/shared-form-submissions.sql');
        if ($sql) {
            self::db()->exec($sql);
        }
        self::$tableChecked = true;
    }

    private static function db(): \PDO
    {
        return Database::formsConnection();
    }

    private static bool $financeTablesReady = false;

    public static function ensureFinanceTables(): void
    {
        if (self::$financeTablesReady) {
            return;
        }
        if (($_ENV['APP_INSTALLED'] ?? 'false') !== 'true') {
            return;
        }
        $sql = file_get_contents(dirname(__DIR__, 2) . '/database/finance-reconciliation.sql');
        if ($sql) {
            Database::connection()->exec($sql);
        }
        $catalogSql = file_get_contents(dirname(__DIR__, 2) . '/database/finance-expense-catalog.sql');
        if ($catalogSql) {
            Database::connection()->exec($catalogSql);
        }
        self::$financeTablesReady = true;
    }

    /** @param array<string, mixed> $data */
    public static function record(string $formType, array $data, array $context = []): int
    {
        self::ensureTable();

        $formType = strtolower(trim($formType));
        unset($data['_hp']);

        $payload = $data;
        $payload['form_type'] = $formType;

        $stmt = self::db()->prepare('
            INSERT INTO form_submissions
                (form_type, campus_id, submitter_name, submitter_email, submitter_phone, payload, ip_address, user_agent, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $formType,
            self::normalizeCampus((string) ($data['campus'] ?? 'nanyuki')),
            self::extractName($data),
            self::extractEmail($data),
            self::extractPhone($data),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            self::truncate((string) ($context['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? ''), 45) ?: null,
            self::truncate((string) ($context['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? ''), 512) ?: null,
            'new',
        ]);

        return (int) self::db()->lastInsertId();
    }

    /**
     * Create a member registration from the admin UI.
     *
     * @param array{name?: string, email?: string, phone?: string, campus?: string, notes?: string, form_type?: string} $data
     */
    public static function createManual(array $data): int
    {
        $formType = strtolower(trim((string) ($data['form_type'] ?? 'manual')));
        if (!in_array($formType, self::MEMBER_FORM_TYPES, true)) {
            $formType = 'manual';
        }

        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));
        $campus = self::normalizeCampus((string) ($data['campus'] ?? 'nanyuki'));

        $payload = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'campus' => $campus,
            'campus_label' => ucfirst($campus),
            'form_title' => 'Added manually',
            'source' => 'admin_manual',
        ];
        if ($notes !== '') {
            $payload['admin_notes'] = $notes;
        }

        $id = self::record($formType, $payload, [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => 'admin-manual',
        ]);

        self::updateStatus($id, 'reviewed', $notes !== '' ? $notes : null);

        return $id;
    }

    /** @return list<array<string, mixed>> */
    public static function membersList(): array
    {
        self::ensureTable();
        $placeholders = implode(',', array_fill(0, count(self::MEMBER_FORM_TYPES), '?'));
        $stmt = self::db()->prepare("
            SELECT id, form_type, campus_id, submitter_name, submitter_email, submitter_phone,
                   status, created_at
            FROM form_submissions
            WHERE form_type IN ($placeholders)
            ORDER BY created_at DESC
        ");
        $stmt->execute(self::MEMBER_FORM_TYPES);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

  /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        self::ensureTable();
        $stmt = self::db()->prepare('SELECT * FROM form_submissions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['payload'] = json_decode($row['payload'], true) ?: [];

        return $row;
    }

    public static function updateStatus(int $id, string $status, ?string $notes = null): void
    {
        self::ensureTable();
        $stmt = self::db()->prepare('
            UPDATE form_submissions SET status = ?, portal_notes = COALESCE(?, portal_notes), updated_at = NOW() WHERE id = ?
        ');
        $stmt->execute([$status, $notes, $id]);
    }

    public static function delete(int $id): bool
    {
        self::ensureTable();
        $placeholders = implode(',', array_fill(0, count(self::MEMBER_FORM_TYPES), '?'));
        $stmt = self::db()->prepare("DELETE FROM form_submissions WHERE id = ? AND form_type IN ($placeholders)");
        $stmt->execute(array_merge([$id], self::MEMBER_FORM_TYPES));

        return $stmt->rowCount() > 0;
    }

    public static function countByStatus(string $status = 'new'): int
    {
        self::ensureTable();
        $placeholders = implode(',', array_fill(0, count(self::MEMBER_FORM_TYPES), '?'));
        $stmt = self::db()->prepare("SELECT COUNT(*) FROM form_submissions WHERE status = ? AND form_type IN ($placeholders)");
        $stmt->execute(array_merge([$status], self::MEMBER_FORM_TYPES));

        return (int) $stmt->fetchColumn();
    }

    /** @return array{labels: list<string>, counts: list<int>} */
    public static function registrationsTrend(int $months = 6): array
    {
        self::ensureTable();
        $labels = [];
        $counts = [];
        $placeholders = implode(',', array_fill(0, count(self::MEMBER_FORM_TYPES), '?'));

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = new \DateTime("first day of -{$i} months");
            $yearMonth = $date->format('Y-m');
            $labels[] = $date->format('M Y');

            $stmt = self::db()->prepare("
                SELECT COUNT(*) FROM form_submissions
                WHERE form_type IN ($placeholders)
                  AND DATE_FORMAT(created_at, '%Y-%m') = ?
            ");
            $stmt->execute(array_merge(self::MEMBER_FORM_TYPES, [$yearMonth]));
            $counts[] = (int) $stmt->fetchColumn();
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    /** @return array<string, int> */
    public static function statusBreakdown(): array
    {
        self::ensureTable();
        $placeholders = implode(',', array_fill(0, count(self::MEMBER_FORM_TYPES), '?'));
        $stmt = self::db()->prepare("
            SELECT status, COUNT(*) AS cnt FROM form_submissions
            WHERE form_type IN ($placeholders)
            GROUP BY status
        ");
        $stmt->execute(self::MEMBER_FORM_TYPES);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(string) $row['status']] = (int) $row['cnt'];
        }

        return $out;
    }

    /** @param array<string, mixed> $data */
    private static function extractName(array $data): ?string
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name !== '') {
            return self::truncate($name, 255);
        }
        $full = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        return $full !== '' ? self::truncate($full, 255) : null;
    }

    /** @param array<string, mixed> $data */
    private static function extractEmail(array $data): ?string
    {
        $email = trim((string) ($data['email'] ?? ''));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? self::truncate($email, 255) : null;
    }

    /** @param array<string, mixed> $data */
    private static function extractPhone(array $data): ?string
    {
        $phone = trim((string) ($data['phone'] ?? ''));

        return $phone !== '' ? self::truncate($phone, 64) : null;
    }

    private static function normalizeCampus(string $campus): string
    {
        $campus = strtolower(trim($campus));

        return in_array($campus, ['nanyuki', 'nairobi'], true) ? $campus : 'nanyuki';
    }

    private static function truncate(string $value, int $max): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
    }

    /** Human labels for join form payload keys */
    public static function fieldLabel(string $key): string
    {
        static $labels = [
            'campus' => 'Campus',
            'name' => 'Full name',
            'phone' => 'Phone',
            'email' => 'Email',
            'date_of_birth' => 'Date of birth',
            'gender' => 'Gender',
            'marital_status' => 'Marital status',
            'address' => 'Address',
            'attending_duration' => 'Time attending church',
            'spouse_name' => 'Spouse / partner name',
            'spouse_phone' => 'Spouse phone',
            'spouse_email' => 'Spouse email',
            'spouse_attends' => 'Spouse attends church',
            'children_details' => 'Children (names & ages)',
            'children_attend' => 'Children attend programmes',
            'dependents_details' => 'Other dependents',
            'household_size' => 'Household size',
            'born_again' => 'Given life to Christ',
            'water_baptised' => 'Water baptised',
            'other_church_details' => 'Previous church',
            'faith_story' => 'Faith testimony',
            'emergency_name' => 'Emergency contact name',
            'emergency_phone' => 'Emergency contact phone',
            'emergency_relationship' => 'Emergency contact relationship',
            'kingdom_group_interest' => 'Kingdom Group interest',
            'ministry_serve' => 'Ministries to serve',
            'ministry_serve[]' => 'Ministries to serve',
            'occupation' => 'Occupation',
            'gifts_skills' => 'Skills & gifts',
            'commit_member' => 'Membership commitment',
            'commit_attendance' => 'Commitment to attendance',
            'commit_leadership' => 'Submit to leadership',
            'first_time' => 'First time here',
            'decision' => 'Decision made today',
            'age_range' => 'Age range',
            'experience' => 'Experience with us',
            'speak_to_pastor' => 'Wants to speak to a pastor',
            'ministry_interest' => 'Ministry interest',
            'signup' => 'Sign up for',
            'heard_about' => 'How they heard about us',
            'has_spouse' => 'Has spouse / partner',
            'has_children' => 'Has children',
            'has_dependents' => 'Has other dependents',
            'other_church_member' => 'Member of another church',
            'campus_label' => 'Campus',
            'form_title' => 'Form',
        ];

        return $labels[$key] ?? ucwords(str_replace(['_', '[]'], [' ', ''], $key));
    }

    /**
     * Join form fields grouped for admin profile — matches Kc_website join modal.
     *
     * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
     */
    public static function joinProfileSections(array $payload): array
    {
        $sections = [
            'Church & Contact' => [
                'campus', 'date_of_birth', 'gender', 'marital_status',
                'address', 'attending_duration',
            ],
            'Family & Household' => [
                'has_spouse', 'spouse_name', 'spouse_phone', 'spouse_email', 'spouse_attends',
                'has_children', 'children_details', 'children_attend',
                'has_dependents', 'dependents_details', 'household_size',
            ],
            'Faith Background' => [
                'born_again', 'water_baptised', 'other_church_member', 'other_church_details', 'faith_story',
            ],
            'Emergency Contact' => [
                'emergency_name', 'emergency_phone', 'emergency_relationship',
            ],
            'Serve & Connect' => [
                'kingdom_group_interest', 'ministry_serve', 'occupation', 'gifts_skills',
            ],
            'Membership Commitment' => [
                'commit_member',
            ],
        ];

        $skip = ['form_type', '_hp', 'name', 'email', 'phone', 'form_title', 'campus_label'];
        $used = [];
        $out = [];

        foreach ($sections as $title => $keys) {
            $rows = [];
            foreach ($keys as $key) {
                if ($key === 'campus') {
                    $campusId = $payload['campus'] ?? null;
                    $campusLabel = $payload['campus_label'] ?? null;
                    if (($campusId === '' || $campusId === null) && ($campusLabel === '' || $campusLabel === null)) {
                        continue;
                    }
                    $rows[] = [
                        'label' => self::fieldLabel('campus'),
                        'value' => $campusLabel !== '' && $campusLabel !== null
                            ? self::formatValue($campusLabel, 'campus_label')
                            : self::formatValue($campusId, 'campus'),
                    ];
                    $used['campus'] = true;
                    $used['campus_label'] = true;
                    continue;
                }
                if (!array_key_exists($key, $payload)) {
                    continue;
                }
                $value = $payload[$key];
                if ($value === '' || $value === null || $value === []) {
                    continue;
                }
                $rows[] = [
                    'label' => self::fieldLabel($key),
                    'value' => self::formatValue($value, $key),
                ];
                $used[$key] = true;
            }
            if ($rows !== []) {
                $out[] = ['title' => $title, 'rows' => $rows];
            }
        }

        $extra = [];
        foreach ($payload as $key => $value) {
            if (isset($used[$key]) || in_array($key, $skip, true) || $value === '' || $value === null || $value === []) {
                continue;
            }
            $extra[] = [
                'label' => self::fieldLabel((string) $key),
                'value' => self::formatValue($value, (string) $key),
            ];
        }
        if ($extra !== []) {
            $out[] = ['title' => 'Other details', 'rows' => $extra];
        }

        return $out;
    }

    public static function formatValue(mixed $value, ?string $key = null): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn ($v) => self::humanize((string) $v, $key), $value));
        }
        if ($value === null || $value === '') {
            return '—';
        }

        return self::humanize((string) $value, $key);
    }

    private static function humanize(string $value, ?string $key = null): string
    {
        static $map = [
            'yes' => 'Yes',
            'no' => 'No',
            'male' => 'Male',
            'female' => 'Female',
            'single' => 'Single',
            'married' => 'Married',
            'divorced' => 'Divorced',
            'widowed' => 'Widowed',
            'separated' => 'Separated',
            'planning' => 'Would like to be baptised',
            'first-time' => 'First time / new',
            'under-3-months' => 'Less than 3 months',
            '3-6-months' => '3 – 6 months',
            '6-12-months' => '6 – 12 months',
            'over-1-year' => 'Over 1 year',
            'already' => 'Already in a Kingdom Group',
            'not-yet' => 'Not yet, but interested',
        ];

        if ($key === 'campus') {
            $campus = strtolower($value);

            return match ($campus) {
                'nanyuki' => 'Kingdomcity Church Nanyuki',
                'nairobi' => 'Kingdomcity Church Nairobi',
                default => ucfirst($campus),
            };
        }

        if ($key === 'kingdom_group_interest') {
            return match ($value) {
                'yes' => 'Yes, please connect me',
                'already' => 'Already in a Kingdom Group',
                'not-yet' => 'Not yet, but interested',
                'no' => 'Not at this time',
                default => $map[$value] ?? str_replace('-', ' ', $value),
            };
        }

        if ($key === 'ministry_serve' || $key === 'ministry_serve[]') {
            return str_replace(['-', '_'], ' ', $value);
        }

        return $map[$value] ?? str_replace('_', ' ', $value);
    }
}
