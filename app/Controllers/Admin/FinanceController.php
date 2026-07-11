<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Services\FinanceBudgetService;
use App\Services\FinanceReconciliationService;
use App\Services\PdfService;
use App\Services\SettingsService;

class FinanceController
{
    public function index(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $tab = $_GET['tab'] ?? 'dashboard';
        $reportSub = $_GET['sub'] ?? 'reconciliation';
        if (!in_array($reportSub, ['reconciliation', 'statement', 'budget'], true)) {
            $reportSub = 'reconciliation';
        }
        if (in_array($tab, ['reconciliation', 'statement'], true)) {
            $tab = 'reports';
        }
        if (in_array($tab, ['arrears'], true)) {
            $tab = 'bills';
        }
        if (in_array($tab, ['weekly', 'collections'], true)) {
            $tab = 'ledger';
        }
        $ledgerSub = ($_GET['sub'] ?? '') === 'collections' ? 'collections' : 'expenses';
        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = $_GET['month'] ?? date('Y-m');
        $budgetYear = (int) ($_GET['budget_year'] ?? FinanceBudgetService::budgetYearForDate($month . '-01'));
        $statementView = $_GET['view'] ?? 'monthly';
        if (!in_array($statementView, ['weekly', 'monthly', 'annual'], true)) {
            $statementView = 'monthly';
        }
        $weekDate = $_GET['week_date'] ?? '';
        $statementSundays = FinanceReconciliationService::sundaysInMonth($month);
        if ($statementView === 'weekly') {
            if ($weekDate === '' && $statementSundays !== []) {
                $weekDate = $statementSundays[0];
            }
            if ($weekDate !== '' && !in_array($weekDate, $statementSundays, true)) {
                $weekDate = $statementSundays[0] ?? '';
            }
        }

        $churchConfig = $this->churchConfig();
        $paymentMethods = FinanceReconciliationService::PAYMENT_METHODS;
        $sundays = $statementSundays;

        $dashboard = [];
        $arrears = [];
        $arrearsTotals = ['due' => 0.0, 'paid' => 0.0, 'balance' => 0.0];
        $weekly = ['sundays' => $sundays, 'rows' => [], 'week_totals' => [], 'month_total' => 0.0];
        $weeklyCollections = [
            'sundays' => $sundays,
            'rows' => [],
            'week_totals' => [],
            'month_total' => 0.0,
            'method_totals' => array_fill_keys(array_keys($paymentMethods), 0.0),
        ];
        $reconciliation = [
            'weeks' => [],
            'month_expenses' => 0.0,
            'month_collections' => 0.0,
            'month_balance' => 0.0,
        ];
        $expenseCatalog = [];
        $statement = [];
        $budget = [];
        $hubConfig = ['year' => $year, 'paymentMethods' => $paymentMethods];

        switch ($tab) {
            case 'dashboard':
                $dashboard = FinanceReconciliationService::buildDashboard($year);
                try {
                    $dashboard['budget'] = FinanceBudgetService::currentMonthSnapshot(
                        FinanceBudgetService::budgetYearForDate($month . '-01'),
                        $month
                    );
                } catch (\Throwable) {
                    $dashboard['budget'] = [
                        'month' => $month,
                        'label' => date('M Y', strtotime($month . '-01')),
                        'budget_expenses' => 0.0,
                        'actual_expenses' => 0.0,
                        'expense_used_pct' => null,
                        'status' => 'neutral',
                        'status_label' => 'Unavailable',
                        'has_activity' => false,
                    ];
                }
                $hubConfig['dashboard'] = $dashboard;
                break;

            case 'bills':
                $arrears = FinanceReconciliationService::arrearsList($year);
                $arrearsTotals = [
                    'due' => array_sum(array_column($arrears, 'amount_due')),
                    'paid' => array_sum(array_column($arrears, 'amount_paid')),
                    'balance' => array_sum(array_column($arrears, 'balance_owing')),
                ];
                $expenseCatalog = FinanceReconciliationService::allExpenseCatalog();
                $hubConfig['arrears'] = array_values($arrears);
                $hubConfig['expenseGroups'] = array_values($expenseCatalog);
                break;

            case 'ledger':
                if ($ledgerSub === 'collections') {
                    $weeklyCollections = FinanceReconciliationService::weeklyCollectionsGrid($month);
                    $hubConfig['weeklyCollectionRows'] = array_values($weeklyCollections['rows']);
                    $hubConfig['weeklyCollectionSundays'] = $weeklyCollections['sundays'];
                } else {
                    $weekly = FinanceReconciliationService::weeklyGrid($month);
                    $expenseCatalog = FinanceReconciliationService::allExpenseCatalog();
                    $hubConfig['weeklyRows'] = array_values($weekly['rows']);
                    $hubConfig['weeklySundays'] = $weekly['sundays'];
                    $hubConfig['weeklyMonth'] = $month;
                    $hubConfig['expenseGroups'] = array_values($expenseCatalog);
                }
                break;

            case 'reports':
                if ($reportSub === 'statement') {
                    $statement = FinanceReconciliationService::buildStatement(
                        $statementView,
                        $year,
                        $month,
                        $weekDate ?: null
                    );
                } elseif ($reportSub === 'budget') {
                    FinanceBudgetService::ensureTables();
                    $budget = FinanceBudgetService::buildBudgetVsActual($budgetYear, $month);
                    $hubConfig['budget'] = $budget;
                } else {
                    $reconciliation = FinanceReconciliationService::monthReconciliation($month);
                    $hubConfig['reconciliation'] = $reconciliation;
                }
                break;
        }

        View::render('admin/finance/index', array_merge([
            'title' => 'Finance',
            'tab' => $tab,
            'ledgerSub' => $ledgerSub,
            'year' => $year,
            'month' => $month,
            'dashboard' => $dashboard,
            'arrears' => $arrears,
            'arrearsTotals' => $arrearsTotals,
            'weekly' => $weekly,
            'sundays' => $sundays,
            'weeklyCollections' => $weeklyCollections,
            'reconciliation' => $reconciliation,
            'paymentMethods' => $paymentMethods,
            'expenseCatalog' => $expenseCatalog,
            'statement' => $statement,
            'statementView' => $statementView,
            'statementWeekDate' => $weekDate,
            'statementSundays' => $statementSundays,
            'reportSub' => $reportSub,
            'budgetYear' => $budgetYear,
            'budget' => $budget,
            'hubConfig' => $hubConfig,
            'churchName' => $churchConfig['site_name'] ?? 'Church',
            'statementLogoUrl' => FinanceReconciliationService::statementLogoUrl(),
            'statementDisclaimer' => FinanceReconciliationService::STATEMENT_DISCLAIMER,
        ], $this->financePageAssets(['/css/admin-pagination.css'])), 'layouts/admin');
    }

