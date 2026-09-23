<?php
/** @var array<string, mixed> $budget */
/** @var int $budgetYear */
/** @var string $month */
$fmt = static fn (float $n): string => number_format($n, 0);
$b = $budget ?? [];
$totals = $b['totals'] ?? [];
$budgetEditMode = !empty($budgetEditMode);
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
$budgetBackUrl = '/admin/finance?' . http_build_query([
    'tab' => 'budget',
    'budget_year' => (int) $budgetYear,
    'month' => $month,
]);
$budgetEditUrl = '/admin/finance?' . http_build_query([
    'tab' => 'budget',
    'edit' => '1',
    'budget_year' => (int) $budgetYear,
    'month' => $month,
]);
$focusMonthLabel = date('F Y', strtotime($month . '-01'));
$usedPct = $focusMonth['expense_used_pct'] ?? null;
$expenseVar = (float) ($focusMonth['expense_variance'] ?? 0);

if ($budgetEditMode):
?>
<div class="arrears-page fin-budget-page fin-budget-page--edit">
    <header class="fin-sunday-top">
        <a href="<?= htmlspecialchars($budgetBackUrl) ?>" class="fin-sunday-back">
            <span class="fin-sunday-back__icon" aria-hidden="true">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </span>
            <span class="fin-sunday-back__text">
                <span class="fin-sunday-back__prefix">Back to</span>
                Budget vs actual
            </span>
        </a>
        <div class="fin-sunday-top__hero">
            <div class="fin-sunday-top__copy">
                <p class="fin-sunday-top__eyebrow"><?= htmlspecialchars($fyLabel) ?></p>
                <h1 class="fin-sunday-top__title">Set Budget</h1>
                <p class="fin-sunday-top__sub">
                    Planned amounts for <?= htmlspecialchars(date('F Y', strtotime($month . '-01'))) ?>.
                    These drive on-track / over-budget tracking.
                </p>
            </div>
        </div>
    </header>

    <form method="post" action="/admin/finance/budget" class="fin-budget-editor fin-budget-editor--page" @submit="saveBudgetMonth($event)">
        <input type="hidden" name="budget_year" value="<?= (int) $budgetYear ?>">
        <input type="hidden" name="month" :value="weeklyMonth" value="<?= htmlspecialchars($month) ?>">

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

        <?php
        // Reuse the same editor sections from below by including shared markup inline.
        ?>
        <section class="fin-budget-editor__section">
            <div class="fin-budget-editor__section-head">
                <h3>Income</h3>
                <button type="button" class="fin-link" @click="startBudgetNewLine('income')">+ Add income line</button>
            </div>
            <div class="fin-amount-grid fin-budget-editor__grid">
                <template x-for="line in budgetEditIncomeLines" :key="'in-' + line.id">
                    <div class="fin-amt-row"
                         :class="Number(line.amount) > 0 && 'fin-amt-row--filled'">
                        <div class="fin-amt-row__meta">
                            <div class="fin-amt-row__text">
                                <label class="fin-amt-row__label"
                                       :for="'budget-in-' + line.id"
                                       x-text="line.label"></label>
                            </div>
                        </div>
                        <div class="fin-amt-row__actions">
                            <div class="fin-amt-row__field">
                                <span class="fin-amt-row__currency" aria-hidden="true">KES</span>
                                <input type="number"
                                       :id="'budget-in-' + line.id"
                                       min="0"
                                       step="1"
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       placeholder="0"
                                       class="fin-amt-row__input"
                                       :name="'amounts[' + line.id + ']'"
                                       x-model.number="line.amount"
                                       @focus="$el.select()"
                                       :aria-label="(line.label || 'Income') + ' amount in Kenyan Shillings'">
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
            </div>
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
                                <span class="fin-amt-row__currency" aria-hidden="true">KES</span>
                                <input type="number"
                                       min="0"
                                       step="1"
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       placeholder="0"
                                       class="fin-amt-row__input"
                                       x-model.number="budgetNewLine.amount"
                                       @focus="$el.select()"
                                       @keydown.enter.prevent="saveBudgetNewLine()"
                                       aria-label="New income amount in Kenyan Shillings">
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

            <template x-for="group in budgetEditExpenseGroups" :key="'exg-' + group.section">
                <div class="fin-budget-editor__category">
                    <div class="fin-budget-editor__category-head">
                        <h4 class="fin-budget-editor__category-title" x-text="group.section"></h4>
                        <span class="fin-budget-editor__category-total"
                              x-text="'KES ' + formatMoneyPlain(group.total)"></span>
                    </div>
                    <div class="fin-amount-grid fin-budget-editor__grid">
                        <template x-for="line in group.lines" :key="'ex-' + line.id">
                            <div class="fin-amt-row fin-amt-row--expense"
                                 :class="Number(line.amount) > 0 && 'fin-amt-row--filled'">
                                <div class="fin-amt-row__meta">
                                    <div class="fin-amt-row__text">
                                        <label class="fin-amt-row__label"
                                               :for="'budget-ex-' + line.id"
                                               x-text="line.label"></label>
                                    </div>
                                </div>
                                <div class="fin-amt-row__actions">
                                    <div class="fin-amt-row__field">
                                        <span class="fin-amt-row__currency" aria-hidden="true">KES</span>
                                        <input type="number"
                                               :id="'budget-ex-' + line.id"
                                               min="0"
                                               step="1"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               placeholder="0"
                                               class="fin-amt-row__input"
                                               :name="'amounts[' + line.id + ']'"
                                               x-model.number="line.amount"
                                               @focus="$el.select()"
                                               :aria-label="(line.label || 'Expense') + ' amount in Kenyan Shillings'">
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
                            <select class="finance-input fin-budget-newline__section"
                                    x-model="budgetNewLine.section"
                                    aria-label="Expense category">
                                <template x-for="sec in budgetExpenseSections" :key="'sec-' + sec">
                                    <option :value="sec" x-text="sec"></option>
                                </template>
                            </select>
                            <input type="text"
                                   required
                                   class="finance-input"
                                   placeholder="Line name"
                                   x-model="budgetNewLine.label"
                                   @keydown.enter.prevent="saveBudgetNewLine()">
                            <div class="fin-amt-row__field">
                                <span class="fin-amt-row__currency" aria-hidden="true">KES</span>
                                <input type="number"
                                       min="0"
                                       step="1"
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       placeholder="0"
                                       class="fin-amt-row__input"
                                       x-model.number="budgetNewLine.amount"
                                       @focus="$el.select()"
                                       @keydown.enter.prevent="saveBudgetNewLine()"
                                       aria-label="New expense amount in Kenyan Shillings">
                            </div>
                            <button type="button" class="finance-btn-primary" @click="saveBudgetNewLine()">Add</button>
                            <button type="button" class="finance-btn-secondary" @click="budgetNewLine = null">Cancel</button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <footer class="fin-sunday-footer">
            <div class="fin-sunday-footer__actions" style="margin-left:auto">
                <a href="<?= htmlspecialchars($budgetBackUrl) ?>" class="fin-btn fin-btn--ghost">Cancel</a>
                <button type="submit" class="fin-btn fin-btn--primary fin-btn--lg fin-btn--save">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    Save budget
                </button>
            </div>
        </footer>
    </form>
