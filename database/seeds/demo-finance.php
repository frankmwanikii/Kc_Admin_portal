<?php

declare(strict_types=1);

/**
 * Seed realistic finance demo data for presentations.
 *
 * Keeps expense catalog + budget line structure.
 * Clears transactional rows, then loads June–July 2026 Sundays + sample bills.
 *
 * Usage: php database/seeds/demo-finance.php
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Database;
use App\Services\FinanceBudgetService;
use App\Services\FinanceReconciliationService;
use App\Services\FormSubmissionService;

$root = dirname(__DIR__, 2);
if (is_file($root . '/.env')) {
    Dotenv\Dotenv::createMutable($root)->safeLoad();
}

if (($_ENV['APP_INSTALLED'] ?? '') !== 'true' && (getenv('APP_INSTALLED') ?: '') !== 'true') {
    // Allow seeding even if flag missing when DB works
    putenv('APP_INSTALLED=true');
    $_ENV['APP_INSTALLED'] = 'true';
}

echo "Seeding finance demo data…\n";

FormSubmissionService::ensureFinanceTables();
FinanceReconciliationService::ensureTables();
FinanceBudgetService::ensureTables();
FinanceBudgetService::importSeedIfEmpty(2026);

$db = Database::connection();

echo "  Clearing transactional finance rows…\n";
$db->exec('DELETE FROM finance_expense_arrears');
$db->exec('DELETE FROM finance_weekly_expenses');
$db->exec('DELETE FROM finance_weekly_collections');
$db->exec('DELETE FROM finance_sunday_sessions');
$db->exec('DELETE FROM finance_collections');

$standard = FinanceReconciliationService::SUNDAY_PRESET_STANDARD;
$full = FinanceReconciliationService::SUNDAY_PRESET_FULL;

/** @var list<array{0: string, 1: array<string, float>, 2: array<string, float>, 3: string}> $sundays */
$sundays = [
    // June 2026 — build trend / prior month
    ['2026-06-01', ['paybill' => 38500, 'cheque' => 0, 'cash' => 9200], $standard, 'First Sunday of June'],
    ['2026-06-08', ['paybill' => 41200, 'cheque' => 5000, 'cash' => 7800], $standard, ''],
    ['2026-06-15', ['paybill' => 45600, 'cheque' => 0, 'cash' => 11400], $full, 'Guest speaker — honorarium'],
    ['2026-06-22', ['paybill' => 39800, 'cheque' => 0, 'cash' => 8600], $standard, ''],
    ['2026-06-29', ['paybill' => 52100, 'cheque' => 10000, 'cash' => 13200], $full, 'Month-end thanksgiving'],

    // July 2026 — primary demo month
    ['2026-07-05', ['paybill' => 44200, 'cheque' => 0, 'cash' => 9800], $standard, 'First Sunday of July'],
    ['2026-07-12', ['paybill' => 47500, 'cheque' => 7500, 'cash' => 10200], $full, 'Missions Sunday'],
    ['2026-07-19', ['paybill' => 46800, 'cheque' => 0, 'cash' => 11500], $standard, ''],
    ['2026-07-26', ['paybill' => 51000, 'cheque' => 5000, 'cash' => 12800], $full, 'Youth Sunday'],
];

echo "  Seeding weekly collections & expenses…\n";
foreach ($sundays as [$weekDate, $collections, $expenses, $notes]) {
    FinanceReconciliationService::saveSundayEntry($weekDate, $collections, $expenses, $notes);
    echo "    · {$weekDate}\n";
}

