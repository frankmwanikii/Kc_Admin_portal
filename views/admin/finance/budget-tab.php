<?php
/** @var array<string, mixed> $budget */
/** @var int $budgetYear */
/** @var string $month */
$fmt = static fn (float $n): string => number_format($n, 0);
$b = $budget ?? [];
$totals = $b['totals'] ?? [];
$focusMonth = null;
foreach ($b['months'] ?? [] as $m) {
    if (($m['month'] ?? '') === $month) {
        $focusMonth = $m;
        break;
    }
}
$focusMonth ??= ['budget_expenses' => 0, 'actual_expenses' => 0, 'expense_used_pct' => null, 'status' => 'neutral', 'status_label' => 'No data'];
$statusClass = match ($focusMonth['status'] ?? 'neutral') {
    'over' => 'fin-budget-status--over',
    'under_income' => 'fin-budget-status--warn',
    'on_track' => 'fin-budget-status--ok',
    default => 'fin-budget-status--neutral',
};
?>
<div class="arrears-page fin-budget-page">
    <h2 class="arrears-title">Budget vs Actual</h2>
    <p class="text-sm text-slate-500 -mt-3 mb-5">
        <?= htmlspecialchars($b['label'] ?? ('FY ' . $budgetYear)) ?> — compare planned spending against Sunday entries
    </p>

    <?php if (!($b['has_budget'] ?? false)): ?>
    <div class="fin-budget-empty">
        <p>No annual budget loaded for this financial year.</p>
        <p class="fin-budget-empty__hint">Import the Draft Budget from the Excel workbook to enable tracking.</p>
    </div>
    <?php else: ?>

    <div class="fin-budget-toolbar">
        <form method="get" class="inline-flex items-center gap-2">
            <input type="hidden" name="tab" value="reports">
            <input type="hidden" name="sub" value="budget">
            <label class="fin-budget-toolbar__label">
                Financial year
                <select name="budget_year" onchange="this.form.submit()" class="arrears-year-select" aria-label="Financial year">
                    <?php for ($y = (int) date('Y') + 1; $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $budgetYear === $y ? 'selected' : '' ?>>FY <?= $y ?>/<?= substr((string) ($y + 1), 2) ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label class="fin-budget-toolbar__label">
                Focus month
                <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()" class="arrears-year-select" aria-label="Month">
            </label>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="reconciliation-stat reconciliation-stat--expenses">
            <p class="reconciliation-stat-label">Budgeted expenses (<?= htmlspecialchars(date('M Y', strtotime($month . '-01'))) ?>)</p>
            <p class="reconciliation-stat-value">KES <?= $fmt((float) ($focusMonth['budget_expenses'] ?? 0)) ?></p>
        </div>
        <div class="reconciliation-stat reconciliation-stat--collected">
            <p class="reconciliation-stat-label">Actual expenses</p>
            <p class="reconciliation-stat-value">KES <?= $fmt((float) ($focusMonth['actual_expenses'] ?? 0)) ?></p>
        </div>
        <div class="reconciliation-stat <?= ($focusMonth['expense_variance'] ?? 0) >= 0 ? 'reconciliation-stat--surplus' : 'reconciliation-stat--deficit' ?>">
            <p class="reconciliation-stat-label">Variance (budget − actual)</p>
            <p class="reconciliation-stat-value"><?= ($focusMonth['expense_variance'] ?? 0) < 0 ? '-' : '' ?>KES <?= $fmt(abs((float) ($focusMonth['expense_variance'] ?? 0))) ?></p>
        </div>
        <div class="reconciliation-stat fin-budget-status <?= $statusClass ?>">
            <p class="reconciliation-stat-label">Status</p>
            <p class="reconciliation-stat-value fin-budget-status__value"><?= htmlspecialchars($focusMonth['status_label'] ?? '') ?></p>
            <?php if (($focusMonth['expense_used_pct'] ?? null) !== null): ?>
            <p class="fin-budget-status__pct"><?= (float) $focusMonth['expense_used_pct'] ?>% of budget used</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="arrears-card finance-table-card fin-budget-card">
        <div class="finance-table-caption">
            <span class="finance-table-caption-label">Executive summary</span>
            <span class="finance-table-caption-badge"><?= htmlspecialchars($b['label'] ?? '') ?></span>
            <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
        </div>
        <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Budget vs actual by month">
            <table class="arrears-table fin-budget-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="ft-th-accent ft-th--right">Budget income</th>
                        <th class="ft-th-accent ft-th--right">Actual income</th>
                        <th class="ft-th-accent ft-th--right">Budget expenses</th>
                        <th class="ft-th-accent ft-th--right">Actual expenses</th>
                        <th class="ft-th-accent ft-th--right">Expense var.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($b['months'] ?? [] as $m):
                        if (!($m['has_activity'] ?? false)) {
                            continue;
                        }
                        $isFocus = ($m['month'] ?? '') === $month;
                        $rowStatus = match ($m['status'] ?? 'neutral') {
                            'over' => 'fin-badge--deficit',
                            'under_income' => 'fin-badge--warn',
                            'on_track' => 'fin-badge--surplus',
                            default => 'fin-badge--neutral',
                        };
                        $var = (float) ($m['expense_variance'] ?? 0);
                    ?>
                    <tr class="arrears-row <?= $isFocus ? 'fin-budget-table__row--focus' : '' ?>">
                        <td><span class="arrears-accent"><?= htmlspecialchars($m['label'] ?? '') ?></span></td>
                        <td class="ft-td-accent ft-td--right"><span class="arrears-amount">KES <?= $fmt((float) ($m['budget_income'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right"><span class="arrears-amount">KES <?= $fmt((float) ($m['actual_income'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right"><span class="arrears-amount">KES <?= $fmt((float) ($m['budget_expenses'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right"><span class="arrears-amount">KES <?= $fmt((float) ($m['actual_expenses'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right">
                            <span class="arrears-amount <?= $var >= 0 ? 'fin-budget-var--good' : 'fin-budget-var--bad' ?>">
                                <?= $var < 0 ? '-' : '' ?>KES <?= $fmt(abs($var)) ?>
                            </span>
                        </td>
                        <td><span class="fin-badge fin-badge--sm <?= $rowStatus ?>"><?= htmlspecialchars($m['status_label'] ?? '') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="finance-table-footer">
                        <td class="finance-table-footer-label">Year total</td>
                        <td class="ft-td-accent ft-td--right"><span class="finance-table-footer-amount">KES <?= $fmt((float) ($totals['budget_income'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right"><span class="finance-table-footer-amount">KES <?= $fmt((float) ($totals['actual_income'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right"><span class="finance-table-footer-amount">KES <?= $fmt((float) ($totals['budget_expenses'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right"><span class="finance-table-footer-amount">KES <?= $fmt((float) ($totals['actual_expenses'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right">
                            <?php $yearVar = round((float) ($totals['budget_expenses'] ?? 0) - (float) ($totals['actual_expenses'] ?? 0), 2); ?>
                            <span class="finance-table-footer-amount finance-table-footer-amount--grand <?= $yearVar >= 0 ? 'fin-budget-var--good' : 'fin-budget-var--bad' ?>">
                                <?= $yearVar < 0 ? '-' : '' ?>KES <?= $fmt(abs($yearVar)) ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $yearStatus = match ($totals['status'] ?? 'neutral') {
                                'over' => 'fin-badge--deficit',
                                'under_income' => 'fin-badge--warn',
                                'on_track' => 'fin-badge--surplus',
                                default => 'fin-badge--neutral',
                            };
                            ?>
                            <span class="fin-badge <?= $yearStatus ?>"><?= htmlspecialchars($totals['status_label'] ?? '') ?></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php if (!empty($b['lines'])): ?>
    <div class="arrears-card finance-table-card fin-budget-card mt-5">
        <div class="finance-table-caption">
            <span class="finance-table-caption-label">Expense lines — <?= htmlspecialchars(date('F Y', strtotime($month . '-01'))) ?></span>
            <span class="finance-table-caption-badge">Budget vs actual</span>
        </div>
        <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Expense line budget comparison">
            <table class="arrears-table fin-budget-lines-table">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Line item</th>
                        <th class="ft-th-accent ft-th--right">Budget</th>
                        <th class="ft-th-accent ft-th--right">Actual</th>
                        <th class="ft-th-accent ft-th--right">Variance</th>
                        <th class="ft-th-accent ft-th--right">Used</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($b['lines'] as $line):
                        $lineStatus = match ($line['status'] ?? 'neutral') {
                            'over' => 'fin-badge--deficit',
                            'unbudgeted' => 'fin-badge--warn',
                            'on_track' => 'fin-badge--surplus',
                            default => 'fin-badge--neutral',
                        };
                        $lineVar = (float) ($line['variance'] ?? 0);
                    ?>
                    <tr class="arrears-row">
                        <td class="arrears-muted"><?= htmlspecialchars($line['section'] ?? '') ?></td>
                        <td>
                            <span class="arrears-accent"><?= htmlspecialchars($line['label'] ?? '') ?></span>
                            <span class="fin-budget-code"><?= htmlspecialchars($line['account_code'] ?? '') ?></span>
                        </td>
                        <td class="ft-td-accent ft-td--right"><span class="arrears-amount">KES <?= $fmt((float) ($line['budget'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right"><span class="arrears-amount">KES <?= $fmt((float) ($line['actual'] ?? 0)) ?></span></td>
                        <td class="ft-td-accent ft-td--right">
                            <span class="arrears-amount <?= $lineVar >= 0 ? 'fin-budget-var--good' : 'fin-budget-var--bad' ?>">
                                <?= $lineVar < 0 ? '-' : '' ?>KES <?= $fmt(abs($lineVar)) ?>
                            </span>
                        </td>
                        <td class="ft-td-accent ft-td--right">
                            <?= ($line['used_pct'] ?? null) !== null ? (float) $line['used_pct'] . '%' : '—' ?>
                        </td>
                        <td><span class="fin-badge fin-badge--sm <?= $lineStatus ?>"><?= htmlspecialchars($line['status_label'] ?? '') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
