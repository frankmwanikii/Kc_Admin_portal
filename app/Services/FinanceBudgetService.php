<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class FinanceBudgetService
{
    private const SCHEMA_VERSION = 1;

    private static bool $ready = false;

    /** @var array<string, string> Weekly department slug → Draft Budget account code */
    private const DEPARTMENT_BUDGET_CODES = [
        'k_kids' => '001/3/001',
        'worship_services' => '001/3/002',
        'discipleship_programmes' => '001/3/003',
        'production_sound_lighting' => '001/3/004',
        'wages' => '001/3/005',
        'training_development' => '001/3/006',
        'pastoral_allowances_salaries' => '001/3/007',
        'pastoral_care' => '001/3/008',
        'missions_outreach' => '001/3/009',
        'gpm_remittances' => '001/3/010',
        'honorarium_gifts' => '001/3/011',
        'benevolent' => '001/3/012',
    ];

    public static function ensureTables(): void
    {
        if (self::$ready) {
            return;
        }

        if (($_ENV['APP_INSTALLED'] ?? 'false') !== 'true') {
            return;
        }

        FinanceReconciliationService::ensureTables();
        SettingsService::ensureTable();

        $stored = (int) (SettingsService::get('finance_budget_schema_version') ?? '0');
        if ($stored < self::SCHEMA_VERSION) {
            $sql = file_get_contents(dirname(__DIR__, 2) . '/database/finance-budget.sql');
            if ($sql) {
                Database::connection()->exec($sql);
            }
            SettingsService::set('finance_budget_schema_version', (string) self::SCHEMA_VERSION);
        }

        self::$ready = true;
        self::importSeedIfEmpty(2026);
    }

    /** FY start year for a calendar date (April–March). */
    public static function budgetYearForDate(string $date): int
    {
        $ts = strtotime($date);
        $month = (int) date('n', $ts);
        $year = (int) date('Y', $ts);

        return $month >= 4 ? $year : $year - 1;
    }

    /** @return list<string> Calendar months for FY starting in budget_year (Apr–Mar). */
    public static function fiscalYearMonths(int $budgetYear): array
    {
        $months = [];
        for ($m = 4; $m <= 12; $m++) {
            $months[] = sprintf('%04d-%02d', $budgetYear, $m);
        }
        for ($m = 1; $m <= 3; $m++) {
            $months[] = sprintf('%04d-%02d', $budgetYear + 1, $m);
        }

        return $months;
    }

    public static function importSeedIfEmpty(int $budgetYear): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM finance_budget_lines WHERE budget_year = ?');
        $stmt->execute([$budgetYear]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $path = dirname(__DIR__, 2) . '/database/seeds/finance-budget-' . $budgetYear . '.json';
        if (!is_file($path)) {
            return false;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (!is_array($payload)) {
            return false;
        }

        self::importPayload($payload);

        return true;
    }

    /** @param array<string, mixed> $payload */
    public static function importPayload(array $payload): void
    {
        $budgetYear = (int) ($payload['budget_year'] ?? 0);
        if ($budgetYear <= 0) {
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $lineIds = $db->prepare('SELECT id FROM finance_budget_lines WHERE budget_year = ?');
            $lineIds->execute([$budgetYear]);
            foreach ($lineIds->fetchAll(PDO::FETCH_COLUMN) as $lineId) {
                $db->prepare('DELETE FROM finance_budget_monthly WHERE budget_line_id = ?')->execute([(int) $lineId]);
            }
            $db->prepare('DELETE FROM finance_budget_lines WHERE budget_year = ?')->execute([$budgetYear]);

            $insertLine = $db->prepare('
                INSERT INTO finance_budget_lines (budget_year, line_type, section, label, account_code, sort_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $insertAmount = $db->prepare('
                INSERT INTO finance_budget_monthly (budget_line_id, budget_month, amount)
                VALUES (?, ?, ?)
            ');

            foreach ($payload['lines'] ?? [] as $line) {
                $insertLine->execute([
                    $budgetYear,
                    $line['line_type'] ?? 'expense',
                    $line['section'] ?? '',
                    $line['label'] ?? '',
                    $line['account_code'] ?? '',
                    (int) ($line['sort_order'] ?? 100),
                ]);
                $lineId = (int) $db->lastInsertId();
                foreach ($line['amounts'] ?? [] as $yearMonth => $amount) {
                    $amt = round((float) $amount, 2);
                    if ($amt === 0.0) {
                        continue;
                    }
                    $insertAmount->execute([$lineId, $yearMonth, $amt]);
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Budget vs actual summary for a financial year.
     *
     * @return array{
     *   budget_year: int,
     *   label: string,
     *   months: list<array<string, mixed>>,
     *   totals: array<string, float>,
     *   lines: list<array<string, mixed>>,
     *   has_budget: bool
     * }
     */
    public static function buildBudgetVsActual(int $budgetYear, ?string $focusMonth = null): array
    {
        self::ensureTables();
        $fiscalMonths = self::fiscalYearMonths($budgetYear);
        if ($focusMonth !== null && !in_array($focusMonth, $fiscalMonths, true)) {
            $focusMonth = null;
        }

        $budgetByMonth = self::budgetTotalsByMonth($budgetYear, $fiscalMonths);
        $actualByMonth = self::actualTotalsByMonth($fiscalMonths);
        $lineComparison = self::lineComparison($budgetYear, $focusMonth ?? $fiscalMonths[0]);

        $months = [];
        $totals = [
            'budget_income' => 0.0,
            'actual_income' => 0.0,
            'budget_expenses' => 0.0,
            'actual_expenses' => 0.0,
        ];

        foreach ($fiscalMonths as $yearMonth) {
            $budgetIncome = round((float) ($budgetByMonth[$yearMonth]['income'] ?? 0), 2);
            $budgetExpenses = round((float) ($budgetByMonth[$yearMonth]['expense'] ?? 0), 2);
            $actualIncome = round((float) ($actualByMonth[$yearMonth]['collections'] ?? 0), 2);
            $actualExpenses = round((float) ($actualByMonth[$yearMonth]['expenses'] ?? 0), 2);
            $budgetNet = round($budgetIncome - $budgetExpenses, 2);
            $actualNet = round($actualIncome - $actualExpenses, 2);
            $incomeVariance = round($actualIncome - $budgetIncome, 2);
            $expenseVariance = round($budgetExpenses - $actualExpenses, 2);

            $months[] = [
                'month' => $yearMonth,
                'label' => date('M Y', strtotime($yearMonth . '-01')),
                'budget_income' => $budgetIncome,
                'actual_income' => $actualIncome,
                'budget_expenses' => $budgetExpenses,
                'actual_expenses' => $actualExpenses,
                'budget_net' => $budgetNet,
                'actual_net' => $actualNet,
                'income_variance' => $incomeVariance,
                'expense_variance' => $expenseVariance,
                'expense_used_pct' => $budgetExpenses > 0 ? round(($actualExpenses / $budgetExpenses) * 100, 1) : null,
                'status' => self::monthStatus($budgetIncome, $budgetExpenses, $actualIncome, $actualExpenses),
                'status_label' => self::monthStatusLabel($budgetIncome, $budgetExpenses, $actualIncome, $actualExpenses),
                'has_activity' => $actualIncome > 0 || $actualExpenses > 0 || $budgetIncome > 0 || $budgetExpenses > 0,
            ];

            $totals['budget_income'] += $budgetIncome;
            $totals['actual_income'] += $actualIncome;
            $totals['budget_expenses'] += $budgetExpenses;
            $totals['actual_expenses'] += $actualExpenses;
        }

        foreach ($totals as $key => $val) {
            $totals[$key] = round($val, 2);
        }
        $totals['budget_net'] = round($totals['budget_income'] - $totals['budget_expenses'], 2);
        $totals['actual_net'] = round($totals['actual_income'] - $totals['actual_expenses'], 2);
        $totals['status'] = self::monthStatus(
            $totals['budget_income'],
            $totals['budget_expenses'],
            $totals['actual_income'],
            $totals['actual_expenses']
        );
        $totals['status_label'] = self::monthStatusLabel(
            $totals['budget_income'],
            $totals['budget_expenses'],
            $totals['actual_income'],
            $totals['actual_expenses']
        );

        $hasBudget = array_sum(array_column($months, 'budget_expenses')) > 0
            || array_sum(array_column($months, 'budget_income')) > 0;

        return [
            'budget_year' => $budgetYear,
            'label' => 'FY ' . $budgetYear . '/' . substr((string) ($budgetYear + 1), 2),
            'months' => $months,
            'totals' => $totals,
            'lines' => $lineComparison,
            'focus_month' => $focusMonth,
            'has_budget' => $hasBudget,
        ];
    }

    /** Snapshot for dashboard current month — lightweight (single month only). */
    public static function currentMonthSnapshot(int $budgetYear, string $calendarMonth): array
    {
        if (!self::tablesExist()) {
            return self::emptyMonthSnapshot($calendarMonth, false);
        }

        $db = Database::connection();

        $budgetIncome = 0.0;
        $budgetExpenses = 0.0;
        $stmt = $db->prepare('
            SELECT l.line_type, COALESCE(SUM(m.amount), 0) AS total
            FROM finance_budget_lines l
            LEFT JOIN finance_budget_monthly m ON m.budget_line_id = l.id AND m.budget_month = ?
            WHERE l.budget_year = ?
            GROUP BY l.line_type
        ');
        $stmt->execute([$calendarMonth, $budgetYear]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $total = round((float) $row['total'], 2);
            if ($row['line_type'] === 'income') {
                $budgetIncome = $total;
            } else {
                $budgetExpenses = $total;
            }
        }

        $start = $calendarMonth . '-01';
        $end = (new \DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');

        $colStmt = $db->prepare('
            SELECT COALESCE(SUM(amount), 0) FROM finance_weekly_collections
            WHERE week_date >= ? AND week_date < ?
        ');
        $colStmt->execute([$start, $end]);
        $actualIncome = round((float) $colStmt->fetchColumn(), 2);

        $expStmt = $db->prepare('
            SELECT COALESCE(SUM(amount), 0) FROM finance_weekly_expenses
            WHERE week_date >= ? AND week_date < ?
        ');
        $expStmt->execute([$start, $end]);
        $actualExpenses = round((float) $expStmt->fetchColumn(), 2);

        $expenseVariance = round($budgetExpenses - $actualExpenses, 2);

        $fyStmt = $db->prepare('
            SELECT COALESCE(SUM(m.amount), 0)
            FROM finance_budget_monthly m
            INNER JOIN finance_budget_lines l ON l.id = m.budget_line_id
            WHERE l.budget_year = ? AND l.line_type = "expense"
        ');
        $fyStmt->execute([$budgetYear]);
        $fyBudgetExpenses = round((float) $fyStmt->fetchColumn(), 2);
        $hasFyBudget = $fyBudgetExpenses > 0;

        return [
            'month' => $calendarMonth,
            'label' => date('M Y', strtotime($calendarMonth . '-01')),
            'budget_income' => $budgetIncome,
            'actual_income' => $actualIncome,
            'budget_expenses' => $budgetExpenses,
            'actual_expenses' => $actualExpenses,
            'income_variance' => round($actualIncome - $budgetIncome, 2),
            'expense_variance' => $expenseVariance,
            'expense_used_pct' => $budgetExpenses > 0 ? round(($actualExpenses / $budgetExpenses) * 100, 1) : null,
            'status' => self::monthStatus($budgetIncome, $budgetExpenses, $actualIncome, $actualExpenses),
            'status_label' => self::monthStatusLabel($budgetIncome, $budgetExpenses, $actualIncome, $actualExpenses),
            'has_activity' => $actualIncome > 0 || $actualExpenses > 0 || $budgetIncome > 0 || $budgetExpenses > 0,
            'has_fy_budget' => $hasFyBudget,
            'fy_budget_expenses' => $fyBudgetExpenses,
            'fy_label' => 'FY ' . $budgetYear . '/' . substr((string) ($budgetYear + 1), 2),
        ];
    }

    private static function tablesExist(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            Database::connection()->query('SELECT 1 FROM finance_budget_lines LIMIT 1');
            $exists = true;
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }

    /** @return array<string, mixed> */
    private static function emptyMonthSnapshot(string $calendarMonth, bool $hasFyBudget): array
    {
        return [
            'month' => $calendarMonth,
            'label' => date('M Y', strtotime($calendarMonth . '-01')),
            'budget_income' => 0.0,
            'actual_income' => 0.0,
            'budget_expenses' => 0.0,
            'actual_expenses' => 0.0,
            'income_variance' => 0.0,
            'expense_variance' => 0.0,
            'expense_used_pct' => null,
            'status' => 'neutral',
            'status_label' => $hasFyBudget ? 'No budget this month' : 'No budget loaded',
            'has_activity' => false,
            'has_fy_budget' => $hasFyBudget,
            'fy_budget_expenses' => 0.0,
            'fy_label' => '',
        ];
    }

    /**
     * @param list<string> $fiscalMonths
     * @return array<string, array{income: float, expense: float}>
     */
    private static function budgetTotalsByMonth(int $budgetYear, array $fiscalMonths): array
    {
        $out = array_fill_keys($fiscalMonths, ['income' => 0.0, 'expense' => 0.0]);
        $placeholders = implode(',', array_fill(0, count($fiscalMonths), '?'));
        $stmt = Database::connection()->prepare("
            SELECT m.budget_month, l.line_type, SUM(m.amount) AS total
            FROM finance_budget_monthly m
            INNER JOIN finance_budget_lines l ON l.id = m.budget_line_id
            WHERE l.budget_year = ? AND m.budget_month IN ($placeholders)
            GROUP BY m.budget_month, l.line_type
        ");
        $stmt->execute(array_merge([$budgetYear], $fiscalMonths));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ym = (string) $row['budget_month'];
            $type = (string) $row['line_type'];
            if (!isset($out[$ym])) {
                continue;
            }
            $out[$ym][$type] = round((float) $row['total'], 2);
        }

        return $out;
    }

    /**
     * @param list<string> $fiscalMonths
     * @return array<string, array{collections: float, expenses: float}>
     */
    private static function actualTotalsByMonth(array $fiscalMonths): array
    {
        $out = array_fill_keys($fiscalMonths, ['collections' => 0.0, 'expenses' => 0.0]);
        if ($fiscalMonths === []) {
            return $out;
        }

        $start = $fiscalMonths[0] . '-01';
        $endMonth = $fiscalMonths[count($fiscalMonths) - 1];
        $end = (new \DateTimeImmutable($endMonth . '-01'))->modify('+1 month')->format('Y-m-d');
        $db = Database::connection();

        $colStmt = $db->prepare('
            SELECT DATE_FORMAT(week_date, "%Y-%m") AS ym, SUM(amount) AS total
            FROM finance_weekly_collections
            WHERE week_date >= ? AND week_date < ?
            GROUP BY ym
        ');
        $colStmt->execute([$start, $end]);
        foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ym = (string) $row['ym'];
            if (isset($out[$ym])) {
                $out[$ym]['collections'] = round((float) $row['total'], 2);
            }
        }

        $expStmt = $db->prepare('
            SELECT DATE_FORMAT(week_date, "%Y-%m") AS ym, SUM(amount) AS total
            FROM finance_weekly_expenses
            WHERE week_date >= ? AND week_date < ?
            GROUP BY ym
        ');
        $expStmt->execute([$start, $end]);
        foreach ($expStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ym = (string) $row['ym'];
            if (isset($out[$ym])) {
                $out[$ym]['expenses'] = round((float) $row['total'], 2);
            }
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private static function lineComparison(int $budgetYear, string $yearMonth): array
    {
        $budgetLines = self::budgetLinesForMonth($budgetYear, $yearMonth);
        $actualByCode = self::actualExpensesByBudgetCode($yearMonth);

        $rows = [];
        foreach ($budgetLines as $line) {
            if ($line['line_type'] !== 'expense') {
                continue;
            }
            $code = (string) $line['account_code'];
            $budget = round((float) $line['amount'], 2);
            $actual = round((float) ($actualByCode[$code] ?? 0), 2);
            if ($budget <= 0 && $actual <= 0) {
                continue;
            }
            $rows[] = [
                'section' => $line['section'],
                'label' => $line['label'],
                'account_code' => $code,
                'sort_order' => (int) ($line['sort_order'] ?? 0),
                'budget' => $budget,
                'actual' => $actual,
                'variance' => round($budget - $actual, 2),
                'used_pct' => $budget > 0 ? round(($actual / $budget) * 100, 1) : null,
                'status' => self::expenseLineStatus($budget, $actual),
                'status_label' => self::expenseLineStatusLabel($budget, $actual),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => ($b['actual'] <=> $a['actual']) ?: ($a['sort_order'] <=> $b['sort_order']));

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private static function budgetLinesForMonth(int $budgetYear, string $yearMonth): array
    {
        $stmt = Database::connection()->prepare('
            SELECT l.id, l.line_type, l.section, l.label, l.account_code, l.sort_order,
                   COALESCE(m.amount, 0) AS amount
            FROM finance_budget_lines l
            LEFT JOIN finance_budget_monthly m ON m.budget_line_id = l.id AND m.budget_month = ?
            WHERE l.budget_year = ?
            ORDER BY l.line_type ASC, l.sort_order ASC, l.id ASC
        ');
        $stmt->execute([$yearMonth, $budgetYear]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, float> account_code => amount */
    private static function actualExpensesByBudgetCode(string $yearMonth): array
    {
        $start = $yearMonth . '-01';
        $end = (new \DateTimeImmutable($start))->modify('+1 month')->format('Y-m-d');
        $db = Database::connection();

        $byCode = [];

        $adminStmt = $db->prepare('
            SELECT ec.account_code, SUM(e.amount) AS total
            FROM finance_weekly_expenses e
            INNER JOIN finance_weekly_categories c ON c.slug = e.category_slug
            INNER JOIN finance_expense_categories ec ON ec.id = c.expense_category_id
            WHERE e.week_date >= ? AND e.week_date < ?
            GROUP BY ec.account_code
        ');
        $adminStmt->execute([$start, $end]);
        foreach ($adminStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code = (string) $row['account_code'];
            $byCode[$code] = round((float) ($byCode[$code] ?? 0) + (float) $row['total'], 2);
        }

        $deptStmt = $db->prepare('
            SELECT d.slug, SUM(e.amount) AS total
            FROM finance_weekly_expenses e
            INNER JOIN finance_weekly_categories c ON c.slug = e.category_slug
            INNER JOIN finance_expense_departments d ON d.id = c.department_id
            WHERE e.week_date >= ? AND e.week_date < ?
            GROUP BY d.slug
        ');
        $deptStmt->execute([$start, $end]);
        foreach ($deptStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $slug = (string) $row['slug'];
            $code = self::DEPARTMENT_BUDGET_CODES[$slug] ?? null;
            if ($code === null) {
                continue;
            }
            $byCode[$code] = round((float) ($byCode[$code] ?? 0) + (float) $row['total'], 2);
        }

        return $byCode;
    }

    private static function monthStatus(float $budgetIncome, float $budgetExpenses, float $actualIncome, float $actualExpenses): string
    {
        if ($budgetExpenses > 0 && $actualExpenses > $budgetExpenses) {
            return 'over';
        }
        if ($budgetIncome > 0 && $actualIncome < $budgetIncome) {
            return 'under_income';
        }
        if ($budgetExpenses > 0 || $budgetIncome > 0 || $actualExpenses > 0 || $actualIncome > 0) {
            return 'on_track';
        }

        return 'neutral';
    }

    private static function monthStatusLabel(float $budgetIncome, float $budgetExpenses, float $actualIncome, float $actualExpenses): string
    {
        return match (self::monthStatus($budgetIncome, $budgetExpenses, $actualIncome, $actualExpenses)) {
            'over' => 'Over budget',
            'under_income' => 'Below income target',
            'on_track' => 'On track',
            default => 'No activity',
        };
    }

    private static function expenseLineStatus(float $budget, float $actual): string
    {
        if ($budget <= 0) {
            return $actual > 0 ? 'unbudgeted' : 'neutral';
        }
        if ($actual > $budget) {
            return 'over';
        }

        return 'on_track';
    }

    private static function expenseLineStatusLabel(float $budget, float $actual): string
    {
        return match (self::expenseLineStatus($budget, $actual)) {
            'over' => 'Over budget',
            'unbudgeted' => 'Spent (no budget)',
            'on_track' => 'On track',
            default => '—',
        };
    }
}
