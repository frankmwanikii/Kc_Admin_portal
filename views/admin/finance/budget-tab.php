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
$fyLabel = $b['label'] ?? ('FY ' . $budgetYear);
?>
<div class="arrears-page fin-budget-page">
    <div class="fin-budget-page__head">
        <div>
            <h2 class="arrears-title">Budget vs Actual</h2>
            <p class="text-sm text-slate-500 -mt-3 mb-5">
                <?= htmlspecialchars($fyLabel) ?> — set planned amounts, then compare against Sunday entries to stay on track
            </p>
        </div>
        <button type="button" class="arrears-btn-new" @click="openBudgetEditor()">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            Set Budget
        </button>
    </div>

    <div class="fin-budget-toolbar">
        <form method="get" class="inline-flex items-center gap-2">
            <input type="hidden" name="tab" value="budget">
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

    <?php if (!($b['has_budget'] ?? false)): ?>
    <div class="fin-budget-empty">
        <p>No budget amounts for this month yet.</p>
        <p class="fin-budget-empty__hint">Click <strong>Set Budget</strong> to enter planned income and expenses. Those figures drive on-track / over-budget status.</p>
        <button type="button" class="arrears-btn-new mt-3" @click="openBudgetEditor()">Set Budget</button>
    </div>
    <?php else: ?>

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

    <!-- Set Budget modal -->
    <div x-show="showBudgetEditor"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="closeBudgetEditor()">
        <div class="finance-modal-backdrop" @click="closeBudgetEditor()"></div>
        <div class="finance-modal finance-modal--sunday" x-transition role="dialog" aria-modal="true" aria-labelledby="budget-editor-title">
            <header class="finance-modal-header">
                <div class="finance-modal-header-text">
                    <p class="finance-modal-eyebrow">Budget</p>
                    <h4 class="finance-modal-title" id="budget-editor-title">Set Budget</h4>
                    <p class="finance-modal-subtitle">
                        Planned amounts for <span x-text="monthLabel"><?= htmlspecialchars(date('F Y', strtotime($month . '-01'))) ?></span>
                        (<?= htmlspecialchars($fyLabel) ?>). These drive on-track / over-budget tracking.
                    </p>
                </div>
                <button type="button" @click="closeBudgetEditor()" class="finance-modal-close" aria-label="Close">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </header>

            <form method="post" action="/admin/finance/budget" class="fin-budget-editor" @submit="saveBudgetMonth($event)">
                <input type="hidden" name="budget_year" value="<?= (int) $budgetYear ?>">
                <input type="hidden" name="month" :value="weeklyMonth" value="<?= htmlspecialchars($month) ?>">

                <div class="finance-modal-body finance-modal-body--sunday">
                    <div class="fin-budget-editor__totals">
                        <div class="fin-budget-editor__total fin-budget-editor__total--in">
                            <span>Income planned</span>
                            <strong x-text="'KES ' + formatMoneyPlain(budgetEditIncomeTotal)">KES 0</strong>
                        </div>
                        <div class="fin-budget-editor__total fin-budget-editor__total--out">
                            <span>Expenses planned</span>
                            <strong x-text="'KES ' + formatMoneyPlain(budgetEditExpenseTotal)">KES 0</strong>
                        </div>
                    </div>

                    <section class="fin-budget-editor__section">
                        <div class="fin-budget-editor__section-head">
                            <h3>Income</h3>
                            <button type="button" class="fin-link" @click="startBudgetNewLine('income')">+ Add income line</button>
                        </div>
                        <template x-for="line in budgetEditIncomeLines" :key="'in-' + line.id">
                            <div class="fin-budget-editor__row">
                                <div class="fin-budget-editor__meta">
                                    <span class="fin-budget-editor__label" x-text="line.label"></span>
                                </div>
                                <div class="fin-budget-editor__controls">
                                    <div class="fin-amt-row__field">
                                        <span class="fin-amt-row__currency">KES</span>
                                        <input type="number"
                                               min="0"
                                               step="1"
                                               inputmode="numeric"
                                               class="fin-amt-row__input finance-input"
                                               :name="'amounts[' + line.id + ']'"
                                               x-model.number="line.amount"
                                               @focus="$el.select()">
                                    </div>
                                    <button type="button"
                                            class="fin-budget-editor__delete"
                                            title="Delete income line"
                                            aria-label="Delete income line"
                                            @click="deleteBudgetLine(line)">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <p class="finance-field-hint" x-show="budgetEditIncomeLines.length === 0 && !(budgetNewLine && budgetNewLine.line_type === 'income')">No income lines yet — add one above.</p>
                        <div class="fin-budget-newline"
                             x-show="budgetNewLine && budgetNewLine.line_type === 'income'"
                             x-cloak
                             x-ref="budgetNewIncome">
                            <template x-if="budgetNewLine && budgetNewLine.line_type === 'income'">
                                <div>
                                    <p class="fin-budget-newline__title">New income line</p>
                                    <div class="fin-budget-newline__fields">
                                        <input type="text"
                                               required
                                               class="finance-input"
                                               placeholder="Line name"
                                               x-model="budgetNewLine.label"
                                               @keydown.enter.prevent="saveBudgetNewLine()">
                                        <div class="fin-amt-row__field">
                                            <span class="fin-amt-row__currency">KES</span>
                                            <input type="number"
                                                   min="0"
                                                   step="1"
                                                   class="fin-amt-row__input finance-input"
                                                   placeholder="0"
                                                   x-model.number="budgetNewLine.amount"
                                                   @keydown.enter.prevent="saveBudgetNewLine()">
                                        </div>
                                        <button type="button" class="finance-btn-primary" @click="saveBudgetNewLine()">Add</button>
                                        <button type="button" class="finance-btn-secondary" @click="budgetNewLine = null">Cancel</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>

                    <section class="fin-budget-editor__section">
                        <div class="fin-budget-editor__section-head">
                            <h3>Expenses</h3>
                            <button type="button" class="fin-link" @click="startBudgetNewLine('expense')">+ Add expense line</button>
                        </div>
                        <template x-for="line in budgetEditExpenseLines" :key="'ex-' + line.id">
                            <div class="fin-budget-editor__row">
                                <div class="fin-budget-editor__meta">
                                    <span class="fin-budget-editor__section-tag" x-show="line.section" x-text="line.section"></span>
                                    <span class="fin-budget-editor__label" x-text="line.label"></span>
                                </div>
                                <div class="fin-budget-editor__controls">
                                    <div class="fin-amt-row__field">
                                        <span class="fin-amt-row__currency">KES</span>
                                        <input type="number"
                                               min="0"
                                               step="1"
                                               inputmode="numeric"
                                               class="fin-amt-row__input finance-input"
                                               :name="'amounts[' + line.id + ']'"
                                               x-model.number="line.amount"
                                               @focus="$el.select()">
                                    </div>
                                    <button type="button"
                                            class="fin-budget-editor__delete"
                                            title="Delete expense line"
                                            aria-label="Delete expense line"
                                            @click="deleteBudgetLine(line)">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <p class="finance-field-hint" x-show="budgetEditExpenseLines.length === 0 && !(budgetNewLine && budgetNewLine.line_type === 'expense')">No expense lines yet — add one above.</p>
                        <div class="fin-budget-newline"
                             x-show="budgetNewLine && budgetNewLine.line_type === 'expense'"
                             x-cloak
                             x-ref="budgetNewExpense">
                            <template x-if="budgetNewLine && budgetNewLine.line_type === 'expense'">
                                <div>
                                    <p class="fin-budget-newline__title">New expense line</p>
                                    <div class="fin-budget-newline__fields">
                                        <input type="text"
                                               required
                                               class="finance-input"
                                               placeholder="Line name"
                                               x-model="budgetNewLine.label"
                                               @keydown.enter.prevent="saveBudgetNewLine()">
                                        <div class="fin-amt-row__field">
                                            <span class="fin-amt-row__currency">KES</span>
                                            <input type="number"
                                                   min="0"
                                                   step="1"
                                                   class="fin-amt-row__input finance-input"
                                                   placeholder="0"
                                                   x-model.number="budgetNewLine.amount"
                                                   @keydown.enter.prevent="saveBudgetNewLine()">
                                        </div>
                                        <button type="button" class="finance-btn-primary" @click="saveBudgetNewLine()">Add</button>
                                        <button type="button" class="finance-btn-secondary" @click="budgetNewLine = null">Cancel</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>
                </div>

                <footer class="finance-modal-footer">
                    <div class="finance-modal-actions finance-modal-actions--end">
                        <button type="button" @click="closeBudgetEditor()" class="finance-btn-secondary">Cancel</button>
                        <button type="submit" class="finance-btn-primary">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            Save budget
                        </button>
                    </div>
                </footer>
            </form>
        </div>
    </div>
</div>