</div>
<?php
return;
endif;
?>
<div class="arrears-page fin-budget-page">
    <div class="fin-report-bar no-print">
        <div class="fin-report-bar__main">
            <div class="fin-report-bar__identity">
                <h2 class="fin-report-bar__title">Budget vs actual</h2>
                <p class="fin-report-bar__hint"><?= htmlspecialchars($fyLabel) ?> — plan first, then track Sunday activity against it</p>
            </div>
            <div class="fin-report-bar__tools">
                <form method="get" class="fin-budget-bar__controls" id="fin-budget-filters">
                    <input type="hidden" name="tab" value="budget">
                    <label class="fin-report-bar__year">
                        <span class="fin-report-bar__year-label">Financial year</span>
                        <select name="budget_year"
                                class="fin-report-bar__select"
                                aria-label="Financial year"
                                onchange="this.form.submit()">
                            <?php for ($y = (int) date('Y') + 1; $y >= 2024; $y--): ?>
                            <option value="<?= $y ?>" <?= (int) $budgetYear === $y ? 'selected' : '' ?>>
                                FY <?= $y ?>/<?= substr((string) ($y + 1), 2) ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label class="fin-report-bar__year">
                        <span class="fin-report-bar__year-label">Focus month</span>
                        <?php
                        $monthPickerLabel = 'Focus month';
                        $monthPickerTarget = 'budget';
                        $monthLabel = $focusMonthLabel;
                        $monthPickerClass = 'fin-budget-bar__month';
                        require __DIR__ . '/_month-picker.php';
                        ?>
                        <input type="hidden" name="month" :value="weeklyMonth" value="<?= htmlspecialchars($month) ?>">
                    </label>
                </form>
                <a href="<?= htmlspecialchars($budgetEditUrl) ?>" class="fin-btn fin-btn--primary fin-budget-bar__cta">
                    <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                    Set Budget
                </a>
            </div>
        </div>
    </div>

    <?php if (!($b['has_budget'] ?? false)): ?>
    <div class="fin-budget-empty">
        <div class="fin-budget-empty__icon" aria-hidden="true">
            <i data-lucide="wallet" class="w-8 h-8"></i>
        </div>
        <h3 class="fin-budget-empty__title">No budget for <?= htmlspecialchars($focusMonthLabel) ?></h3>
        <p class="fin-budget-empty__hint">
            Set planned income and expenses for this month. Overview and status badges use these figures to show on-track or over-budget.
        </p>
        <a href="<?= htmlspecialchars($budgetEditUrl) ?>" class="fin-btn fin-btn--primary mt-4">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            Set Budget
        </a>
    </div>
    <?php else: ?>

    <div class="fin-budget-pulse">
        <div class="fin-budget-pulse__card">
            <p class="fin-budget-pulse__label">Budgeted expenses</p>
            <p class="fin-budget-pulse__value">KES <?= $fmt((float) ($focusMonth['budget_expenses'] ?? 0)) ?></p>
            <p class="fin-budget-pulse__meta"><?= htmlspecialchars($focusMonthLabel) ?></p>
        </div>
        <div class="fin-budget-pulse__card">
            <p class="fin-budget-pulse__label">Actual expenses</p>
            <p class="fin-budget-pulse__value">KES <?= $fmt((float) ($focusMonth['actual_expenses'] ?? 0)) ?></p>
            <p class="fin-budget-pulse__meta">From Sunday entries</p>
        </div>
        <div class="fin-budget-pulse__card <?= $expenseVar >= 0 ? 'fin-budget-pulse__card--good' : 'fin-budget-pulse__card--bad' ?>">
            <p class="fin-budget-pulse__label">Variance</p>
            <p class="fin-budget-pulse__value"><?= $expenseVar < 0 ? '-' : '' ?>KES <?= $fmt(abs($expenseVar)) ?></p>
            <p class="fin-budget-pulse__meta"><?= $expenseVar >= 0 ? 'Under budget' : 'Over budget' ?></p>
        </div>
        <div class="fin-budget-pulse__card fin-budget-status <?= $statusClass ?>">
            <p class="fin-budget-pulse__label">Status</p>
            <p class="fin-budget-pulse__value fin-budget-status__value"><?= htmlspecialchars($focusMonth['status_label'] ?? '') ?></p>
            <?php if ($usedPct !== null): ?>
            <div class="fin-budget-meter fin-budget-meter--inline" role="progressbar" aria-valuenow="<?= (float) $usedPct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="fin-budget-meter__fill <?= (float) $usedPct > 100 ? 'fin-budget-meter__fill--over' : '' ?>"
                     style="width: <?= min(100, (float) $usedPct) ?>%"></div>
            </div>
            <p class="fin-budget-pulse__meta"><?= (float) $usedPct ?>% of budget used</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="arrears-card finance-table-card fin-budget-card">
        <div class="finance-table-caption">
            <span class="finance-table-caption-label">Year at a glance</span>
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
                        if (!($m['has_activity'] ?? false) && (float) ($m['budget_expenses'] ?? 0) <= 0 && (float) ($m['budget_income'] ?? 0) <= 0) {
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
                        $rowMonth = (string) ($m['month'] ?? '');
                        $rowHref = $rowMonth !== ''
                            ? '/admin/finance?' . http_build_query([
                                'tab' => 'budget',
                                'budget_year' => (int) $budgetYear,
                                'month' => $rowMonth,
                            ])
                            : '';
                    ?>
                    <tr class="arrears-row <?= $isFocus ? 'fin-budget-table__row--focus' : '' ?>">
                        <td>
                            <?php if ($rowHref !== '' && !$isFocus): ?>
                            <a href="<?= htmlspecialchars($rowHref) ?>" class="fin-link arrears-accent"><?= htmlspecialchars($m['label'] ?? '') ?></a>
                            <?php else: ?>
                            <span class="arrears-accent"><?= htmlspecialchars($m['label'] ?? '') ?></span>
                            <?php endif; ?>
                        </td>
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
            <span class="finance-table-caption-label">Expense lines — <?= htmlspecialchars($focusMonthLabel) ?></span>
            <span class="finance-table-caption-badge">Budget vs actual</span>
            <a href="<?= htmlspecialchars($budgetEditUrl) ?>" class="fin-link finance-table-caption-action">Edit amounts →</a>
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
                    <?php
                    $prevSection = null;
                    foreach ($b['lines'] as $line):
                        $section = (string) ($line['section'] ?? '');
                        if ($section !== '' && $section !== $prevSection):
                            $prevSection = $section;
                    ?>
                    <tr class="fin-budget-lines-table__section">
                        <td colspan="7"><?= htmlspecialchars($section) ?></td>
                    </tr>
                    <?php
                        endif;
                        $lineStatus = match ($line['status'] ?? 'neutral') {
                            'over' => 'fin-badge--deficit',
                            'unbudgeted' => 'fin-badge--warn',
                            'on_track' => 'fin-badge--surplus',
                            default => 'fin-badge--neutral',
                        };
                        $lineVar = (float) ($line['variance'] ?? 0);
                    ?>
                    <tr class="arrears-row">
                        <td class="arrears-muted"><?= htmlspecialchars($section) ?></td>
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
</div>