echo "  Seeding expense bills (arrears)…\n";
$bills = [
    [
        'department_id' => 2, // Administration
        'category_id' => 1,   // Rent
        'month_incurred' => 'Jul 2026',
        'amount_due' => 55000,
        'amount_paid' => 55000,
        'date_paid' => '2026-07-03',
        'paid_by_ref' => 'M-Pesa Paybill / Admin',
        'notes' => 'July rent — paid in full',
        'budget_year' => 2026,
    ],
    [
        'department_id' => 2,
        'category_id' => 3, // Electricity
        'month_incurred' => 'Jun – Jul 2026',
        'amount_due' => 18500,
        'amount_paid' => 10000,
        'date_paid' => '2026-07-10',
        'paid_by_ref' => 'Cheque #1042',
        'notes' => 'Partial — balance due mid-month',
        'budget_year' => 2026,
    ],
    [
        'department_id' => 2,
        'category_id' => 2, // Water
        'month_incurred' => 'Jul 2026',
        'amount_due' => 4200,
        'amount_paid' => 0,
        'date_paid' => '',
        'paid_by_ref' => '',
        'notes' => 'County water bill — awaiting payment',
        'budget_year' => 2026,
    ],
    [
        'department_id' => 2,
        'category_id' => 6, // Security
        'month_incurred' => 'Jul 2026',
        'amount_due' => 15000,
        'amount_paid' => 15000,
        'date_paid' => '2026-07-05',
        'paid_by_ref' => 'Cash / Security firm',
        'notes' => '',
        'budget_year' => 2026,
    ],
    [
        'department_id' => 7, // Training & Development
        'category_id' => 22,
        'month_incurred' => '2025 carry-over',
        'amount_due' => 28000,
        'amount_paid' => 8000,
        'date_paid' => '2026-06-20',
        'paid_by_ref' => 'Paybill',
        'notes' => 'Leadership retreat balance from last year',
        'budget_year' => 2026,
    ],
    [
        'department_id' => 10, // Missions & Outreach
        'category_id' => 25,
        'month_incurred' => 'Jul 2026',
        'amount_due' => 35000,
        'amount_paid' => 0,
        'date_paid' => '',
        'paid_by_ref' => '',
        'notes' => 'Community outreach planned for end of month',
        'budget_year' => 2026,
    ],
    [
        'department_id' => 2,
        'category_id' => 14, // Repair & Maintenance
        'month_incurred' => 'May – Jun 2026',
        'amount_due' => 22000,
        'amount_paid' => 22000,
        'date_paid' => '2026-06-28',
        'paid_by_ref' => 'Cheque #1038',
        'notes' => 'Sound booth repairs completed',
        'budget_year' => 2026,
    ],
    [
        'department_id' => 1, // K.Kids
        'category_id' => 17,
        'month_incurred' => 'Jul 2026',
        'amount_due' => 8500,
        'amount_paid' => 8500,
        'date_paid' => '2026-07-12',
        'paid_by_ref' => 'Cash',
        'notes' => 'Kids ministry materials & snacks',
        'budget_year' => 2026,
    ],
];

foreach ($bills as $bill) {
    FinanceReconciliationService::saveArrear($bill);
    echo "    · {$bill['month_incurred']} — due " . number_format((float) $bill['amount_due']) . "\n";
}

// Light budget actuals for demo months (where seed had zeros)
echo "  Filling budget monthly amounts for Jun–Jul 2026…\n";
$lines = $db->query('SELECT id, account_code, line_type FROM finance_budget_lines WHERE budget_year = 2026')->fetchAll(PDO::FETCH_ASSOC);
$byCode = [];
foreach ($lines as $line) {
    $byCode[(string) $line['account_code']] = (int) $line['id'];
}

$monthFills = [
    '2026-06' => [
        '001/2/001' => 55000, // Rent
        '001/2/002' => 4000,
        '001/2/003' => 12000,
        '001/2/006' => 15000,
        '001/3/001' => 18000, // Worship
        '001/1/001' => 8000,  // K.Kids
    ],
    '2026-07' => [
        '001/2/001' => 55000,
        '001/2/002' => 4200,
        '001/2/003' => 15000,
        '001/2/006' => 15000,
        '001/2/014' => 5000,
        '001/3/001' => 20000,
        '001/1/001' => 8500,
        '001/10/001' => 10000, // Missions
        '001/7/001' => 8000,   // Training
    ],
];

foreach ($monthFills as $month => $amounts) {
    $byLineId = [];
    foreach ($amounts as $code => $amount) {
        if (!isset($byCode[$code])) {
            continue;
        }
        $byLineId[$byCode[$code]] = $amount;
    }
    if ($byLineId !== []) {
        FinanceBudgetService::saveMonthAmounts(2026, $month, $byLineId);
        echo "    · budget {$month} (" . count($byLineId) . " lines)\n";
    }
}

$counts = $db->query("
    SELECT 'bills' t, COUNT(*) c FROM finance_expense_arrears
    UNION SELECT 'weekly_expenses', COUNT(*) FROM finance_weekly_expenses
    UNION SELECT 'weekly_collections', COUNT(*) FROM finance_weekly_collections
    UNION SELECT 'sunday_sessions', COUNT(*) FROM finance_sunday_sessions
")->fetchAll(PDO::FETCH_KEY_PAIR);

echo "\nDone. Demo finance ready:\n";
foreach ($counts as $table => $count) {
    echo "  {$table}: {$count}\n";
}
echo "\nOpen Finance → Overview / Bills / Ledger / Reconciliation / Budget / Reports (July 2026).\n";