    public function storeArrear(): void
    {
        Auth::requireAdmin();
        $year = (int) ($_POST['budget_year'] ?? date('Y'));
        try {
            FinanceReconciliationService::saveArrear($this->arrearPostData());
        } catch (\InvalidArgumentException) {
            // validation failed — return to form
        }
        View::redirect('/admin/finance?tab=bills&year=' . $year);
    }

    public function updateArrear(string $id): void
    {
        Auth::requireAdmin();
        $year = (int) ($_POST['budget_year'] ?? date('Y'));
        try {
            FinanceReconciliationService::saveArrear($this->arrearPostData(), (int) $id);
        } catch (\InvalidArgumentException) {
            // validation failed — return to form
        }
        View::redirect('/admin/finance?tab=bills&year=' . $year);
    }

    /** @return array<string, mixed> */
    private function arrearPostData(): array
    {
        $categoryRaw = $_POST['category_id'] ?? '';
        $categoryId = ($categoryRaw === '__new__' || $categoryRaw === '') ? 0 : (int) $categoryRaw;

        return [
            'department_id' => (int) ($_POST['department_id'] ?? 0),
            'category_id' => $categoryId,
            'new_category_label' => trim($_POST['new_category_label'] ?? ''),
            'expense_item' => trim($_POST['expense_item'] ?? ''),
            'month_incurred' => trim($_POST['month_incurred'] ?? ''),
            'amount_due' => $_POST['amount_due'] ?? 0,
            'amount_paid' => $_POST['amount_paid'] ?? 0,
            'date_paid' => $_POST['date_paid'] ?? '',
            'paid_by_ref' => trim($_POST['paid_by_ref'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'budget_year' => $_POST['budget_year'] ?? date('Y'),
        ];
    }

    public function deleteArrear(string $id): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::deleteArrear((int) $id);
        View::redirect('/admin/finance?tab=bills');
    }

    public function sundayEntry(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $month = $_GET['month'] ?? date('Y-m');
        $sundays = FinanceReconciliationService::sundaysInMonth($month);
        $weekDate = $_GET['week_date'] ?? FinanceReconciliationService::suggestedSundayDate($month);
        if ($weekDate !== '' && $sundays !== [] && !in_array($weekDate, $sundays, true)) {
            $weekDate = FinanceReconciliationService::suggestedSundayDate($month);
        }

        $session = $weekDate !== '' ? FinanceReconciliationService::sundaySessionData($weekDate) : ['collections' => [], 'expenses' => [], 'notes' => ''];
        $categories = FinanceReconciliationService::allWeeklyCategories();

        $sessionsByDate = FinanceReconciliationService::sundaySessionsForDates($sundays);

        View::render('admin/finance/sunday-entry', array_merge([
            'title' => 'Record Sunday',
            'month' => $month,
            'weekDate' => $weekDate,
            'sundays' => $sundays,
            'categories' => $categories,
            'collections' => $session['collections'],
            'expenses' => $session['expenses'],
            'notes' => $session['notes'],
            'sessionsByDate' => $sessionsByDate,
            'paymentMethods' => FinanceReconciliationService::PAYMENT_METHODS,
            'presets' => [
                'standard' => FinanceReconciliationService::SUNDAY_PRESET_STANDARD,
                'full' => FinanceReconciliationService::SUNDAY_PRESET_FULL,
            ],
        ], $this->financePageAssets()), 'layouts/admin');
    }

    public function storeSundayEntry(): void
    {
        Auth::requireAdmin();
        $weekDate = trim($_POST['week_date'] ?? '');
        if ($weekDate === '') {
            View::redirect('/admin/finance/sunday');
        }

        $collectionAmounts = [];
        foreach (array_keys(FinanceReconciliationService::PAYMENT_METHODS) as $method) {
            $collectionAmounts[$method] = $_POST['collections'][$method] ?? 0;
        }

        $expenseAmounts = [];
        foreach (FinanceReconciliationService::allWeeklyCategories() as $slug => $_meta) {
            $expenseAmounts[$slug] = $_POST['expenses'][$slug] ?? 0;
        }

        FinanceReconciliationService::saveSundayEntry(
            $weekDate,
            $collectionAmounts,
            $expenseAmounts,
            trim($_POST['notes'] ?? '')
        );

        $month = substr($weekDate, 0, 7);
        View::redirect('/admin/finance/sunday?month=' . urlencode($month) . '&week_date=' . urlencode($weekDate) . '&saved=1');
    }

    public function weeklyEntry(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $month = $_GET['month'] ?? date('Y-m');
        $sundays = FinanceReconciliationService::sundaysInMonth($month);
        $weekDate = $_GET['week_date'] ?? '';
        if ($weekDate === '' && $sundays !== []) {
            $weekDate = $sundays[0];
        }
        if ($weekDate !== '' && !in_array($weekDate, $sundays, true)) {
            $weekDate = $sundays[0] ?? $weekDate;
        }

        $categories = FinanceReconciliationService::allWeeklyCategories();
        $amounts = $weekDate !== '' ? FinanceReconciliationService::weeklyAmountsForDate($weekDate) : [];

        $amountsByDate = [];
        if ($sundays !== []) {
            $sessions = FinanceReconciliationService::sundaySessionsForDates($sundays);
            foreach ($sundays as $sun) {
                $amountsByDate[$sun] = $sessions[$sun]['expenses'] ?? [];
            }
        }

        View::render('admin/finance/weekly-entry', array_merge([
            'title' => 'Weekly Expenses',
            'month' => $month,
            'weekDate' => $weekDate,
            'sundays' => $sundays,
            'categories' => $categories,
            'amounts' => $amounts,
            'amountsByDate' => $amountsByDate,
        ], $this->financePageAssets()), 'layouts/admin');
    }

    public function storeWeeklyCategory(): void
    {
        Auth::requireAdmin();
        $month = $_POST['month'] ?? date('Y-m');
        try {
            [$departmentId, $expenseCategoryId, $label] = $this->resolveWeeklyCategoryPost();
            FinanceReconciliationService::addWeeklyCategory(
                $label,
                trim($_POST['hint'] ?? ''),
                $departmentId,
                $expenseCategoryId
            );
        } catch (\InvalidArgumentException) {
            // ignore validation errors
        }
        View::redirect('/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month));
    }

    public function updateWeeklyCategory(string $slug): void
    {
        Auth::requireAdmin();
        $month = $_POST['month'] ?? date('Y-m');
        try {
            [$departmentId, $expenseCategoryId, $label] = $this->resolveWeeklyCategoryPost();
            FinanceReconciliationService::updateWeeklyCategory(
                $slug,
                $label,
                trim($_POST['hint'] ?? ''),
                $departmentId,
                $expenseCategoryId
            );
        } catch (\InvalidArgumentException) {
            // ignore validation errors
        }
        View::redirect('/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month));
    }

    /** @return array{0: int, 1: int|null, 2: string} */
    private function resolveWeeklyCategoryPost(): array
    {
        $departmentId = (int) ($_POST['department_id'] ?? 0);
        $expenseCategoryRaw = $_POST['expense_category_id'] ?? '';
        $expenseCategoryId = ($expenseCategoryRaw === '__new__' || $expenseCategoryRaw === '') ? 0 : (int) $expenseCategoryRaw;
        $newItemLabel = trim($_POST['new_category_item_label'] ?? '');
        $label = trim($_POST['label'] ?? '');

        if ($expenseCategoryId <= 0 && $newItemLabel !== '' && $departmentId > 0) {
            $expenseCategoryId = FinanceReconciliationService::addExpenseCategory($departmentId, $newItemLabel);
            if ($label === '') {
                $label = $newItemLabel;
            }
        }

        if ($label === '' && $expenseCategoryId > 0) {
            $cat = FinanceReconciliationService::findExpenseCategory($expenseCategoryId);
            $label = (string) ($cat['label'] ?? '');
        }

        if ($label === '') {
            throw new \InvalidArgumentException('Label is required.');
        }

        return [$departmentId, $expenseCategoryId > 0 ? $expenseCategoryId : null, $label];
    }

    public function deleteWeeklyCategory(string $slug): void
    {
        Auth::requireAdmin();
        $month = $_POST['month'] ?? date('Y-m');
        FinanceReconciliationService::deleteWeeklyCategory($slug);
        View::redirect('/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month));
    }

    public function storeWeekly(): void
    {
        Auth::requireAdmin();
        $weekDate = $_POST['week_date'] ?? '';
        $amounts = [];
        foreach (FinanceReconciliationService::allWeeklyCategories() as $slug => $_meta) {
            $amounts[$slug] = $_POST['amounts'][$slug] ?? 0;
        }
        FinanceReconciliationService::saveWeeklyEntry($weekDate, $amounts);
        $month = substr($weekDate, 0, 7);
        View::redirect('/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month));
    }

    public function weeklyCollectionsEntry(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $month = $_GET['month'] ?? date('Y-m');
        $sundays = FinanceReconciliationService::sundaysInMonth($month);
        $weekDate = $_GET['week_date'] ?? '';
        if ($weekDate === '' && $sundays !== []) {
            $weekDate = $sundays[0];
        }
        if ($weekDate !== '' && !in_array($weekDate, $sundays, true)) {
            $weekDate = $sundays[0] ?? $weekDate;
        }

        $amounts = $weekDate !== '' ? FinanceReconciliationService::weeklyCollectionAmountsForDate($weekDate) : [];

        $amountsByDate = [];
        foreach ($sundays as $sun) {
            $amountsByDate[$sun] = FinanceReconciliationService::weeklyCollectionAmountsForDate($sun);
        }

        View::render('admin/finance/collections-weekly-entry', array_merge([
            'title' => 'Weekly Collections',
            'month' => $month,
            'weekDate' => $weekDate,
            'sundays' => $sundays,
            'paymentMethods' => FinanceReconciliationService::PAYMENT_METHODS,
            'amounts' => $amounts,
            'amountsByDate' => $amountsByDate,
        ], $this->financePageAssets()), 'layouts/admin');
    }

    public function storeWeeklyCollections(): void
    {
        Auth::requireAdmin();
        $weekDate = $_POST['week_date'] ?? '';
        $amounts = [];
        foreach (array_keys(FinanceReconciliationService::PAYMENT_METHODS) as $method) {
            $amounts[$method] = $_POST['amounts'][$method] ?? 0;
        }
        FinanceReconciliationService::saveWeeklyCollectionEntry($weekDate, $amounts);
        $month = substr($weekDate, 0, 7);
        $year = (int) substr($weekDate, 0, 4);
        View::redirect('/admin/finance?tab=ledger&sub=collections&year=' . $year . '&month=' . urlencode($month));
    }

    public function storeCollection(): void
    {
        Auth::requireAdmin();
        $date = $_POST['collection_date'] ?? date('Y-m-d');
        FinanceReconciliationService::saveCollection([
            'collection_date' => $date,
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'amount' => $_POST['amount'] ?? 0,
            'reference' => trim($_POST['reference'] ?? ''),
            'fund_type' => trim($_POST['fund_type'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'budget_year' => $_POST['budget_year'] ?? date('Y'),
        ]);
        $month = substr($date, 0, 7);
        View::redirect('/admin/finance?tab=reports&sub=reconciliation&year=' . (int) ($_POST['budget_year'] ?? date('Y')) . '&month=' . urlencode($month));
    }

    public function deleteCollection(string $id): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::deleteCollection((int) $id);
        View::redirect('/admin/finance?tab=reports&sub=reconciliation');
    }

    public function downloadStatementPdf(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $statement = $this->resolveStatementForExport();
        $churchName = SettingsService::churchName() ?: ($this->churchConfig()['site_name'] ?? 'Church');
        $churchAddress = SettingsService::churchAddress();

        $pdf = new PdfService();
        $content = $pdf->generateFinanceStatement($statement, $churchName, $churchAddress);

        $filename = FinanceReconciliationService::statementExportFilename($statement, 'pdf');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit;
    }

    public function downloadStatementCsv(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $statement = $this->resolveStatementForExport();
        $churchName = SettingsService::churchName() ?: ($this->churchConfig()['site_name'] ?? 'Church');
        $csv = FinanceReconciliationService::statementToCsv($statement, $churchName);

        $filename = FinanceReconciliationService::statementExportFilename($statement, 'csv');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv;
        exit;
    }

    /** @return array<string, mixed> */
    private function resolveStatementForExport(): array
    {
        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = $_GET['month'] ?? date('Y-m');
        $statementView = $_GET['view'] ?? 'monthly';
        if (!in_array($statementView, ['weekly', 'monthly', 'annual'], true)) {
            $statementView = 'monthly';
        }

        $weekDate = $_GET['week_date'] ?? '';
        $statementSundays = FinanceReconciliationService::sundaysInMonth($month);
        if ($statementView === 'weekly') {
            if ($weekDate === '' && $statementSundays !== []) {
                $weekDate = $statementSundays[0];
            }
            if ($weekDate !== '' && !in_array($weekDate, $statementSundays, true)) {
                $weekDate = $statementSundays[0] ?? '';
            }
        }

        return FinanceReconciliationService::buildStatement($statementView, $year, $month, $weekDate ?: null);
    }

    /** @return array{pageStyles: list<string>, pageScripts: list<string>} */
    private function financePageAssets(array $extraStyles = []): array
    {
        return [
            'pageStyles' => array_merge(['/css/admin-finance.css'], $extraStyles),
            'pageScripts' => ['/js/admin-finance.js'],
        ];
    }

    /** @return array<string, mixed> */
    private function churchConfig(): array
    {
        $configFile = dirname(__DIR__, 3) . '/config/church-site.php';

        return is_file($configFile) ? require $configFile : [];
    }
}
