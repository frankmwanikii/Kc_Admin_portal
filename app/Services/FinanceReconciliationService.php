<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class FinanceReconciliationService
{
    private const SCHEMA_VERSION = 1;

    private static bool $schemaReady = false;

    /** @var array<string, array{label: string, hint: string, department: string}>|null */
    private static ?array $weeklyCategoriesCache = null;

    /** @var list<array<string, mixed>>|null */
    private static ?array $expenseCatalogCache = null;

    /** @var array<string, int>|null */
    private static ?array $departmentIdMapCache = null;

    /** @var array{by_week: array<string, array{expenses: float, collections: float, balance: float}>, by_month: array<string, array{expenses: float, collections: float, balance: float}>}|null */
    private static ?array $yearActivityCache = null;

    private static ?int $yearActivityCacheYear = null;

    /** @var array<string, array{label: string, hint: string, department: string}> */
    public const WEEKLY_CATEGORIES = [
        'keyboardist' => ['label' => 'Keyboardist', 'hint' => 'Sunday allowance', 'department' => 'worship_services'],
        'drummer' => ['label' => 'Drummer', 'hint' => 'Sunday allowance', 'department' => 'worship_services'],
        'bassist' => ['label' => 'Bassist', 'hint' => 'Sunday allowance', 'department' => 'worship_services'],
        'kids_teacher' => ['label' => 'Kids Teacher', 'hint' => 'Sunday allowance', 'department' => 'k_kids'],
        'caretaker' => ['label' => 'Caretaker', 'hint' => 'Sunday allowance', 'department' => 'wages'],
        'honorarium_gifts' => ['label' => 'Honorarium & Gifts', 'hint' => '', 'department' => 'honorarium_gifts'],
        'kplc_tokens' => ['label' => 'KPLC Tokens', 'hint' => 'Weekly usage', 'department' => 'administration'],
    ];

    /** @var array<string, array{label: string, desc: string}> */
    public const PAYMENT_METHODS = [
        'paybill' => ['label' => 'M-Pesa Paybill', 'desc' => 'Paybill 176287'],
        'cheque' => ['label' => 'Cheque', 'desc' => 'Bank cheque payments'],
        'cash' => ['label' => 'Cash', 'desc' => 'Cash & envelopes'],
    ];

    /** @var list<string> */
    public const FUND_TYPES = ['Tithe', 'Offering', 'Projects', 'Shop', 'Other'];

    /** @var array<string, string> */
    public const EXPENSE_GROUPS = [
        'admin_expenses' => 'Admin Expenses',
        'ministry_departments' => 'Ministry & Departments',
    ];

    /** @var list<array{slug: string, label: string, code_prefix: string, sort: int, group: string}> */
    private const EXPENSE_DEPARTMENT_SEED = [
        ['slug' => 'administration', 'label' => 'Administration', 'code_prefix' => '001/2', 'sort' => 10, 'group' => 'admin_expenses'],
        ['slug' => 'k_kids', 'label' => 'K.Kids', 'code_prefix' => '001/1', 'sort' => 10, 'group' => 'ministry_departments'],
        ['slug' => 'worship_services', 'label' => 'Worship & Services', 'code_prefix' => '001/3', 'sort' => 20, 'group' => 'ministry_departments'],
        ['slug' => 'discipleship_programmes', 'label' => 'Discipleship Programes', 'code_prefix' => '001/4', 'sort' => 30, 'group' => 'ministry_departments'],
        ['slug' => 'production_sound_lighting', 'label' => 'Production, Sound & Lighting', 'code_prefix' => '001/5', 'sort' => 40, 'group' => 'ministry_departments'],
        ['slug' => 'wages', 'label' => 'Wages', 'code_prefix' => '001/6', 'sort' => 50, 'group' => 'ministry_departments'],
        ['slug' => 'training_development', 'label' => 'Training & Development', 'code_prefix' => '001/7', 'sort' => 60, 'group' => 'ministry_departments'],
        ['slug' => 'pastoral_allowances_salaries', 'label' => 'Pastoral Allowances/ Salaries', 'code_prefix' => '001/8', 'sort' => 70, 'group' => 'ministry_departments'],
        ['slug' => 'pastoral_care', 'label' => 'Pastoral Care', 'code_prefix' => '001/9', 'sort' => 80, 'group' => 'ministry_departments'],
        ['slug' => 'missions_outreach', 'label' => 'Missions & Outreach', 'code_prefix' => '001/10', 'sort' => 90, 'group' => 'ministry_departments'],
        ['slug' => 'gpm_remittances', 'label' => 'GPM Remittances', 'code_prefix' => '001/11', 'sort' => 100, 'group' => 'ministry_departments'],
        ['slug' => 'honorarium_gifts', 'label' => 'Honorarium & Gifts', 'code_prefix' => '001/12', 'sort' => 110, 'group' => 'ministry_departments'],
        ['slug' => 'benevolent', 'label' => 'Benovelent', 'code_prefix' => '001/13', 'sort' => 120, 'group' => 'ministry_departments'],
    ];

    /** @var list<array{label: string, code: string}> */
    private const ADMINISTRATION_CATEGORY_SEED = [
        ['label' => 'Rent', 'code' => '001/2/001'],
        ['label' => 'Water', 'code' => '001/2/002'],
        ['label' => 'Electricity', 'code' => '001/2/003'],
        ['label' => 'Transport', 'code' => '001/2/004'],
        ['label' => 'Insurance', 'code' => '001/2/005'],
        ['label' => 'Security', 'code' => '001/2/006'],
        ['label' => 'Communication/Telephone', 'code' => '001/2/007'],
        ['label' => 'Stationery', 'code' => '001/2/008'],
        ['label' => 'Refreshments', 'code' => '001/2/009'],
        ['label' => 'Health & Safety (Fumigation/F.Extinguishers)', 'code' => '001/2/010'],
        ['label' => 'Hospitality', 'code' => '001/2/011'],
        ['label' => 'Detergents & Toiletries', 'code' => '001/2/012'],
        ['label' => 'Consultancy Fee', 'code' => '001/2/013'],
        ['label' => 'Repair & Maintenance', 'code' => '001/2/014'],
        ['label' => 'Licences', 'code' => '001/2/015'],
        ['label' => 'Audit Fees', 'code' => '001/2/016'],
    ];

    public static function ensureTables(): void
    {
        if (self::$schemaReady) {
            return;
        }

        if (($_ENV['APP_INSTALLED'] ?? 'false') !== 'true') {
            return;
        }

        SettingsService::ensureTable();
        $storedVersion = (int) (SettingsService::get('finance_schema_version') ?? '0');
        if ($storedVersion < self::SCHEMA_VERSION) {
            FormSubmissionService::ensureFinanceTables();
            self::seedExpenseCatalogIfEmpty();
            self::migrateExpenseDepartmentGroups();
            self::ensureAdministrationCategories();
            self::ensureMinistryExpenseItems();
            self::seedWeeklyCategoriesIfEmpty();
            self::migrateWeeklyCategoryDepartments();
            SettingsService::set('finance_schema_version', (string) self::SCHEMA_VERSION);
        }

        self::$schemaReady = true;
    }

    private static function clearRuntimeCaches(): void
    {
        self::$weeklyCategoriesCache = null;
        self::$expenseCatalogCache = null;
        self::$departmentIdMapCache = null;
        self::$yearActivityCache = null;
        self::$yearActivityCacheYear = null;
    }

    /** @return array<string, int> */
    private static function departmentIdMap(): array
    {
        if (self::$departmentIdMapCache !== null) {
            return self::$departmentIdMapCache;
        }

        $db = Database::connection();
        $map = [];
        try {
            foreach ($db->query('SELECT id, slug FROM finance_expense_departments') as $row) {
                $map[$row['slug']] = (int) $row['id'];
            }
        } catch (\Throwable) {
            // departments table may not exist yet
        }

        self::$departmentIdMapCache = $map;

        return $map;
    }

    private static function migrateWeeklyCategoryDepartments(): void
    {
        $db = Database::connection();
        try {
            $hasColumn = (bool) $db->query("SHOW COLUMNS FROM finance_weekly_categories LIKE 'department_id'")->fetch();
            if (!$hasColumn) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $deptMap = self::departmentIdMap();
        if ($deptMap === []) {
            return;
        }

        $update = $db->prepare('
            UPDATE finance_weekly_categories SET department_id = ? WHERE slug = ? AND department_id IS NULL
        ');
        foreach (self::WEEKLY_CATEGORIES as $slug => $meta) {
            $deptSlug = $meta['department'] ?? '';
            if ($deptSlug === '' || !isset($deptMap[$deptSlug])) {
                continue;
            }
            $update->execute([$deptMap[$deptSlug], $slug]);
        }

        self::ensureSystemWeeklyCategories();
    }

    private static function ensureSystemWeeklyCategories(): void
    {
        $db = Database::connection();
        try {
            $hasColumn = (bool) $db->query("SHOW COLUMNS FROM finance_weekly_categories LIKE 'department_id'")->fetch();
            if (!$hasColumn) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $deptMap = self::departmentIdMap();
        $maxOrder = (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) FROM finance_weekly_categories')->fetchColumn();
        $insert = $db->prepare('
            INSERT INTO finance_weekly_categories (slug, label, hint, department_id, is_system, sort_order)
            VALUES (?, ?, ?, ?, 1, ?)
        ');

        foreach (self::WEEKLY_CATEGORIES as $slug => $meta) {
            $stmt = $db->prepare('SELECT id FROM finance_weekly_categories WHERE slug = ?');
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                continue;
            }
            $deptId = $deptMap[$meta['department'] ?? ''] ?? null;
            $maxOrder += 10;
            $insert->execute([$slug, $meta['label'], $meta['hint'], $deptId, $maxOrder]);
        }
    }

    private static function seedExpenseCatalogIfEmpty(): void
    {
        $db = Database::connection();
        try {
            $count = (int) $db->query('SELECT COUNT(*) FROM finance_expense_departments')->fetchColumn();
        } catch (\Throwable) {
            return;
        }
        if ($count > 0) {
            return;
        }

        $deptIds = [];
        $insertDept = $db->prepare('
            INSERT INTO finance_expense_departments (slug, label, code_prefix, expense_group, sort_order, is_system)
            VALUES (?, ?, ?, ?, ?, 1)
        ');
        foreach (self::EXPENSE_DEPARTMENT_SEED as $dept) {
            $insertDept->execute([
                $dept['slug'],
                $dept['label'],
                $dept['code_prefix'],
                $dept['group'],
                $dept['sort'],
            ]);
            $deptIds[$dept['slug']] = (int) $db->lastInsertId();
        }

        $insertCat = $db->prepare('
            INSERT INTO finance_expense_categories (department_id, slug, label, account_code, sort_order, is_system)
            VALUES (?, ?, ?, ?, ?, 1)
        ');

        $order = 10;
        foreach (self::ADMINISTRATION_CATEGORY_SEED as $cat) {
            $slug = self::uniqueCategorySlug($db, self::slugify($cat['label']));
            $insertCat->execute([$deptIds['administration'], $slug, $cat['label'], $cat['code'], $order]);
            $order += 10;
        }

        foreach (self::EXPENSE_DEPARTMENT_SEED as $dept) {
            if ($dept['slug'] === 'administration') {
                continue;
            }
            $slug = self::uniqueCategorySlug($db, self::slugify($dept['label']));
            $insertCat->execute([
                $deptIds[$dept['slug']],
                $slug,
                $dept['label'],
                $dept['code_prefix'] . '/001',
                10,
            ]);
        }
    }

    private static function uniqueCategorySlug(PDO $db, string $base): string
    {
        $slug = $base;
        $n = 1;
        while (true) {
            $stmt = $db->prepare('SELECT id FROM finance_expense_categories WHERE slug = ?');
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) {
                return $slug;
            }
            $slug = $base . '_' . $n++;
        }
    }

    private static function migrateExpenseDepartmentGroups(): void
    {
        $db = Database::connection();
        try {
            $hasColumn = (bool) $db->query("SHOW COLUMNS FROM finance_expense_departments LIKE 'expense_group'")->fetch();
            if (!$hasColumn) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $update = $db->prepare('
            UPDATE finance_expense_departments SET expense_group = ? WHERE slug = ?
        ');
        foreach (self::EXPENSE_DEPARTMENT_SEED as $dept) {
            $update->execute([$dept['group'], $dept['slug']]);
        }
    }

    private static function ensureAdministrationCategories(): void
    {
        $db = Database::connection();
        try {
            $db->query('SELECT 1 FROM finance_expense_departments LIMIT 1');
        } catch (\Throwable) {
            return;
        }

        $deptMap = self::departmentIdMap();
        $adminId = $deptMap['administration'] ?? null;
        if (!$adminId) {
            return;
        }

        $insert = $db->prepare('
            INSERT INTO finance_expense_categories (department_id, slug, label, account_code, sort_order, is_system)
            VALUES (?, ?, ?, ?, ?, 1)
        ');
        $order = 10;
        foreach (self::ADMINISTRATION_CATEGORY_SEED as $cat) {
            $stmt = $db->prepare('SELECT id FROM finance_expense_categories WHERE account_code = ?');
            $stmt->execute([$cat['code']]);
            if ($stmt->fetch()) {
                $order += 10;
                continue;
            }
            $slug = self::uniqueCategorySlug($db, self::slugify($cat['label']));
            $insert->execute([$adminId, $slug, $cat['label'], $cat['code'], $order]);
            $order += 10;
        }
    }

    private static function ensureMinistryExpenseItems(): void
    {
        $db = Database::connection();
        try {
            $db->query('SELECT 1 FROM finance_expense_categories LIMIT 1');
        } catch (\Throwable) {
            return;
        }

        $deptMap = self::departmentIdMap();
        $insert = $db->prepare('
            INSERT INTO finance_expense_categories (department_id, slug, label, account_code, sort_order, is_system)
            VALUES (?, ?, ?, ?, ?, 1)
        ');
        $prefixStmt = $db->prepare('SELECT code_prefix FROM finance_expense_departments WHERE id = ?');

        foreach (self::WEEKLY_CATEGORIES as $meta) {
            $deptSlug = $meta['department'] ?? '';
            $deptId = $deptMap[$deptSlug] ?? null;
            if (!$deptId) {
                continue;
            }

            $exists = $db->prepare('SELECT id FROM finance_expense_categories WHERE department_id = ? AND label = ?');
            $exists->execute([$deptId, $meta['label']]);
            if ($exists->fetch()) {
                continue;
            }

            $prefixStmt->execute([$deptId]);
            $prefix = (string) $prefixStmt->fetchColumn();
            $accountCode = self::nextAccountCodeForDepartment($deptId, $prefix);
            $slug = self::uniqueCategorySlug($db, self::slugify($meta['label']));
            $orderStmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM finance_expense_categories WHERE department_id = ?');
            $orderStmt->execute([$deptId]);
            $maxOrder = (int) $orderStmt->fetchColumn();
            $insert->execute([$deptId, $slug, $meta['label'], $accountCode, $maxOrder + 10]);
        }
    }

    public static function expenseGroupLabel(string $slug): string
    {
        return self::EXPENSE_GROUPS[$slug] ?? $slug;
    }

    /** @return list<array<string, mixed>> */
    public static function allExpenseCatalog(): array
    {
        if (self::$expenseCatalogCache !== null) {
            return self::$expenseCatalogCache;
        }

        self::ensureTables();
        $db = Database::connection();
        $depts = $db->query('
            SELECT id, slug, label, code_prefix, expense_group
            FROM finance_expense_departments
            ORDER BY
                CASE expense_group WHEN \'admin_expenses\' THEN 1 WHEN \'ministry_departments\' THEN 2 ELSE 9 END,
                sort_order ASC,
                id ASC
        ')->fetchAll(PDO::FETCH_ASSOC);
        $cats = $db->query('
            SELECT id, department_id, slug, label, account_code, sort_order
            FROM finance_expense_categories
            ORDER BY sort_order ASC, id ASC
        ')->fetchAll(PDO::FETCH_ASSOC);

        $byDept = [];
        foreach ($cats as $cat) {
            $deptId = (int) $cat['department_id'];
            $byDept[$deptId][] = [
                'id' => (int) $cat['id'],
                'slug' => $cat['slug'],
                'label' => $cat['label'],
                'account_code' => $cat['account_code'],
            ];
        }

        $grouped = [];
        foreach (self::EXPENSE_GROUPS as $groupSlug => $groupLabel) {
            $grouped[$groupSlug] = [
                'slug' => $groupSlug,
                'label' => $groupLabel,
                'departments' => [],
            ];
        }

        foreach ($depts as $dept) {
            $groupSlug = (string) ($dept['expense_group'] ?? 'ministry_departments');
            if (!isset($grouped[$groupSlug])) {
                $groupSlug = 'ministry_departments';
            }
            $id = (int) $dept['id'];
            $grouped[$groupSlug]['departments'][] = [
                'id' => $id,
                'slug' => $dept['slug'],
                'label' => $dept['label'],
                'code_prefix' => $dept['code_prefix'],
                'expense_group' => $groupSlug,
                'categories' => $byDept[$id] ?? [],
            ];
        }

        return self::$expenseCatalogCache = array_values($grouped);
    }

    /** @return array<string, mixed>|null */
    public static function findExpenseCategory(int $id): ?array
    {
        self::ensureTables();
        $stmt = Database::connection()->prepare('
            SELECT c.*, d.label AS department_label, d.code_prefix, d.expense_group
            FROM finance_expense_categories c
            INNER JOIN finance_expense_departments d ON d.id = c.department_id
            WHERE c.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function addExpenseCategory(int $departmentId, string $label): int
    {
        self::ensureTables();
        $label = trim($label);
        if ($label === '') {
            throw new \InvalidArgumentException('Category name is required.');
        }
        if ($departmentId <= 0) {
            throw new \InvalidArgumentException('Department is required.');
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT id, code_prefix FROM finance_expense_departments WHERE id = ?');
        $stmt->execute([$departmentId]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dept) {
            throw new \InvalidArgumentException('Department not found.');
        }

        $accountCode = self::nextAccountCodeForDepartment($departmentId, (string) $dept['code_prefix']);
        $slug = self::uniqueCategorySlug($db, self::slugify($label));
        $orderStmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM finance_expense_categories WHERE department_id = ?');
        $orderStmt->execute([$departmentId]);
        $maxOrder = (int) $orderStmt->fetchColumn();

        $insert = $db->prepare('
            INSERT INTO finance_expense_categories (department_id, slug, label, account_code, sort_order, is_system)
            VALUES (?, ?, ?, ?, ?, 0)
        ');
        $insert->execute([$departmentId, $slug, $label, $accountCode, $maxOrder + 10]);

        self::clearRuntimeCaches();

        return (int) $db->lastInsertId();
    }

    private static function nextAccountCodeForDepartment(int $departmentId, string $codePrefix): string
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT account_code FROM finance_expense_categories
            WHERE department_id = ?
            ORDER BY account_code DESC
            LIMIT 1
        ');
        $stmt->execute([$departmentId]);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $next = 1;
        if ($last !== '' && preg_match('/(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $codePrefix . '/' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /** @param array<string, mixed> $cat */
    public static function formatExpenseItemLabel(array $cat): string
    {
        $code = trim((string) ($cat['account_code'] ?? ''));
        $label = trim((string) ($cat['label'] ?? ''));
        if ($code !== '' && $label !== '') {
            return $code . ' — ' . $label;
        }

        return $label !== '' ? $label : $code;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{0: int, 1: string}
     */
    public static function resolveArrearCategory(array $data): array
    {
        $departmentId = (int) ($data['department_id'] ?? 0);
        $categoryId = (int) ($data['category_id'] ?? 0);
        $newLabel = trim((string) ($data['new_category_label'] ?? ''));

        if ($departmentId <= 0) {
            throw new \InvalidArgumentException('Select a department for this expense.');
        }

        if ($categoryId > 0) {
            $cat = self::findExpenseCategory($categoryId);
            if (!$cat) {
                throw new \InvalidArgumentException('Expense category not found.');
            }
            if ((int) $cat['department_id'] !== $departmentId) {
                throw new \InvalidArgumentException('Category does not belong to the selected department.');
            }

            return [$categoryId, self::formatExpenseItemLabel($cat)];
        }

        if ($newLabel !== '') {
            $newId = self::addExpenseCategory($departmentId, $newLabel);
            $cat = self::findExpenseCategory($newId);
            if (!$cat) {
                throw new \InvalidArgumentException('Could not create expense category.');
            }

            return [$newId, self::formatExpenseItemLabel($cat)];
        }

        throw new \InvalidArgumentException('Select an expense category or enter a new one.');
    }

    /** @return array{0: float, 1: float} */
    public static function normalizeArrearAmounts(float $due, float $paid): array
    {
        $due = max(0, round($due, 2));
        $paid = max(0, round($paid, 2));
        if ($paid > $due) {
            throw new \InvalidArgumentException('Amount paid cannot exceed amount due.');
        }

        return [$due, $paid];
    }

    private static function seedWeeklyCategoriesIfEmpty(): void
    {
        $db = Database::connection();
        try {
            $count = (int) $db->query('SELECT COUNT(*) FROM finance_weekly_categories')->fetchColumn();
        } catch (\Throwable) {
            return;
        }
        if ($count > 0) {
            return;
        }

        $deptMap = self::departmentIdMap();
        $order = 10;
        foreach (self::WEEKLY_CATEGORIES as $slug => $meta) {
            $deptId = $deptMap[$meta['department'] ?? ''] ?? null;
            $stmt = $db->prepare('
                INSERT INTO finance_weekly_categories (slug, label, hint, department_id, is_system, sort_order)
                VALUES (?, ?, ?, ?, 1, ?)
            ');
            $stmt->execute([$slug, $meta['label'], $meta['hint'], $deptId, $order]);
            $order += 10;
        }
    }

    /** @return array<string, array{label: string, hint: string, is_system: bool, id: int, department_id: int|null, department_label: string}> */
    public static function allWeeklyCategories(): array
    {
        if (self::$weeklyCategoriesCache !== null) {
            return self::$weeklyCategoriesCache;
        }

        self::ensureTables();
        $stmt = Database::connection()->query('
            SELECT c.id, c.slug, c.label, c.hint, c.is_system, c.department_id, c.expense_category_id,
                   d.label AS department_label, d.expense_group,
                   ec.label AS admin_line_label
            FROM finance_weekly_categories c
            LEFT JOIN finance_expense_departments d ON d.id = c.department_id
            LEFT JOIN finance_expense_categories ec ON ec.id = c.expense_category_id
            ORDER BY
                CASE d.expense_group WHEN \'admin_expenses\' THEN 1 WHEN \'ministry_departments\' THEN 2 ELSE 9 END,
                COALESCE(ec.sort_order, d.sort_order, 9999) ASC,
                c.sort_order ASC,
                c.id ASC
        ');
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $groupSlug = (string) ($row['expense_group'] ?? '');
            $out[$row['slug']] = [
                'id' => (int) $row['id'],
                'label' => $row['label'],
                'hint' => $row['hint'] ?? '',
                'is_system' => (int) $row['is_system'] === 1,
                'department_id' => isset($row['department_id']) ? (int) $row['department_id'] : null,
                'department_label' => (string) ($row['department_label'] ?? ''),
                'expense_group' => $groupSlug,
                'group_label' => $groupSlug !== '' ? self::expenseGroupLabel($groupSlug) : '',
                'expense_category_id' => isset($row['expense_category_id']) ? (int) $row['expense_category_id'] : null,
                'admin_line_label' => (string) ($row['admin_line_label'] ?? ''),
            ];
        }

        return self::$weeklyCategoriesCache = $out;
    }

    public static function addWeeklyCategory(
        string $label,
        string $hint = '',
        int $departmentId = 0,
        ?int $expenseCategoryId = null
    ): string {
        self::ensureTables();
        $label = trim($label);
        if ($label === '') {
            throw new \InvalidArgumentException('Category name is required.');
        }
        if ($departmentId <= 0) {
            throw new \InvalidArgumentException('Department is required.');
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM finance_expense_departments WHERE id = ?');
        $stmt->execute([$departmentId]);
        if (!$stmt->fetch()) {
            throw new \InvalidArgumentException('Department not found.');
        }

        if ($expenseCategoryId !== null && $expenseCategoryId > 0) {
            $catStmt = $db->prepare('SELECT id FROM finance_expense_categories WHERE id = ?');
            $catStmt->execute([$expenseCategoryId]);
            if (!$catStmt->fetch()) {
                throw new \InvalidArgumentException('Admin expense category not found.');
            }
        } else {
            $expenseCategoryId = null;
        }

        $base = self::slugify($label);
        $slug = $base;
        $n = 1;
        while (true) {
            $stmt = $db->prepare('SELECT id FROM finance_weekly_categories WHERE slug = ?');
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) {
                break;
            }
            $slug = $base . '_' . $n++;
        }
        $maxOrder = (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) FROM finance_weekly_categories')->fetchColumn();
        $stmt = $db->prepare('
            INSERT INTO finance_weekly_categories (slug, label, hint, department_id, expense_category_id, is_system, sort_order)
            VALUES (?, ?, ?, ?, ?, 0, ?)
        ');
        $stmt->execute([$slug, $label, trim($hint), $departmentId, $expenseCategoryId, $maxOrder + 10]);

        self::clearRuntimeCaches();

        return $slug;
    }

    public static function updateWeeklyCategory(
        string $slug,
        string $label,
        string $hint = '',
        int $departmentId = 0,
        ?int $expenseCategoryId = null
    ): bool {
        self::ensureTables();
        $label = trim($label);
        if ($label === '') {
            throw new \InvalidArgumentException('Category name is required.');
        }
        if ($departmentId <= 0) {
            throw new \InvalidArgumentException('Department is required.');
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM finance_weekly_categories WHERE slug = ?');
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            return false;
        }
        $deptStmt = $db->prepare('SELECT id FROM finance_expense_departments WHERE id = ?');
        $deptStmt->execute([$departmentId]);
        if (!$deptStmt->fetch()) {
            throw new \InvalidArgumentException('Department not found.');
        }

        if ($expenseCategoryId !== null && $expenseCategoryId > 0) {
            $catStmt = $db->prepare('SELECT id FROM finance_expense_categories WHERE id = ?');
            $catStmt->execute([$expenseCategoryId]);
            if (!$catStmt->fetch()) {
                throw new \InvalidArgumentException('Admin expense category not found.');
            }
        } else {
            $expenseCategoryId = null;
        }

        $hint = trim($hint);
        $db->prepare('
            UPDATE finance_weekly_categories
            SET label = ?, hint = ?, department_id = ?, expense_category_id = ?, updated_at = NOW()
            WHERE slug = ?
        ')->execute([$label, $hint, $departmentId, $expenseCategoryId, $slug]);
        $db->prepare('
            UPDATE finance_weekly_expenses SET category_label = ?, updated_at = NOW() WHERE category_slug = ?
        ')->execute([$label, $slug]);

        self::clearRuntimeCaches();

        return true;
    }

    public static function deleteWeeklyCategory(string $slug): bool
    {
        self::ensureTables();
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM finance_weekly_categories WHERE slug = ?');
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            return false;
        }
        $db->prepare('DELETE FROM finance_weekly_expenses WHERE category_slug = ?')->execute([$slug]);
        $db->prepare('DELETE FROM finance_weekly_categories WHERE slug = ?')->execute([$slug]);

        self::clearRuntimeCaches();

        return true;
    }

    /** @return array<string, float> */
    public static function weeklyAmountsForDate(string $weekDate): array
    {
        self::ensureTables();
        $stmt = Database::connection()->prepare('
            SELECT category_slug, amount FROM finance_weekly_expenses WHERE week_date = ?
        ');
        $stmt->execute([$weekDate]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['category_slug']] = (float) $row['amount'];
        }

        return $out;
    }

    private static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '_', $text) ?? '';
        $text = trim($text, '_');

        return $text !== '' ? substr($text, 0, 45) : 'category';
    }

    public static function balanceOwing(float $due, float $paid): float
    {
        return max(0, round($due - $paid, 2));
    }

    public static function paymentStatus(float $due, float $paid): string
    {
        $balance = self::balanceOwing($due, $paid);
        if ($balance <= 0 && $paid > 0) {
            return 'PAID';
        }
        if ($paid > 0 && $balance > 0) {
            return 'PARTIAL';
        }

        return 'UNPAID';
    }

    /** @return list<array<string, mixed>> */
    public static function arrearsList(int $year): array
    {
        self::ensureTables();
        $stmt = Database::connection()->prepare('
            SELECT a.*,
                   c.label AS category_label,
                   c.account_code,
                   c.department_id,
                   d.label AS department_label,
                   d.expense_group
            FROM finance_expense_arrears a
            LEFT JOIN finance_expense_categories c ON a.category_id = c.id
            LEFT JOIN finance_expense_departments d ON c.department_id = d.id
            WHERE a.budget_year = ?
            ORDER BY
                CASE d.expense_group WHEN \'admin_expenses\' THEN 1 WHEN \'ministry_departments\' THEN 2 ELSE 9 END,
                COALESCE(d.sort_order, 9999) ASC,
                COALESCE(c.sort_order, 9999) ASC,
                a.id ASC
        ');
        $stmt->execute([$year]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([self::class, 'enrichArrear'], $rows);
    }

    /** @param array<string, mixed> $row */
    public static function enrichArrear(array $row): array
    {
        $due = (float) $row['amount_due'];
        $paid = (float) $row['amount_paid'];
        $row['balance_owing'] = self::balanceOwing($due, $paid);
        $row['payment_status'] = self::paymentStatus($due, $paid);
        $row['department_id'] = isset($row['department_id']) ? (int) $row['department_id'] : null;
        $row['category_id'] = isset($row['category_id']) ? (int) $row['category_id'] : null;
        $row['department_label'] = (string) ($row['department_label'] ?? '');
        $row['category_label'] = (string) ($row['category_label'] ?? '');
        $row['account_code'] = (string) ($row['account_code'] ?? '');
        $groupSlug = (string) ($row['expense_group'] ?? '');
        $row['expense_group'] = $groupSlug;
        $row['group_label'] = $groupSlug !== '' ? self::expenseGroupLabel($groupSlug) : '';

        return $row;
    }

    /** @param array<string, mixed> $data */
    public static function saveArrear(array $data, ?int $id = null): void
    {
        self::ensureTables();
        [$categoryId, $expenseItemFromCategory] = self::resolveArrearCategory($data);
        $expenseItem = trim((string) ($data['expense_item'] ?? ''));
        if ($expenseItem === '') {
            $expenseItem = $expenseItemFromCategory;
        }
        $due = (float) ($data['amount_due'] ?? 0);
        $paid = (float) ($data['amount_paid'] ?? 0);
        [$due, $paid] = self::normalizeArrearAmounts($due, $paid);

        if ($id) {
            $stmt = Database::connection()->prepare('
                UPDATE finance_expense_arrears SET
                    category_id = ?, expense_item = ?, month_incurred = ?, amount_due = ?, amount_paid = ?,
                    date_paid = ?, paid_by_ref = ?, notes = ?, budget_year = ?, updated_at = NOW()
                WHERE id = ?
            ');
            $stmt->execute([
                $categoryId,
                $expenseItem,
                $data['month_incurred'],
                $due,
                $paid,
                ($data['date_paid'] ?? '') ?: null,
                ($data['paid_by_ref'] ?? '') ?: null,
                ($data['notes'] ?? '') ?: null,
                (int) ($data['budget_year'] ?? date('Y')),
                $id,
            ]);

            self::clearRuntimeCaches();

            return;
        }

        $stmt = Database::connection()->prepare('
            INSERT INTO finance_expense_arrears
                (category_id, expense_item, month_incurred, amount_due, amount_paid, date_paid, paid_by_ref, notes, budget_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $categoryId,
            $expenseItem,
            $data['month_incurred'],
            $due,
            $paid,
            ($data['date_paid'] ?? '') ?: null,
            ($data['paid_by_ref'] ?? '') ?: null,
            ($data['notes'] ?? '') ?: null,
            (int) ($data['budget_year'] ?? date('Y')),
        ]);

        self::clearRuntimeCaches();
    }

    public static function deleteArrear(int $id): void
    {
        self::ensureTables();
        Database::connection()->prepare('DELETE FROM finance_expense_arrears WHERE id = ?')->execute([$id]);
    }

    /** Sundays in a given month (Y-m) */
    public static function sundaysInMonth(string $yearMonth): array
    {
        [$year, $month] = array_map('intval', explode('-', $yearMonth));
        $days = [];
        $date = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $date->modify('last day of this month');

        while ($date <= $end) {
            if ($date->format('w') === '0') {
                $days[] = $date->format('Y-m-d');
            }
            $date = $date->modify('+1 day');
        }

        return $days;
    }

    /**
     * Weekly grid: categories × Sundays for a month.
     *
     * @return array{sundays: list<string>, rows: list<array>, week_totals: array<string, float>, month_total: float}
     */
    public static function weeklyGrid(string $yearMonth): array
    {
        self::ensureTables();
        $sundays = self::sundaysInMonth($yearMonth);
        $amounts = [];

        if ($sundays) {
            $placeholders = implode(',', array_fill(0, count($sundays), '?'));
            $stmt = Database::connection()->prepare("
                SELECT week_date, category_slug, amount
                FROM finance_weekly_expenses
                WHERE week_date IN ($placeholders)
            ");
            $stmt->execute($sundays);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $amounts[$r['category_slug']][$r['week_date']] = (float) $r['amount'];
            }
        }

        $weekTotals = array_fill_keys($sundays, 0.0);
        $rows = [];
        $monthTotal = 0.0;
        $categories = self::allWeeklyCategories();

        foreach ($categories as $slug => $meta) {
            $row = [
                'slug' => $slug,
                'label' => $meta['label'],
                'hint' => $meta['hint'],
                'is_system' => $meta['is_system'],
                'department_id' => $meta['department_id'],
                'department_label' => $meta['department_label'],
                'expense_group' => $meta['expense_group'] ?? '',
                'group_label' => $meta['group_label'] ?? '',
                'amounts' => [],
                'total' => 0.0,
            ];
            foreach ($sundays as $sun) {
                $amt = $amounts[$slug][$sun] ?? 0.0;
                $row['amounts'][$sun] = $amt;
                $row['total'] += $amt;
                $weekTotals[$sun] += $amt;
            }
            $monthTotal += $row['total'];
            $rows[] = $row;
        }

        return [
            'sundays' => $sundays,
            'rows' => $rows,
            'week_totals' => $weekTotals,
            'month_total' => $monthTotal,
        ];
    }

    /** @param array<string, float|string> $categoryAmounts slug => amount */
    public static function saveWeeklyEntry(string $weekDate, array $categoryAmounts): void
    {
        self::ensureTables();
        $db = Database::connection();
        $categories = self::allWeeklyCategories();

        foreach ($categories as $slug => $meta) {
            $amount = (float) ($categoryAmounts[$slug] ?? 0);
            if ($amount <= 0) {
                $db->prepare('DELETE FROM finance_weekly_expenses WHERE week_date = ? AND category_slug = ?')
                    ->execute([$weekDate, $slug]);
                continue;
            }
            $stmt = $db->prepare('
                INSERT INTO finance_weekly_expenses (week_date, category_slug, category_label, amount)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE amount = VALUES(amount), category_label = VALUES(category_label), updated_at = NOW()
            ');
            $stmt->execute([$weekDate, $slug, $meta['label'], $amount]);
        }

        self::clearRuntimeCaches();
    }

    /** @return array<string, float> payment_method => amount */
    public static function weeklyCollectionAmountsForDate(string $weekDate): array
    {
        self::ensureTables();
        $stmt = Database::connection()->prepare('
            SELECT payment_method, amount FROM finance_weekly_collections WHERE week_date = ?
        ');
        $stmt->execute([$weekDate]);
        $out = array_fill_keys(array_keys(self::PAYMENT_METHODS), 0.0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['payment_method']] = (float) $row['amount'];
        }

        return $out;
    }

    /**
     * Weekly collections grid: payment methods × Sundays for a month.
     *
     * @return array{sundays: list<string>, rows: list<array>, week_totals: array<string, float>, month_total: float, method_totals: array<string, float>}
     */
    public static function weeklyCollectionsGrid(string $yearMonth): array
    {
        self::ensureTables();
        $sundays = self::sundaysInMonth($yearMonth);
        $amounts = [];

        if ($sundays) {
            $placeholders = implode(',', array_fill(0, count($sundays), '?'));
            $stmt = Database::connection()->prepare("
                SELECT week_date, payment_method, amount
                FROM finance_weekly_collections
                WHERE week_date IN ($placeholders)
            ");
            $stmt->execute($sundays);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $amounts[$r['payment_method']][$r['week_date']] = (float) $r['amount'];
            }
        }

        $weekTotals = array_fill_keys($sundays, 0.0);
        $methodTotals = array_fill_keys(array_keys(self::PAYMENT_METHODS), 0.0);
        $rows = [];
        $monthTotal = 0.0;

        foreach (self::PAYMENT_METHODS as $method => $meta) {
            $row = [
                'method' => $method,
                'label' => $meta['label'],
                'desc' => $meta['desc'],
                'amounts' => [],
                'total' => 0.0,
            ];
            foreach ($sundays as $sun) {
                $amt = $amounts[$method][$sun] ?? 0.0;
                $row['amounts'][$sun] = $amt;
                $row['total'] += $amt;
                $weekTotals[$sun] += $amt;
                $methodTotals[$method] += $amt;
            }
            $monthTotal += $row['total'];
            $rows[] = $row;
        }

        return [
            'sundays' => $sundays,
            'rows' => $rows,
            'week_totals' => $weekTotals,
            'method_totals' => $methodTotals,
            'month_total' => $monthTotal,
        ];
    }

    /**
     * Save one payment method's amounts across Sundays in a month.
     *
     * @param array<string, float|string> $amountsByDate week_date => amount
     */
    public static function saveCollectionMethodMonth(string $method, string $yearMonth, array $amountsByDate): void
    {
        self::ensureTables();
        if (!isset(self::PAYMENT_METHODS[$method])) {
            throw new \InvalidArgumentException('Unknown payment method.');
        }

        $sundays = self::sundaysInMonth($yearMonth);
        $db = Database::connection();

        foreach ($sundays as $sun) {
            $amount = (float) ($amountsByDate[$sun] ?? 0);
            if ($amount <= 0) {
                $db->prepare('DELETE FROM finance_weekly_collections WHERE week_date = ? AND payment_method = ?')
                    ->execute([$sun, $method]);
                continue;
            }
            $stmt = $db->prepare('
                INSERT INTO finance_weekly_collections (week_date, payment_method, amount)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE amount = VALUES(amount), updated_at = NOW()
            ');
            $stmt->execute([$sun, $method, $amount]);
        }

        self::clearRuntimeCaches();
    }

    /** Clear all amounts for one payment method in a month. */
    public static function clearCollectionMethodMonth(string $method, string $yearMonth): void
    {
        self::ensureTables();
        if (!isset(self::PAYMENT_METHODS[$method])) {
            throw new \InvalidArgumentException('Unknown payment method.');
        }

        $sundays = self::sundaysInMonth($yearMonth);
        if ($sundays === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($sundays), '?'));
        $params = array_merge([$method], $sundays);
        Database::connection()
            ->prepare("DELETE FROM finance_weekly_collections WHERE payment_method = ? AND week_date IN ($placeholders)")
            ->execute($params);

        self::clearRuntimeCaches();
    }

    /** @param array<string, float|string> $methodAmounts payment_method => amount */
    public static function saveWeeklyCollectionEntry(string $weekDate, array $methodAmounts): void
    {
        self::ensureTables();
        $db = Database::connection();

        foreach (self::PAYMENT_METHODS as $method => $_meta) {
            $amount = (float) ($methodAmounts[$method] ?? 0);
            if ($amount <= 0) {
                $db->prepare('DELETE FROM finance_weekly_collections WHERE week_date = ? AND payment_method = ?')
                    ->execute([$weekDate, $method]);
                continue;
            }
            $stmt = $db->prepare('
                INSERT INTO finance_weekly_collections (week_date, payment_method, amount)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE amount = VALUES(amount), updated_at = NOW()
            ');
            $stmt->execute([$weekDate, $method, $amount]);
        }

        self::clearRuntimeCaches();
    }

    /**
     * Aggregated weekly collections and expenses for a calendar year (2 queries, cached per request).
     *
     * @return array{
     *   by_week: array<string, array{expenses: float, collections: float, balance: float}>,
     *   by_month: array<string, array{expenses: float, collections: float, balance: float}>
     * }
     */
    private static function yearActivitySummary(int $year): array
    {
        if (self::$yearActivityCache !== null && self::$yearActivityCacheYear === $year) {
            return self::$yearActivityCache;
        }

        self::ensureTables();
        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-01-01', $year + 1);
        $db = Database::connection();

        $expStmt = $db->prepare('
            SELECT week_date, SUM(amount) AS total
            FROM finance_weekly_expenses
            WHERE week_date >= ? AND week_date < ?
            GROUP BY week_date
        ');
        $expStmt->execute([$start, $end]);
        $expByWeek = [];
        foreach ($expStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $expByWeek[(string) $row['week_date']] = (float) $row['total'];
        }

        $colStmt = $db->prepare('
            SELECT week_date, SUM(amount) AS total
            FROM finance_weekly_collections
            WHERE week_date >= ? AND week_date < ?
            GROUP BY week_date
        ');
        $colStmt->execute([$start, $end]);
        $colByWeek = [];
        foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $colByWeek[(string) $row['week_date']] = (float) $row['total'];
        }

        $byWeek = [];
        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $yearMonth = sprintf('%04d-%02d', $year, $m);
            $byMonth[$yearMonth] = ['expenses' => 0.0, 'collections' => 0.0, 'balance' => 0.0];
        }

        $allWeekDates = array_unique(array_merge(array_keys($expByWeek), array_keys($colByWeek)));
        sort($allWeekDates);

        foreach ($allWeekDates as $weekDate) {
            $exp = round($expByWeek[$weekDate] ?? 0.0, 2);
            $col = round($colByWeek[$weekDate] ?? 0.0, 2);
            $bal = round($col - $exp, 2);
            $byWeek[$weekDate] = [
                'expenses' => $exp,
                'collections' => $col,
                'balance' => $bal,
            ];

            $yearMonth = substr($weekDate, 0, 7);
            if (!isset($byMonth[$yearMonth])) {
                continue;
            }
            $byMonth[$yearMonth]['expenses'] = round($byMonth[$yearMonth]['expenses'] + $exp, 2);
            $byMonth[$yearMonth]['collections'] = round($byMonth[$yearMonth]['collections'] + $col, 2);
            $byMonth[$yearMonth]['balance'] = round($byMonth[$yearMonth]['collections'] - $byMonth[$yearMonth]['expenses'], 2);
        }

        self::$yearActivityCache = [
            'by_week' => $byWeek,
            'by_month' => $byMonth,
        ];
        self::$yearActivityCacheYear = $year;

        return self::$yearActivityCache;
    }

    /**
     * Collections vs expenses per week and month — for reconciliation statements.
     *
     * @return array{
     *   weeks: list<array{week_date: string, expenses: float, collections: float, balance: float}>,
     *   month_expenses: float,
     *   month_collections: float,
     *   month_balance: float
     * }
     */
    public static function monthReconciliation(string $yearMonth): array
    {
        $year = (int) substr($yearMonth, 0, 4);
        $activity = self::yearActivitySummary($year);
        $sundays = self::sundaysInMonth($yearMonth);
        $weeks = [];

        foreach ($sundays as $sun) {
            $row = $activity['by_week'][$sun] ?? ['expenses' => 0.0, 'collections' => 0.0, 'balance' => 0.0];
            $weeks[] = [
                'week_date' => $sun,
                'expenses' => $row['expenses'],
                'collections' => $row['collections'],
                'balance' => $row['balance'],
            ];
        }

        $monthRow = $activity['by_month'][$yearMonth] ?? ['expenses' => 0.0, 'collections' => 0.0, 'balance' => 0.0];

        return [
            'weeks' => $weeks,
            'month_expenses' => $monthRow['expenses'],
            'month_collections' => $monthRow['collections'],
            'month_balance' => $monthRow['balance'],
        ];
    }

    /**
     * Year-to-date reconciliation across all months in a budget year.
     *
     * @return array{
     *   months: list<array{month: string, label: string, expenses: float, collections: float, balance: float}>,
     *   year_expenses: float,
     *   year_collections: float,
     *   year_balance: float
     * }
     */
    public static function yearReconciliation(int $year): array
    {
        $activity = self::yearActivitySummary($year);
        $months = [];
        $yearExpenses = 0.0;
        $yearCollections = 0.0;

        for ($m = 1; $m <= 12; $m++) {
            $yearMonth = sprintf('%04d-%02d', $year, $m);
            $summary = $activity['by_month'][$yearMonth];
            if ($summary['expenses'] <= 0 && $summary['collections'] <= 0) {
                continue;
            }
            $months[] = [
                'month' => $yearMonth,
                'label' => date('F Y', strtotime($yearMonth . '-01')),
                'expenses' => $summary['expenses'],
                'collections' => $summary['collections'],
                'balance' => $summary['balance'],
            ];
            $yearExpenses += $summary['expenses'];
            $yearCollections += $summary['collections'];
        }

        return [
            'months' => $months,
            'year_expenses' => $yearExpenses,
            'year_collections' => $yearCollections,
            'year_balance' => round($yearCollections - $yearExpenses, 2),
        ];
    }

  /**
     * Formal financial statement — weekly, monthly, or annual (Jan–Dec).
     *
     * @return array<string, mixed>
     */
    public static function buildStatement(string $view, int $year, ?string $month = null, ?string $weekDate = null): array
    {
        self::ensureTables();
        $view = in_array($view, ['weekly', 'monthly', 'annual'], true) ? $view : 'monthly';

        if ($view === 'annual') {
            return self::enrichStatementWithArrears(self::buildAnnualStatement($year));
        }

        $yearMonth = $month ?? sprintf('%04d-%02d', $year, (int) date('m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            $yearMonth = sprintf('%04d-%02d', $year, (int) date('m'));
        }

        if ($view === 'weekly') {
            return self::enrichStatementWithArrears(self::buildWeeklyStatement($yearMonth, $weekDate ?? ''));
        }

        return self::enrichStatementWithArrears(self::buildMonthlyStatement($yearMonth));
    }

    /** @return array<string, mixed> */
    private static function buildWeeklyStatement(string $yearMonth, string $weekDate): array
    {
        $sundays = self::sundaysInMonth($yearMonth);
        if ($weekDate === '' && $sundays !== []) {
            $weekDate = $sundays[0];
        }
        if ($weekDate !== '' && !in_array($weekDate, $sundays, true)) {
            $weekDate = $sundays[0] ?? '';
        }

        $sunIndex = 0;
        foreach ($sundays as $i => $sun) {
            if ($sun === $weekDate) {
                $sunIndex = $i + 1;
                break;
            }
        }

        $collectionAmounts = $weekDate !== '' ? self::weeklyCollectionAmountsForDate($weekDate) : [];
        $expenseAmounts = $weekDate !== '' ? self::weeklyAmountsForDate($weekDate) : [];
        $categories = self::allWeeklyCategories();

        $collectionLines = [];
        $collectionsTotal = 0.0;
        foreach (self::PAYMENT_METHODS as $method => $meta) {
            $amt = round((float) ($collectionAmounts[$method] ?? 0), 2);
            $collectionLines[] = ['label' => $meta['label'], 'amount' => $amt];
            $collectionsTotal += $amt;
        }
        $collectionsTotal = round($collectionsTotal, 2);

        $expenseLines = [];
        $expensesTotal = 0.0;
        foreach ($categories as $slug => $meta) {
            $amt = round((float) ($expenseAmounts[$slug] ?? 0), 2);
            $expenseLines[] = ['label' => $meta['label'], 'amount' => $amt];
            $expensesTotal += $amt;
        }
        $expensesTotal = round($expensesTotal, 2);

        $summary = self::statementSummary($collectionsTotal, $expensesTotal);
        $weekLabel = $weekDate !== ''
            ? date('l, j F Y', strtotime($weekDate))
            : 'No Sunday selected';

        return [
            'view' => 'weekly',
            'period_label' => $weekDate !== ''
                ? 'Week ending ' . date('j M Y', strtotime($weekDate)) . ($sunIndex > 0 ? ' (Sun ' . $sunIndex . ')' : '')
                : date('F Y', strtotime($yearMonth . '-01')),
            'period_subtitle' => $weekLabel,
            'period_start' => $weekDate,
            'period_end' => $weekDate,
            'year' => (int) substr($yearMonth, 0, 4),
            'month' => $yearMonth,
            'week_date' => $weekDate,
            'sun_index' => $sunIndex,
            'sundays' => $sundays,
            'summary' => $summary,
            'collection_lines' => $collectionLines,
            'expense_lines' => $expenseLines,
            'activity_rows' => [],
            'activity_heading' => '',
            'narrative' => self::statementNarrative($summary),
        ];
    }

    /** @return array<string, mixed> */
    private static function buildMonthlyStatement(string $yearMonth): array
    {
        $reconciliation = self::monthReconciliation($yearMonth);
        $expenses = self::weeklyGrid($yearMonth);
        $collections = self::weeklyCollectionsGrid($yearMonth);

        $collectionLines = [];
        foreach ($collections['rows'] as $row) {
            $collectionLines[] = [
                'label' => $row['label'],
                'amount' => round((float) $row['total'], 2),
            ];
        }

        $expenseLines = [];
        foreach ($expenses['rows'] as $row) {
            $expenseLines[] = [
                'label' => $row['label'],
                'amount' => round((float) $row['total'], 2),
            ];
        }

        $activityRows = [];
        foreach ($reconciliation['weeks'] as $i => $week) {
            $activityRows[] = [
                'label' => date('j M Y', strtotime($week['week_date'])),
                'sub_label' => 'Sun ' . ($i + 1),
                'collections' => round((float) $week['collections'], 2),
                'expenses' => round((float) $week['expenses'], 2),
                'balance' => round((float) $week['balance'], 2),
            ];
        }

        $summary = self::statementSummary(
            round((float) $reconciliation['month_collections'], 2),
            round((float) $reconciliation['month_expenses'], 2)
        );

        return [
            'view' => 'monthly',
            'period_label' => date('F Y', strtotime($yearMonth . '-01')),
            'period_subtitle' => 'Monthly statement',
            'period_start' => $yearMonth . '-01',
            'period_end' => date('Y-m-t', strtotime($yearMonth . '-01')),
            'year' => (int) substr($yearMonth, 0, 4),
            'month' => $yearMonth,
            'week_date' => '',
            'sun_index' => 0,
            'sundays' => $expenses['sundays'],
            'summary' => $summary,
            'collection_lines' => $collectionLines,
            'expense_lines' => $expenseLines,
            'activity_rows' => $activityRows,
            'activity_heading' => 'Weekly activity',
            'narrative' => self::statementNarrative($summary),
        ];
    }

    /** @return array<string, mixed> */
    private static function buildAnnualStatement(int $year): array
    {
        $activityRows = [];
        $collectionsTotal = 0.0;
        $expensesTotal = 0.0;

        for ($m = 1; $m <= 12; $m++) {
            $yearMonth = sprintf('%04d-%02d', $year, $m);
            $monthRec = self::monthReconciliation($yearMonth);
            $col = round((float) $monthRec['month_collections'], 2);
            $exp = round((float) $monthRec['month_expenses'], 2);
            $bal = round((float) $monthRec['month_balance'], 2);
            $activityRows[] = [
                'label' => date('F', strtotime($yearMonth . '-01')),
                'sub_label' => date('Y', strtotime($yearMonth . '-01')),
                'collections' => $col,
                'expenses' => $exp,
                'balance' => $bal,
            ];
            $collectionsTotal += $col;
            $expensesTotal += $exp;
        }

        $collectionsTotal = round($collectionsTotal, 2);
        $expensesTotal = round($expensesTotal, 2);
        $summary = self::statementSummary($collectionsTotal, $expensesTotal);

        $collectionLines = [];
        foreach (self::PAYMENT_METHODS as $method => $meta) {
            $methodTotal = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $grid = self::weeklyCollectionsGrid(sprintf('%04d-%02d', $year, $m));
                $methodTotal += (float) ($grid['method_totals'][$method] ?? 0);
            }
            $collectionLines[] = [
                'label' => $meta['label'],
                'amount' => round($methodTotal, 2),
            ];
        }

        $categories = self::allWeeklyCategories();
        $expenseLines = [];
        foreach ($categories as $slug => $meta) {
            $catTotal = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $grid = self::weeklyGrid(sprintf('%04d-%02d', $year, $m));
                foreach ($grid['rows'] as $row) {
                    if ($row['slug'] === $slug) {
                        $catTotal += (float) $row['total'];
                    }
                }
            }
            $expenseLines[] = [
                'label' => $meta['label'],
                'amount' => round($catTotal, 2),
            ];
        }

        return [
            'view' => 'annual',
            'period_label' => 'Annual statement — ' . $year,
            'period_subtitle' => 'January to December ' . $year,
            'period_start' => $year . '-01-01',
            'period_end' => $year . '-12-31',
            'year' => $year,
            'month' => '',
            'week_date' => '',
            'sun_index' => 0,
            'sundays' => [],
            'summary' => $summary,
            'collection_lines' => $collectionLines,
            'expense_lines' => $expenseLines,
            'activity_rows' => $activityRows,
            'activity_heading' => 'Monthly summary',
            'narrative' => self::statementNarrative($summary),
        ];
    }

    /** @param array<string, mixed> $statement */
    private static function enrichStatementWithArrears(array $statement): array
    {
        $year = (int) ($statement['year'] ?? date('Y'));
        $arrears = self::arrearsList($year);

        $totalDue = 0.0;
        $totalPaid = 0.0;
        $balanceOwing = 0.0;
        $lines = [];

        foreach ($arrears as $row) {
            $due = round((float) ($row['amount_due'] ?? 0), 2);
            $paid = round((float) ($row['amount_paid'] ?? 0), 2);
            $owing = round((float) ($row['balance_owing'] ?? 0), 2);
            $lines[] = [
                'expense_item' => (string) ($row['expense_item'] ?? ''),
                'department_label' => (string) ($row['department_label'] ?? ''),
                'group_label' => (string) ($row['group_label'] ?? ''),
                'category_label' => (string) ($row['category_label'] ?? ''),
                'account_code' => (string) ($row['account_code'] ?? ''),
                'month_incurred' => (string) ($row['month_incurred'] ?? ''),
                'amount_due' => $due,
                'amount_paid' => $paid,
                'balance_owing' => $owing,
                'payment_status' => (string) ($row['payment_status'] ?? 'UNPAID'),
                'status_label' => self::arrearStatusLabel((string) ($row['payment_status'] ?? 'UNPAID')),
            ];
            $totalDue += $due;
            $totalPaid += $paid;
            $balanceOwing += $owing;
        }

        $arrearsSummary = [
            'budget_year' => $year,
            'total_due' => round($totalDue, 2),
            'total_paid' => round($totalPaid, 2),
            'balance_owing' => round($balanceOwing, 2),
            'count' => count($lines),
        ];

        $operatingBalance = round((float) ($statement['summary']['balance'] ?? 0), 2);
        $netPosition = round($operatingBalance - $arrearsSummary['balance_owing'], 2);
        $netStatus = $netPosition > 0 ? 'surplus' : ($netPosition < 0 ? 'deficit' : 'balanced');

        $statement['arrears'] = $arrearsSummary;
        $statement['arrears_lines'] = $lines;
        $statement['true_picture'] = [
            'operating_balance' => $operatingBalance,
            'arrears_owing' => $arrearsSummary['balance_owing'],
            'net_position' => $netPosition,
            'status' => $netStatus,
            'status_label' => match ($netStatus) {
                'surplus' => 'Net surplus',
                'deficit' => 'Net deficit',
                default => 'Balanced net position',
            },
        ];
        $statement['true_picture_narrative'] = self::truePictureNarrative(
            $statement['summary'] ?? [],
            $arrearsSummary,
            $statement['true_picture']
        );

        return $statement;
    }

    private static function arrearStatusLabel(string $status): string
    {
        return match ($status) {
            'PAID' => 'Paid',
            'PARTIAL' => 'Partially paid',
            default => 'Not paid',
        };
    }

    /**
     * @param array{collections?: float, expenses?: float, balance?: float, status?: string, status_label?: string} $summary
     * @param array{total_due: float, total_paid: float, balance_owing: float, count: int} $arrears
     * @param array{operating_balance: float, arrears_owing: float, net_position: float, status: string, status_label: string} $truePicture
     */
    private static function truePictureNarrative(array $summary, array $arrears, array $truePicture): string
    {
        $operating = number_format(abs((float) ($truePicture['operating_balance'] ?? 0)), 0);
        $owing = number_format((float) ($arrears['balance_owing'] ?? 0), 0);
        $net = number_format(abs((float) ($truePicture['net_position'] ?? 0)), 0);
        $operatingLabel = ($summary['status_label'] ?? 'Operating balance') . ' of KES ' . $operating;

        if ((float) ($arrears['balance_owing'] ?? 0) <= 0) {
            return match ($summary['status'] ?? 'balanced') {
                'surplus' => "This period shows an operating surplus of KES {$operating} with no outstanding bills recorded for the budget year.",
                'deficit' => "This period shows an operating deficit of KES {$operating}. No outstanding bills are recorded for the budget year.",
                default => 'Collections matched weekly expenses for this period, with no outstanding bills recorded for the budget year.',
            };
        }

        $base = match ($summary['status'] ?? 'balanced') {
            'surplus' => "Operating surplus of KES {$operating}",
            'deficit' => "Operating deficit of KES {$operating}",
            default => 'A balanced operating position',
        };

        return "{$base}, combined with KES {$owing} still owing on outstanding bills, gives a {$truePicture['status_label']} of KES {$net}.";
    }

    /** @return array{collections: float, expenses: float, balance: float, status: string, status_label: string} */
    private static function statementSummary(float $collections, float $expenses): array
    {
        $collections = round($collections, 2);
        $expenses = round($expenses, 2);
        $balance = round($collections - $expenses, 2);
        $status = $balance > 0 ? 'surplus' : ($balance < 0 ? 'deficit' : 'balanced');

        return [
            'collections' => $collections,
            'expenses' => $expenses,
            'balance' => $balance,
            'status' => $status,
            'status_label' => match ($status) {
                'surplus' => 'Operating surplus',
                'deficit' => 'Operating deficit',
                default => 'Balanced position',
            },
        ];
    }

    /** @param array{collections: float, expenses: float, balance: float, status: string, status_label: string} $summary */
    private static function statementNarrative(array $summary): string
    {
        $col = number_format($summary['collections'], 0);
        $exp = number_format($summary['expenses'], 0);
        $bal = number_format(abs($summary['balance']), 0);

        return match ($summary['status']) {
            'surplus' => "Total collections of KES {$col} exceeded expenses of KES {$exp}, resulting in an operating surplus of KES {$bal} for this period.",
            'deficit' => "Expenses of KES {$exp} exceeded collections of KES {$col}, resulting in an operating deficit of KES {$bal} for this period.",
            default => "Collections and expenses were equal at KES {$col} for this period.",
        };
    }

    public const STATEMENT_DISCLAIMER = 'This statement is generated electronically and is not considered official unless it bears an authorised signature and official stamp.';

    public static function statementLogoPath(): string
    {
        $root = dirname(__DIR__, 2);
        $uploaded = SettingsService::get('church_logo_path');
        if (is_string($uploaded) && $uploaded !== '') {
            $path = $root . '/public/' . ltrim($uploaded, '/');
            if (is_file($path)) {
                return $path;
            }
        }

        return $root . '/public/images/kc-logo.png';
    }

    public static function statementLogoUrl(): string
    {
        $path = self::statementLogoPath();
        $publicRoot = dirname(__DIR__, 2) . '/public';
        if (str_starts_with($path, $publicRoot)) {
            return '/' . ltrim(substr($path, strlen($publicRoot)), '/');
        }

        return '/images/kc-logo.png';
    }

    public static function statementLogoDataUri(): string
    {
        $path = self::statementLogoPath();
        if (!is_file($path)) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    /**
     * @param array<string, mixed> $statement
     */
    public static function statementExportFilename(array $statement, string $extension): string
    {
        $view = $statement['view'] ?? 'monthly';
        $year = (int) ($statement['year'] ?? date('Y'));
        $slug = match ($view) {
            'weekly' => 'weekly-' . ($statement['week_date'] ?? $year),
            'annual' => 'annual-' . $year,
            default => 'monthly-' . ($statement['month'] ?? $year),
        };

        return 'financial-statement-' . $slug . '.' . ltrim($extension, '.');
    }

    /**
     * @param array<string, mixed> $statement
     */
    public static function statementToCsv(array $statement, string $churchName): string
    {
        $summary = $statement['summary'] ?? [
            'collections' => 0,
            'expenses' => 0,
            'balance' => 0,
            'status_label' => 'Balanced position',
        ];
        $fmt = static fn (float $n): string => number_format($n, 2, '.', '');

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }

        $write = static function (array $row) use ($stream): void {
            fputcsv($stream, $row);
        };

        $write([$churchName]);
        $write(['Financial Statement']);
        $write(['Statement period', $statement['period_label'] ?? '']);
        $write(['Subtitle', $statement['period_subtitle'] ?? '']);
        $write(['Generated', date('Y-m-d H:i')]);
        $write([]);

        $write(['Summary']);
        $write(['Total collections (KES)', $fmt((float) $summary['collections'])]);
        $write(['Weekly expenses (KES)', $fmt((float) $summary['expenses'])]);
        $write([$summary['status_label'] ?? 'Operating balance (KES)', $fmt((float) $summary['balance'])]);
        $write([]);

        $truePicture = $statement['true_picture'] ?? null;
        $arrears = $statement['arrears'] ?? null;
        if (is_array($truePicture) && is_array($arrears)) {
            $write(['Net Financial Position']);
            $write(['Operating balance (KES)', $fmt((float) ($truePicture['operating_balance'] ?? 0))]);
            $write(['Outstanding arrears (KES)', $fmt((float) ($arrears['balance_owing'] ?? 0))]);
            $write([($truePicture['status_label'] ?? 'Net position') . ' (KES)', $fmt((float) ($truePicture['net_position'] ?? 0))]);
            if (!empty($statement['true_picture_narrative'])) {
                $write(['Overview', $statement['true_picture_narrative']]);
            }
            $write([]);
        }

        if (!empty($statement['narrative'])) {
            $write(['Overview', $statement['narrative']]);
            $write([]);
        }

        $write(['Collections']);
        $write(['Description', 'Amount (KES)']);
        foreach ($statement['collection_lines'] ?? [] as $line) {
            $write([$line['label'] ?? '', $fmt((float) ($line['amount'] ?? 0))]);
        }
        $write(['Total collections', $fmt((float) $summary['collections'])]);
        $write([]);

        $write(['Weekly expenses']);
        $write(['Description', 'Amount (KES)']);
        foreach ($statement['expense_lines'] ?? [] as $line) {
            $write([$line['label'] ?? '', $fmt((float) ($line['amount'] ?? 0))]);
        }
        $write(['Total weekly expenses', $fmt((float) $summary['expenses'])]);
        $write([]);

        if (!empty($statement['activity_rows'])) {
            $write([$statement['activity_heading'] ?? 'Activity']);
            $write(['Period', 'Sub-period', 'Collections (KES)', 'Expenses (KES)', 'Balance (KES)']);
            foreach ($statement['activity_rows'] as $row) {
                $write([
                    $row['label'] ?? '',
                    $row['sub_label'] ?? '',
                    $fmt((float) ($row['collections'] ?? 0)),
                    $fmt((float) ($row['expenses'] ?? 0)),
                    $fmt((float) ($row['balance'] ?? 0)),
                ]);
            }
            $write([
                'Period total',
                '',
                $fmt((float) $summary['collections']),
                $fmt((float) $summary['expenses']),
                $fmt((float) $summary['balance']),
            ]);
        }

        if (!empty($statement['arrears_lines'])) {
            $arrearsSummary = $statement['arrears'] ?? [];
            $write(['Outstanding arrears']);
            $write(['Expense item', 'Period incurred', 'Amount due (KES)', 'Amount paid (KES)', 'Balance owing (KES)', 'Status']);
            foreach ($statement['arrears_lines'] as $line) {
                $write([
                    $line['expense_item'] ?? '',
                    $line['month_incurred'] ?? '',
                    $fmt((float) ($line['amount_due'] ?? 0)),
                    $fmt((float) ($line['amount_paid'] ?? 0)),
                    $fmt((float) ($line['balance_owing'] ?? 0)),
                    $line['status_label'] ?? '',
                ]);
            }
            $write([
                'Year totals',
                '',
                $fmt((float) ($arrearsSummary['total_due'] ?? 0)),
                $fmt((float) ($arrearsSummary['total_paid'] ?? 0)),
                $fmt((float) ($arrearsSummary['balance_owing'] ?? 0)),
                '',
            ]);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $csvContent = $csv !== false ? $csv : '';
        if ($csvContent !== '') {
            $csvContent .= "\n" . FinanceReconciliationService::STATEMENT_DISCLAIMER . "\n";
        }

        return $csvContent;
    }

    /** @return list<array<string, mixed>> */
    public static function collectionsList(int $year, ?string $month = null): array
    {
        self::ensureTables();
        $sql = 'SELECT * FROM finance_collections WHERE budget_year = ?';
        $params = [$year];
        if ($month) {
            $sql .= ' AND collection_date >= ? AND collection_date < ?';
            $start = $month . '-01';
            $end = (new \DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
            $params[] = $start;
            $params[] = $end;
        }
        $sql .= ' ORDER BY collection_date DESC, id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array{paybill: float, cheque: float, cash: float, total: float, count: int}
     */
    public static function collectionTotals(int $year, ?string $month = null): array
    {
        if ($month) {
            $grid = self::weeklyCollectionsGrid($month);
            $totals = [
                'paybill' => (float) ($grid['method_totals']['paybill'] ?? 0),
                'cheque' => (float) ($grid['method_totals']['cheque'] ?? 0),
                'cash' => (float) ($grid['method_totals']['cash'] ?? 0),
                'total' => (float) $grid['month_total'],
                'count' => count($grid['rows']),
            ];

            return $totals;
        }

        $totals = ['paybill' => 0.0, 'cheque' => 0.0, 'cash' => 0.0, 'total' => 0.0, 'count' => 0];
        $activity = self::yearActivitySummary($year);
        foreach ($activity['by_month'] as $summary) {
            if ($summary['collections'] <= 0 && $summary['expenses'] <= 0) {
                continue;
            }
            $totals['total'] += (float) $summary['collections'];
            $totals['count']++;
        }

        // Method breakdown still needs one grouped query for the year.
        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-01-01', $year + 1);
        $stmt = Database::connection()->prepare('
            SELECT payment_method, SUM(amount) AS total
            FROM finance_weekly_collections
            WHERE week_date >= ? AND week_date < ?
            GROUP BY payment_method
        ');
        $stmt->execute([$start, $end]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $method = (string) $row['payment_method'];
            if (isset($totals[$method])) {
                $totals[$method] = (float) $row['total'];
            }
        }

        return $totals;
    }

    public static function paymentMethodLabel(string $method): string
    {
        return self::PAYMENT_METHODS[$method]['label'] ?? ucfirst($method);
    }

    /** @param array<string, mixed> $data */
    public static function saveCollection(array $data, ?int $id = null): void
    {
        self::ensureTables();
        $method = $data['payment_method'] ?? 'cash';
        if (!isset(self::PAYMENT_METHODS[$method])) {
            $method = 'cash';
        }
        $amount = (float) ($data['amount'] ?? 0);

        if ($id) {
            $stmt = Database::connection()->prepare('
                UPDATE finance_collections SET
                    collection_date = ?, payment_method = ?, amount = ?, reference = ?,
                    fund_type = ?, notes = ?, budget_year = ?, updated_at = NOW()
                WHERE id = ?
            ');
            $stmt->execute([
                $data['collection_date'],
                $method,
                $amount,
                ($data['reference'] ?? '') ?: null,
                ($data['fund_type'] ?? '') ?: null,
                ($data['notes'] ?? '') ?: null,
                (int) ($data['budget_year'] ?? date('Y')),
                $id,
            ]);

            return;
        }

        $stmt = Database::connection()->prepare('
            INSERT INTO finance_collections
                (collection_date, payment_method, amount, reference, fund_type, notes, budget_year)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['collection_date'],
            $method,
            $amount,
            ($data['reference'] ?? '') ?: null,
            ($data['fund_type'] ?? '') ?: null,
            ($data['notes'] ?? '') ?: null,
            (int) ($data['budget_year'] ?? date('Y')),
        ]);
    }

    public static function deleteCollection(int $id): void
    {
        self::ensureTables();
        Database::connection()->prepare('DELETE FROM finance_collections WHERE id = ?')->execute([$id]);
    }

    /** @var array<string, float> Standard weekly expense preset (KES). */
    public const SUNDAY_PRESET_STANDARD = [
        'keyboardist' => 2500,
        'drummer' => 2500,
        'kids_teacher' => 2500,
        'caretaker' => 4200,
        'kplc_tokens' => 500,
    ];

    /** @var array<string, float> Standard Sunday plus honorarium (KES). */
    public const SUNDAY_PRESET_FULL = [
        'keyboardist' => 2500,
        'drummer' => 2500,
        'kids_teacher' => 2500,
        'caretaker' => 4200,
        'honorarium_gifts' => 5000,
        'kplc_tokens' => 500,
    ];

    /**
     * Executive dashboard — YTD totals, monthly performance, arrears snapshot.
     *
     * @return array<string, mixed>
     */
    public static function buildDashboard(int $year): array
    {
        self::ensureTables();

        $activity = self::yearActivitySummary($year);
        $yearRec = self::yearReconciliationTotals($year);
        $arrears = self::arrearsList($year);

        $arrearsOwing = 0.0;
        $unpaidArrears = [];
        foreach ($arrears as $row) {
            $owing = round((float) ($row['balance_owing'] ?? 0), 2);
            $arrearsOwing += $owing;
            if ($owing > 0) {
                $unpaidArrears[] = $row;
            }
        }
        $arrearsOwing = round($arrearsOwing, 2);
        usort($unpaidArrears, static fn (array $a, array $b): int => (int) (($b['balance_owing'] ?? 0) <=> ($a['balance_owing'] ?? 0)));

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $yearMonth = sprintf('%04d-%02d', $year, $m);
            $row = $activity['by_month'][$yearMonth];
            $col = round((float) $row['collections'], 2);
            $exp = round((float) $row['expenses'], 2);
            $months[] = [
                'month' => $yearMonth,
                'label' => date('M', strtotime($yearMonth . '-01')),
                'full_label' => date('F Y', strtotime($yearMonth . '-01')),
                'collections' => $col,
                'expenses' => $exp,
                'balance' => round($col - $exp, 2),
                'has_activity' => $col > 0 || $exp > 0,
            ];
        }

        $running = self::runningBalanceYtd($year);

        return [
            'year' => $year,
            'collections_ytd' => round($yearRec['year_collections'], 2),
            'expenses_ytd' => round($yearRec['year_expenses'], 2),
            'net_operating' => round($yearRec['year_balance'], 2),
            'arrears_owing' => $arrearsOwing,
            'arrears_count' => count($unpaidArrears),
            'net_position' => round($yearRec['year_balance'] - $arrearsOwing, 2),
            'months' => $months,
            'top_arrears' => array_slice($unpaidArrears, 0, 6),
            'recent_weeks' => $running['weeks'],
            'running_balance' => $running['current'],
            'weeks_recorded' => $running['weeks_count'],
        ];
    }

    /**
     * Year totals — always sums all 12 months (accurate YTD).
     *
     * @return array{year_collections: float, year_expenses: float, year_balance: float}
     */
    public static function yearReconciliationTotals(int $year): array
    {
        $activity = self::yearActivitySummary($year);
        $yearExpenses = 0.0;
        $yearCollections = 0.0;

        foreach ($activity['by_month'] as $summary) {
            $yearExpenses += (float) $summary['expenses'];
            $yearCollections += (float) $summary['collections'];
        }

        return [
            'year_collections' => round($yearCollections, 2),
            'year_expenses' => round($yearExpenses, 2),
            'year_balance' => round($yearCollections - $yearExpenses, 2),
        ];
    }

    /**
     * Running balance across all Sundays in a year (chronological).
     *
     * @return array{current: float, weeks: list<array<string, mixed>>, weeks_count: int}
     */
    public static function runningBalanceYtd(int $year): array
    {
        $activity = self::yearActivitySummary($year);
        $weeks = [];
        $running = 0.0;
        $weekDates = array_keys($activity['by_week']);
        sort($weekDates);
        $sundaysCache = [];

        foreach ($weekDates as $weekDate) {
            $row = $activity['by_week'][$weekDate];
            $col = $row['collections'];
            $exp = $row['expenses'];
            if ($col <= 0 && $exp <= 0) {
                continue;
            }
            $bal = $row['balance'];
            $running = round($running + $bal, 2);
            $yearMonth = substr($weekDate, 0, 7);
            if (!isset($sundaysCache[$yearMonth])) {
                $sundaysCache[$yearMonth] = self::sundaysInMonth($yearMonth);
            }
            $sunIndex = array_search($weekDate, $sundaysCache[$yearMonth], true);
            $weeks[] = [
                'week_date' => $weekDate,
                'sun_label' => 'Sun ' . ($sunIndex !== false ? $sunIndex + 1 : 1),
                'collections' => $col,
                'expenses' => $exp,
                'balance' => $bal,
                'running_balance' => $running,
            ];
        }

        return [
            'current' => $running,
            'weeks' => array_slice(array_reverse($weeks), 0, 8),
            'weeks_count' => count($weeks),
        ];
    }

    /** @return array{collections: array<string, float>, expenses: array<string, float>, notes: string} */
    public static function sundaySessionData(string $weekDate): array
    {
        $sessions = self::sundaySessionsForDates([$weekDate]);

        return $sessions[$weekDate] ?? [
            'collections' => array_fill_keys(array_keys(self::PAYMENT_METHODS), 0.0),
            'expenses' => [],
            'notes' => '',
        ];
    }

    /**
     * Batch-load Sunday session data for multiple dates (3 queries total).
     *
     * @param list<string> $weekDates
     * @return array<string, array{collections: array<string, float>, expenses: array<string, float>, notes: string}>
     */
    public static function sundaySessionsForDates(array $weekDates): array
    {
        $weekDates = array_values(array_unique(array_filter($weekDates)));
        if ($weekDates === []) {
            return [];
        }

        self::ensureTables();
        $defaultCollections = array_fill_keys(array_keys(self::PAYMENT_METHODS), 0.0);
        $sessions = [];
        foreach ($weekDates as $weekDate) {
            $sessions[$weekDate] = [
                'collections' => $defaultCollections,
                'expenses' => [],
                'notes' => '',
            ];
        }

        $placeholders = implode(',', array_fill(0, count($weekDates), '?'));
        $db = Database::connection();

        $colStmt = $db->prepare("
            SELECT week_date, payment_method, amount
            FROM finance_weekly_collections
            WHERE week_date IN ($placeholders)
        ");
        $colStmt->execute($weekDates);
        foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $date = (string) $row['week_date'];
            $method = (string) $row['payment_method'];
            if (isset($sessions[$date]['collections'][$method])) {
                $sessions[$date]['collections'][$method] = (float) $row['amount'];
            }
        }

        $expStmt = $db->prepare("
            SELECT week_date, category_slug, amount
            FROM finance_weekly_expenses
            WHERE week_date IN ($placeholders)
        ");
        $expStmt->execute($weekDates);
        foreach ($expStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $date = (string) $row['week_date'];
            $sessions[$date]['expenses'][(string) $row['category_slug']] = (float) $row['amount'];
        }

        try {
            $noteStmt = $db->prepare("SELECT week_date, notes FROM finance_sunday_sessions WHERE week_date IN ($placeholders)");
            $noteStmt->execute($weekDates);
            foreach ($noteStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $date = (string) $row['week_date'];
                $sessions[$date]['notes'] = (string) ($row['notes'] ?? '');
            }
        } catch (\Throwable) {
            // sessions table may not exist yet on first run
        }

        return $sessions;
    }

    /**
     * Save collections, expenses, and optional notes for one Sunday.
     *
     * @param array<string, float|string> $collectionAmounts
     * @param array<string, float|string> $expenseAmounts
     */
    public static function saveSundayEntry(string $weekDate, array $collectionAmounts, array $expenseAmounts, string $notes = ''): void
    {
        self::saveWeeklyCollectionEntry($weekDate, $collectionAmounts);
        self::saveWeeklyEntry($weekDate, $expenseAmounts);
        self::saveSundayNotes($weekDate, $notes);
        self::clearRuntimeCaches();
    }

    public static function saveSundayNotes(string $weekDate, string $notes): void
    {
        self::ensureTables();
        $notes = trim($notes);
        $db = Database::connection();

        try {
            if ($notes === '') {
                $db->prepare('DELETE FROM finance_sunday_sessions WHERE week_date = ?')->execute([$weekDate]);

                return;
            }
            $db->prepare('
                INSERT INTO finance_sunday_sessions (week_date, notes)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE notes = VALUES(notes), updated_at = NOW()
            ')->execute([$weekDate, $notes]);
        } catch (\Throwable) {
            // sessions table not yet migrated
        }
    }

    /** All Sundays in a calendar year. */
    public static function sundaysInYear(int $year): array
    {
        $days = [];
        $date = new \DateTimeImmutable(sprintf('%04d-01-01', $year));
        $end = new \DateTimeImmutable(sprintf('%04d-12-31', $year));

        while ($date <= $end) {
            if ($date->format('w') === '0') {
                $days[] = $date->format('Y-m-d');
            }
            $date = $date->modify('+1 day');
        }

        return $days;
    }

    /** Suggested next Sunday for data entry (latest Sunday with data, or current month). */
    public static function suggestedSundayDate(?string $month = null): string
    {
        $month = $month ?? date('Y-m');
        $sundays = self::sundaysInMonth($month);
        if ($sundays === []) {
            return date('Y-m-d');
        }

        $today = date('Y-m-d');
        $past = array_filter($sundays, static fn (string $d): bool => $d <= $today);

        return $past !== [] ? (string) end($past) : $sundays[0];
    }
}
