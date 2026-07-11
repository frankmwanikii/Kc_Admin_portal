<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\FinanceReconciliationService;
use App\Services\FormSubmissionService;
use App\Services\SettingsService;

class DashboardController
{
    public function index(): void
    {
        Auth::requireAdmin();

        FormSubmissionService::ensureTable();
        FormSubmissionService::ensureFinanceTables();
        FinanceReconciliationService::ensureTables();

        $db = Database::connection();
        $year = (int) date('Y');
        $month = date('Y-m');

        $memberCount = count(FormSubmissionService::membersList());
        $newMembers = FormSubmissionService::countByStatus('new');

        $inventoryCount = 0;
        try {
            $inventoryCount = (int) $db->query('SELECT COUNT(*) FROM inventory_items')->fetchColumn();
        } catch (\Throwable) {
            // table may not exist until first inventory page load
        }

        $arrears = FinanceReconciliationService::arrearsList($year);
        $arrearsOutstanding = array_sum(array_column($arrears, 'balance_owing'));
        $weekly = FinanceReconciliationService::weeklyGrid($month);
        $monthFinance = FinanceReconciliationService::monthReconciliation($month);
        $yearFinance = FinanceReconciliationService::yearReconciliation($year);
        $collectionsMonth = FinanceReconciliationService::collectionTotals($year, $month);

        $expenseBreakdown = [];
        foreach ($weekly['rows'] ?? [] as $row) {
            if (($row['total'] ?? 0) > 0) {
                $expenseBreakdown[] = [
                    'label' => $row['label'],
                    'amount' => (float) $row['total'],
                ];
            }
        }
        usort($expenseBreakdown, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        $expenseBreakdown = array_slice($expenseBreakdown, 0, 6);

        View::render('admin/dashboard', [
            'title' => 'Dashboard',
            'churchName' => SettingsService::churchName(),
            'stats' => [
                'members' => $memberCount,
                'new_members' => $newMembers,
                'inventory' => $inventoryCount,
                'arrears_outstanding' => $arrearsOutstanding,
                'weekly_month' => $weekly['month_total'] ?? 0,
                'collections_month' => $collectionsMonth['total'] ?? 0,
                'month_balance' => $monthFinance['month_balance'] ?? 0,
            ],
            'recentMembers' => array_slice(FormSubmissionService::membersList(), 0, 6),
            'charts' => [
                'financeYear' => $yearFinance,
                'financeMonth' => $monthFinance,
                'collectionsMonth' => $collectionsMonth,
                'expenseBreakdown' => $expenseBreakdown,
                'membersTrend' => FormSubmissionService::registrationsTrend(6),
                'membersStatus' => FormSubmissionService::statusBreakdown(),
            ],
        ], 'layouts/admin');
    }
}
