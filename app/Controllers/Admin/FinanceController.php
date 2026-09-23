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

        $tab = strtolower((string) ($_GET['tab'] ?? 'dashboard'));
        $rawSub = strtolower((string) ($_GET['sub'] ?? ''));
        $reportSub = $rawSub;

        // Legacy aliases → Overview · Sundays · Bills · Budget · Reports
        if ($tab === 'arrears') {
            $tab = 'bills';
        }
        if (in_array($tab, ['weekly', 'collections'], true)) {
            $tab = 'ledger';
        }
        if ($tab === 'statement') {
            $tab = 'reports';
            if ($reportSub === '') {
                $reportSub = 'statement';
            }
        }
        // Reconciliation folded into Overview
        if ($tab === 'reconciliation' || ($tab === 'reports' && $reportSub === 'reconciliation')) {
            $tab = 'dashboard';
            $reportSub = '';
        }
        // Legacy Reports → Budget now has its own sidebar item
        if ($tab === 'reports' && $reportSub === 'budget') {
            $tab = 'budget';
            $reportSub = '';
        }

        if ($tab === 'reports') {
            if (!in_array($reportSub, ['statement', 'position'], true)) {
                $reportSub = 'statement';
            }
        } else {
            $reportSub = '';
        }

        $ledgerSub = $rawSub === 'collections' ? 'collections' : 'expenses';
        $financeYears = FinanceReconciliationService::availableLedgerYears();
        $yearParam = isset($_GET['year']) ? (int) $_GET['year'] : null;
        if ($yearParam !== null && $yearParam >= 2000 && $yearParam <= 2100) {
            $year = $yearParam;
        } elseif ($tab === 'reports' && $reportSub === 'position') {
            // Open I&E on the newest year that already has Sunday activity
            $year = FinanceReconciliationService::latestLedgerYear();
        } else {
            $year = (int) date('Y');
        }
        if (!in_array($year, $financeYears, true)) {
            $financeYears[] = $year;
            rsort($financeYears, SORT_NUMERIC);
        }
        $month = $_GET['month'] ?? date('Y-m');
        // If browsing a future/past ledger year with no month chosen, land on a month in that year
        if (!isset($_GET['month']) && $year !== (int) date('Y')) {
            $month = sprintf('%04d-01', $year);
        }
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
        $position = [];
        $budget = [];
        $recordSunday = isset($_GET['record']);
        $recordDate = trim((string) ($_GET['record_date'] ?? ''));
        $sundayForm = $this->sundayFormPayload($month, $recordDate !== '' ? $recordDate : null);
        $presets = $sundayForm['sundayPresets'];
        $expenseCatalog = FinanceReconciliationService::allExpenseCatalog();
        $hubConfig = [
            'year' => $year,
            'month' => $month,
            'financeYears' => array_values($financeYears),
            'paymentMethods' => $paymentMethods,
            'openSundayModal' => $recordSunday,
            'financeTab' => $tab,
            'weeklyMonth' => $month,
            'weeklySundays' => $sundays,
            'expenseGroups' => array_values($expenseCatalog),
            'sundaySessionsByDate' => $sundayForm['sundaySessionsByDate'],
            'sundayFormBase' => [
                'weekDate' => $sundayForm['sundayWeekDate'],
                'methods' => array_keys($sundayForm['sundayPaymentMethods']),
                'categories' => array_keys($sundayForm['sundayCategories']),
                'presets' => $presets,
                'presetTotals' => [
                    'standard' => array_sum($presets['standard'] ?? []),
                    'full' => array_sum($presets['full'] ?? []),
                ],
            ],
        ];
        if ($tab === 'ledger') {
            $hubConfig['ledgerSub'] = $ledgerSub;
        }

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
                // Month pulse (former Reconciliation tab) lives on Overview
                $reconciliation = FinanceReconciliationService::monthReconciliation($month);
                $hubConfig['dashboard'] = $dashboard;
                $hubConfig['reconciliation'] = $reconciliation;
                $hubConfig['weeklyMonth'] = $month;
                break;

            case 'bills':
                $arrears = FinanceReconciliationService::arrearsList($year);
                $arrearsTotals = [
                    'due' => array_sum(array_column($arrears, 'amount_due')),
                    'paid' => array_sum(array_column($arrears, 'amount_paid')),
                    'balance' => array_sum(array_column($arrears, 'balance_owing')),
                ];
                $hubConfig['arrears'] = array_values($arrears);
                $hubConfig['arrearsTotals'] = $arrearsTotals;
                break;

            case 'ledger':
                $weekly = FinanceReconciliationService::weeklyGrid($month);
                $weeklyCollections = FinanceReconciliationService::weeklyCollectionsGrid($month);
                $hubConfig['weeklyRows'] = array_values($weekly['rows']);
                $hubConfig['weeklySundays'] = $weekly['sundays'];
                $hubConfig['weeklyCollectionRows'] = array_values($weeklyCollections['rows']);
                $hubConfig['weeklyCollectionSundays'] = $weeklyCollections['sundays'];
                $hubConfig['weeklyMonth'] = $month;
                $hubConfig['ledgerSub'] = $ledgerSub;
                break;

            case 'budget':
                FinanceBudgetService::ensureTables();
                $budget = FinanceBudgetService::buildBudgetVsActual($budgetYear, $month);
                $hubConfig['weeklyMonth'] = $month;
                $hubConfig['budget'] = $budget;
                $hubConfig['budgetYear'] = $budgetYear;
                $hubConfig['budgetEditLines'] = FinanceBudgetService::linesForEdit($budgetYear, $month);
                break;

            case 'reports':
                $hubConfig['reportSub'] = $reportSub;
                $hubConfig['weeklyMonth'] = $month;
                if ($reportSub === 'position') {
                    $position = FinanceReconciliationService::buildConsolidatedPosition($year);
                    $hubConfig['positionYear'] = $year;
                } else {
                    $statement = FinanceReconciliationService::buildStatement(
                        $statementView,
                        $year,
                        $month,
                        $weekDate ?: null
                    );
                    $hubConfig['statementView'] = $statementView;
                    $hubConfig['statementWeekDate'] = $weekDate;
                    $hubConfig['statementSundays'] = $statementSundays;
                }
                break;
        }

        $pageTitles = [
            'dashboard' => 'Finance overview',
            'bills' => 'Bills',
            'ledger' => 'Sundays',
            'budget' => !empty($_GET['edit']) ? 'Set Budget' : 'Budget',
            'reports' => 'Reports',
        ];

        View::render('admin/finance/index', array_merge([
            'title' => $pageTitles[$tab] ?? 'Finance',
            'tab' => $tab,
            'ledgerSub' => $ledgerSub,
            'year' => $year,
            'financeYears' => $financeYears,
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
            'position' => $position,
            'statementView' => $statementView,
            'statementWeekDate' => $weekDate,
            'statementSundays' => $statementSundays,
            'reportSub' => $reportSub,
            'budgetYear' => $budgetYear,
            'budget' => $budget,
            'budgetEditMode' => $tab === 'budget' && isset($_GET['edit']),
            'hubConfig' => $hubConfig,
            'churchName' => SettingsService::churchName() ?: ($churchConfig['site_name'] ?? 'Church'),
            'statementLogoUrl' => FinanceReconciliationService::statementLogoUrl(),
            'statementDisclaimer' => FinanceReconciliationService::STATEMENT_DISCLAIMER,
        ], $sundayForm, $this->financePageAssets(['/css/admin-pagination.css'])), 'layouts/admin');
    }

    /**
     * Shared payload for the Record Sunday modal (and legacy /sunday redirect).
     *
     * @return array<string, mixed>
     */
    private function sundayFormPayload(string $month, ?string $weekDate = null): array
    {
        $sundays = FinanceReconciliationService::sundaysInMonth($month);
        if ($weekDate === null || $weekDate === '') {
            $weekDate = FinanceReconciliationService::suggestedSundayDate($month);
        }
        if ($weekDate !== '' && $sundays !== [] && !in_array($weekDate, $sundays, true)) {
            $weekDate = FinanceReconciliationService::suggestedSundayDate($month);
        }

        $session = $weekDate !== ''
            ? FinanceReconciliationService::sundaySessionData($weekDate)
            : ['collections' => [], 'expenses' => [], 'notes' => ''];
        $categories = FinanceReconciliationService::allWeeklyCategories();
        $sessionsByDate = FinanceReconciliationService::sundaySessionsForDates($sundays);

        return [
            'sundayMonth' => $month,
            'sundayWeekDate' => $weekDate,
            'sundaySundays' => $sundays,
            'sundayCategories' => $categories,
            'sundayCollections' => $session['collections'],
            'sundayExpenses' => $session['expenses'],
            'sundayNotes' => $session['notes'],
            'sundaySessionsByDate' => $sessionsByDate,
            'sundayPresets' => [
                'standard' => FinanceReconciliationService::SUNDAY_PRESET_STANDARD,
                'full' => FinanceReconciliationService::SUNDAY_PRESET_FULL,
            ],
            'sundayPaymentMethods' => FinanceReconciliationService::PAYMENT_METHODS,
        ];
    }

    public function storeArrear(): void
    {
        Auth::requireAdmin();
        $year = (int) ($_POST['budget_year'] ?? date('Y'));
        try {
            FinanceReconciliationService::saveArrear($this->arrearPostData());
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=bills&year=' . $year,
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not save bill.'],
                422
            );
        }
        $this->respondMutation(
            '/admin/finance?tab=bills&year=' . $year,
            array_merge(['ok' => true, 'message' => 'Bill saved.', 'year' => $year], $this->billsAjaxPayload($year))
        );
    }

    public function updateArrear(string $id): void
    {
        Auth::requireAdmin();
        $year = (int) ($_POST['budget_year'] ?? date('Y'));
        try {
            FinanceReconciliationService::saveArrear($this->arrearPostData(), (int) $id);
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=bills&year=' . $year,
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not update bill.'],
                422
            );
        }
        $this->respondMutation(
            '/admin/finance?tab=bills&year=' . $year,
            array_merge(['ok' => true, 'message' => 'Bill updated.', 'year' => $year], $this->billsAjaxPayload($year))
        );
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
            'month_incurred' => FinanceReconciliationService::normalizeMonthIncurred(
                trim($_POST['month_incurred'] ?? ''),
                (int) ($_POST['budget_year'] ?? date('Y'))
            ),
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
        $year = (int) ($_POST['budget_year'] ?? $_GET['year'] ?? date('Y'));
        FinanceReconciliationService::deleteArrear((int) $id);
        $this->respondMutation(
            '/admin/finance?tab=bills&year=' . $year,
            array_merge(['ok' => true, 'message' => 'Bill deleted.', 'year' => $year], $this->billsAjaxPayload($year))
        );
    }

    public function data(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $month = (string) ($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $year = (int) ($_GET['year'] ?? substr($month, 0, 4));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

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

        $payload = array_merge(
            [
                'ok' => true,
                'year' => $year,
                'month' => $month,
                'dashboard' => $dashboard,
                'reconciliation' => FinanceReconciliationService::monthReconciliation($month),
            ],
            $this->ledgerAjaxPayload($month),
            $this->billsAjaxPayload($year)
        );

        View::json($payload);
    }

    public function sundayEntry(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $month = (string) ($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $weekDate = trim((string) ($_GET['week_date'] ?? ''));
        $panel = trim((string) ($_GET['panel'] ?? ''));
        if (!in_array($panel, ['collections', 'expenses'], true)) {
            $panel = '';
        }
        $returnTab = trim((string) ($_GET['return_tab'] ?? 'ledger'));
        if ($returnTab === 'reconciliation') {
            $returnTab = 'dashboard';
        }
        if (!in_array($returnTab, ['dashboard', 'bills', 'ledger', 'budget', 'reports'], true)) {
            $returnTab = 'ledger';
        }
        $returnSub = trim((string) ($_GET['return_sub'] ?? ''));
        if ($returnTab === 'reports') {
            if (!in_array($returnSub, ['statement', 'position'], true)) {
                $returnSub = 'statement';
            }
        } elseif (!in_array($returnSub, ['expenses', 'collections'], true)) {
            $returnSub = $panel !== '' ? $panel : '';
        }

        $payload = $this->sundayFormPayload($month, $weekDate !== '' ? $weekDate : null);

        View::render('admin/finance/sunday-entry', array_merge([
            'title' => 'Record Sunday',
            'month' => $payload['sundayMonth'],
            'weekDate' => $payload['sundayWeekDate'],
            'sundays' => $payload['sundaySundays'],
            'categories' => $payload['sundayCategories'],
            'sessionsByDate' => $payload['sundaySessionsByDate'],
            'presets' => $payload['sundayPresets'],
            'paymentMethods' => $payload['sundayPaymentMethods'],
            'panel' => $panel,
            'returnTab' => $returnTab,
            'returnSub' => $returnSub,
        ], $this->financePageAssets()), 'layouts/admin');
    }

    public function storeSundayEntry(): void
    {
        Auth::requireAdmin();
        $weekDate = trim($_POST['week_date'] ?? '');
        $returnTab = trim((string) ($_POST['return_tab'] ?? 'ledger'));
        $returnSub = trim((string) ($_POST['return_sub'] ?? ''));
        if ($returnTab === 'reconciliation') {
            $returnTab = 'dashboard';
        }
        if (!in_array($returnTab, ['dashboard', 'bills', 'ledger', 'budget', 'reports'], true)) {
            $returnTab = 'ledger';
        }

        if ($weekDate === '') {
            $this->respondMutation(
                '/admin/finance?tab=' . urlencode($returnTab) . '&record=1',
                ['ok' => false, 'message' => 'Choose a Sunday date.'],
                422
            );
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
        $qs = [
            'tab' => $returnTab,
            'month' => $month,
            'saved' => '1',
        ];
        if ($returnTab === 'reports') {
            $qs['sub'] = in_array($returnSub, ['statement', 'position'], true) ? $returnSub : 'statement';
        } elseif ($returnSub !== '' && in_array($returnSub, ['expenses', 'collections'], true)) {
            $qs['sub'] = $returnSub;
        }
        $this->respondMutation(
            '/admin/finance?' . http_build_query($qs),
            array_merge(['ok' => true, 'message' => 'Sunday saved.'], $this->ledgerAjaxPayload($month))
        );
    }

    public function weeklyEntry(): void
    {
        Auth::requireAdmin();
        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $month = date('Y-m');
        }
        $weekDate = trim((string) ($_GET['week_date'] ?? ''));
        $qs = [
            'month' => $month,
            'panel' => 'expenses',
            'return_tab' => 'ledger',
            'return_sub' => 'expenses',
        ];
        if ($weekDate !== '') {
            $qs['week_date'] = $weekDate;
        }
        View::redirect('/admin/finance/sunday?' . http_build_query($qs));
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
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not add category.'],
                422
            );
        }
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Category added.'], $this->ledgerAjaxPayload($month))
        );
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
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not update category.'],
                422
            );
        }
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Category updated.'], $this->ledgerAjaxPayload($month))
        );
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
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Category deleted.'], $this->ledgerAjaxPayload($month))
        );
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
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Expenses saved.'], $this->ledgerAjaxPayload($month))
        );
    }

    public function saveWeeklyExpenseCell(): void
    {
        Auth::requireAdmin();
        $weekDate = trim((string) ($_POST['week_date'] ?? ''));
        $slug = trim((string) ($_POST['category_slug'] ?? ''));
        $amount = (float) ($_POST['amount'] ?? 0);
        $month = trim((string) ($_POST['month'] ?? substr($weekDate, 0, 7)));
        try {
            FinanceReconciliationService::saveWeeklyExpenseAmount($weekDate, $slug, $amount);
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not save amount.'],
                422
            );
        }
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=expenses&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Amount updated.'], $this->ledgerAjaxPayload($month !== '' ? $month : substr($weekDate, 0, 7)))
        );
    }

    public function weeklyCollectionsEntry(): void
    {
        Auth::requireAdmin();
        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $month = date('Y-m');
        }
        $weekDate = trim((string) ($_GET['week_date'] ?? ''));
        $qs = [
            'month' => $month,
            'panel' => 'collections',
            'return_tab' => 'ledger',
            'return_sub' => 'collections',
        ];
        if ($weekDate !== '') {
            $qs['week_date'] = $weekDate;
        }
        View::redirect('/admin/finance/sunday?' . http_build_query($qs));
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
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=collections&year=' . $year . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Collections saved.'], $this->ledgerAjaxPayload($month))
        );
    }

    public function saveWeeklyCollectionCell(): void
    {
        Auth::requireAdmin();
        $weekDate = trim((string) ($_POST['week_date'] ?? ''));
        $method = trim((string) ($_POST['method'] ?? ''));
        $amount = (float) ($_POST['amount'] ?? 0);
        $month = trim((string) ($_POST['month'] ?? substr($weekDate, 0, 7)));
        try {
            FinanceReconciliationService::saveWeeklyCollectionAmount($weekDate, $method, $amount);
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=ledger&sub=collections&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not save amount.'],
                422
            );
        }
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=collections&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Amount updated.'], $this->ledgerAjaxPayload($month !== '' ? $month : substr($weekDate, 0, 7)))
        );
    }

    public function updateWeeklyCollectionMethod(string $method): void
    {
        Auth::requireAdmin();
        $month = trim((string) ($_POST['month'] ?? date('Y-m')));
        $amounts = $_POST['amounts'] ?? [];
        if (!is_array($amounts)) {
            $amounts = [];
        }
        try {
            FinanceReconciliationService::saveCollectionMethodMonth($method, $month, $amounts);
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=ledger&sub=collections&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not update amounts.'],
                422
            );
        }
        $year = (int) substr($month, 0, 4);
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=collections&year=' . $year . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Amounts updated.'], $this->ledgerAjaxPayload($month))
        );
    }

    public function clearWeeklyCollectionMethod(string $method): void
    {
        Auth::requireAdmin();
        $month = trim((string) ($_POST['month'] ?? date('Y-m')));
        try {
            FinanceReconciliationService::clearCollectionMethodMonth($method, $month);
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=ledger&sub=collections&year=' . (int) substr($month, 0, 4) . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not clear amounts.'],
                422
            );
        }
        $year = (int) substr($month, 0, 4);
        $this->respondMutation(
            '/admin/finance?tab=ledger&sub=collections&year=' . $year . '&month=' . urlencode($month),
            array_merge(['ok' => true, 'message' => 'Amounts cleared.'], $this->ledgerAjaxPayload($month))
        );
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
        View::redirect('/admin/finance?tab=dashboard&year=' . (int) ($_POST['budget_year'] ?? date('Y')) . '&month=' . urlencode($month));
    }

    public function deleteCollection(string $id): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::deleteCollection((int) $id);
        View::redirect('/admin/finance?tab=dashboard');
    }

    public function storeBudgetMonth(): void
    {
        Auth::requireAdmin();
        FinanceBudgetService::ensureTables();

        $budgetYear = (int) ($_POST['budget_year'] ?? date('Y'));
        $month = trim((string) ($_POST['month'] ?? date('Y-m')));
        $amounts = $_POST['amounts'] ?? [];
        if (!is_array($amounts)) {
            $amounts = [];
        }

        try {
            FinanceBudgetService::saveMonthAmounts($budgetYear, $month, $amounts);
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=budget&budget_year=' . $budgetYear . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not save budget.'],
                422
            );
        }

        $this->respondMutation(
            '/admin/finance?tab=budget&budget_year=' . $budgetYear . '&month=' . urlencode($month),
            array_merge(
                ['ok' => true, 'message' => 'Budget saved. Tracking will use these figures.'],
                $this->budgetAjaxPayload($budgetYear, $month)
            )
        );
    }

    public function storeBudgetLine(): void
    {
        Auth::requireAdmin();
        FinanceBudgetService::ensureTables();

        $budgetYear = (int) ($_POST['budget_year'] ?? date('Y'));
        $month = trim((string) ($_POST['month'] ?? date('Y-m')));

        try {
            $id = FinanceBudgetService::addLine($budgetYear, [
                'line_type' => $_POST['line_type'] ?? 'expense',
                'section' => $_POST['section'] ?? '',
                'label' => $_POST['label'] ?? '',
                'account_code' => $_POST['account_code'] ?? '',
            ]);
            $amount = round((float) ($_POST['amount'] ?? 0), 2);
            if ($amount > 0) {
                FinanceBudgetService::saveMonthAmounts($budgetYear, $month, [$id => $amount]);
            }
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=budget&budget_year=' . $budgetYear . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not add budget line.'],
                422
            );
        }

        $this->respondMutation(
            '/admin/finance?tab=budget&budget_year=' . $budgetYear . '&month=' . urlencode($month),
            array_merge(
                ['ok' => true, 'message' => 'Budget line added.'],
                $this->budgetAjaxPayload($budgetYear, $month)
            )
        );
    }

    public function deleteBudgetLine(string $id): void
    {
        Auth::requireAdmin();
        FinanceBudgetService::ensureTables();

        $budgetYear = (int) ($_POST['budget_year'] ?? date('Y'));
        $month = trim((string) ($_POST['month'] ?? date('Y-m')));
        $lineId = (int) $id;

        try {
            FinanceBudgetService::deleteLine($budgetYear, $lineId);
        } catch (\InvalidArgumentException $e) {
            $this->respondMutation(
                '/admin/finance?tab=budget&budget_year=' . $budgetYear . '&month=' . urlencode($month),
                ['ok' => false, 'message' => $e->getMessage() ?: 'Could not delete budget line.'],
                422
            );
        }

        $this->respondMutation(
            '/admin/finance?tab=budget&budget_year=' . $budgetYear . '&month=' . urlencode($month),
            array_merge(
                ['ok' => true, 'message' => 'Budget line deleted.'],
                $this->budgetAjaxPayload($budgetYear, $month)
            )
        );
    }

    public function statementData(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = (string) ($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $statementView = (string) ($_GET['view'] ?? 'monthly');
        if (!in_array($statementView, ['weekly', 'monthly', 'annual'], true)) {
            $statementView = 'monthly';
        }

        $weekDate = trim((string) ($_GET['week_date'] ?? ''));
        $statementSundays = FinanceReconciliationService::sundaysInMonth($month);
        if ($statementView === 'weekly') {
            if ($weekDate === '' && $statementSundays !== []) {
                $weekDate = $statementSundays[0];
            }
            if ($weekDate !== '' && !in_array($weekDate, $statementSundays, true)) {
                $weekDate = $statementSundays[0] ?? '';
            }
        } else {
            $weekDate = '';
        }

        $statement = FinanceReconciliationService::buildStatement(
            $statementView,
            $year,
            $month,
            $weekDate !== '' ? $weekDate : null
        );

        $churchConfig = $this->churchConfig();
        $churchName = SettingsService::churchName() ?: ($churchConfig['site_name'] ?? 'Church');
        $generatedAt = date('j F Y, g:i a');
        $refId = 'STMT-' . strtoupper($statement['view'] ?? 'M') . '-' . ($statement['year'] ?? $year) . '-' . date('YmdHis');

        ob_start();
        $statementLogoUrl = FinanceReconciliationService::statementLogoUrl();
        $statementDisclaimer = FinanceReconciliationService::STATEMENT_DISCLAIMER;
        require dirname(__DIR__, 3) . '/views/admin/finance/_statement-document.php';
        $html = (string) ob_get_clean();

        View::json([
            'ok' => true,
            'view' => $statementView,
            'year' => $year,
            'month' => $month,
            'week_date' => $weekDate,
            'sundays' => $statementSundays,
            'html' => $html,
            'period_label' => $statement['period_label'] ?? '',
        ]);
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

    public function positionData(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $year = isset($_GET['year'])
            ? (int) $_GET['year']
            : FinanceReconciliationService::latestLedgerYear();
        if ($year < 2000 || $year > 2100) {
            $year = FinanceReconciliationService::latestLedgerYear();
        }
        $position = FinanceReconciliationService::buildConsolidatedPosition($year);

        $churchConfig = $this->churchConfig();
        $churchName = SettingsService::churchName() ?: ($churchConfig['site_name'] ?? 'Church');
        $generatedAt = date('j F Y, g:i a');
        $refId = 'POS-' . ($position['year'] ?? $year) . '-' . date('YmdHis');

        ob_start();
        $statementLogoUrl = FinanceReconciliationService::statementLogoUrl();
        $statementDisclaimer = FinanceReconciliationService::STATEMENT_DISCLAIMER;
        require dirname(__DIR__, 3) . '/views/admin/finance/_position-document.php';
        $html = (string) ob_get_clean();

        View::json([
            'ok' => true,
            'year' => $year,
            'financeYears' => FinanceReconciliationService::availableLedgerYears(),
            'html' => $html,
            'period_label' => $position['period_label'] ?? '',
        ]);
    }

    public function downloadPositionPdf(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $year = (int) ($_GET['year'] ?? date('Y'));
        $position = FinanceReconciliationService::buildConsolidatedPosition($year);
        $churchName = SettingsService::churchName() ?: ($this->churchConfig()['site_name'] ?? 'Church');
        $churchAddress = SettingsService::churchAddress();

        $pdf = new PdfService();
        $content = $pdf->generateConsolidatedPosition($position, $churchName, $churchAddress);

        $filename = FinanceReconciliationService::consolidatedPositionExportFilename($position, 'pdf');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit;
    }

    public function downloadPositionCsv(): void
    {
        Auth::requireAdmin();
        FinanceReconciliationService::ensureTables();

        $year = (int) ($_GET['year'] ?? date('Y'));
        $position = FinanceReconciliationService::buildConsolidatedPosition($year);
        $churchName = SettingsService::churchName() ?: ($this->churchConfig()['site_name'] ?? 'Church');
        $csv = FinanceReconciliationService::consolidatedPositionToCsv($position, $churchName);

        $filename = FinanceReconciliationService::consolidatedPositionExportFilename($position, 'csv');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv;
        exit;
    }

    /** @return array{pageStyles: list<string>, pageScripts: list<string>} */
    private function financePageAssets(array $extraStyles = []): array
    {
        return [
            'pageStyles' => array_merge(['/css/admin-finance.css'], $extraStyles),
            'pageScripts' => [
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                '/js/admin-finance.js',
            ],
        ];
    }

    /**
     * Fresh ledger grids for AJAX clients after a mutation.
     *
     * @return array<string, mixed>
     */
    private function ledgerAjaxPayload(string $month): array
    {
        $weekly = FinanceReconciliationService::weeklyGrid($month);
        $collections = FinanceReconciliationService::weeklyCollectionsGrid($month);
        $sundays = $weekly['sundays'] !== [] ? $weekly['sundays'] : $collections['sundays'];
        $expenseCatalog = FinanceReconciliationService::allExpenseCatalog();
        $presets = [
            'standard' => FinanceReconciliationService::SUNDAY_PRESET_STANDARD,
            'full' => FinanceReconciliationService::SUNDAY_PRESET_FULL,
        ];

        return [
            'month' => $month,
            'weeklyMonth' => $month,
            'weeklyRows' => array_values($weekly['rows']),
            'weeklySundays' => $weekly['sundays'],
            'weeklyCollectionRows' => array_values($collections['rows']),
            'weeklyCollectionSundays' => $collections['sundays'],
            'expenseGroups' => array_values($expenseCatalog),
            'sundaySessionsByDate' => FinanceReconciliationService::sundaySessionsForDates($sundays),
            'sundayFormBase' => [
                'weekDate' => FinanceReconciliationService::suggestedSundayDate($month),
                'methods' => array_keys(FinanceReconciliationService::PAYMENT_METHODS),
                'categories' => array_keys(FinanceReconciliationService::allWeeklyCategories()),
                'presets' => $presets,
                'presetTotals' => [
                    'standard' => array_sum($presets['standard']),
                    'full' => array_sum($presets['full']),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function budgetAjaxPayload(int $budgetYear, string $month): array
    {
        $budget = FinanceBudgetService::buildBudgetVsActual($budgetYear, $month);

        return [
            'budgetYear' => $budgetYear,
            'month' => $month,
            'weeklyMonth' => $month,
            'budget' => $budget,
            'budgetEditLines' => FinanceBudgetService::linesForEdit($budgetYear, $month),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function billsAjaxPayload(int $year): array
    {
        $arrears = FinanceReconciliationService::arrearsList($year);
        $expenseCatalog = FinanceReconciliationService::allExpenseCatalog();

        return [
            'arrears' => array_values($arrears),
            'expenseGroups' => array_values($expenseCatalog),
            'arrearsTotals' => [
                'due' => array_sum(array_column($arrears, 'amount_due')),
                'paid' => array_sum(array_column($arrears, 'amount_paid')),
                'balance' => array_sum(array_column($arrears, 'balance_owing')),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $json
     */
    private function respondMutation(string $redirectUrl, array $json, int $status = 200): void
    {
        if (View::wantsJson()) {
            View::json($json, $status);
        }
        View::redirect($redirectUrl);
    }

    /** @return array<string, mixed> */
    private function churchConfig(): array
    {
        $configFile = dirname(__DIR__, 3) . '/config/church-site.php';

        return is_file($configFile) ? require $configFile : [];
    }
}
