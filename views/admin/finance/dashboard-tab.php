<?php
/** @var array<string, mixed> $dashboard */
/** @var int $year */
/** @var string $month */
$fmt = static fn (float $n): string => number_format($n, 0);
$d = $dashboard ?? [];
$netOp = (float) ($d['net_operating'] ?? 0);
$netPos = (float) ($d['net_position'] ?? 0);
$colYtd = (float) ($d['collections_ytd'] ?? 0);
$expYtd = (float) ($d['expenses_ytd'] ?? 0);
$arrearsOwing = (float) ($d['arrears_owing'] ?? 0);
$runningBal = (float) ($d['running_balance'] ?? 0);
$budgetSnap = $d['budget'] ?? [];
$budgetStatusClass = match ($budgetSnap['status'] ?? 'neutral') {
    'over' => 'fin-budget-pill--over',
    'under_income' => 'fin-budget-pill--warn',
    'on_track' => 'fin-budget-pill--ok',
    default => 'fin-budget-pill--neutral',
};
$budgetYearDash = (int) date('n', strtotime($month . '-01')) >= 4
    ? (int) substr($month, 0, 4)
    : (int) substr($month, 0, 4) - 1;
?>
<section class="fin-dashboard">
    <div class="fin-kpi-grid">
        <article class="fin-kpi fin-kpi--collections">
            <div class="fin-kpi__icon-wrap">
                <i data-lucide="trending-up" class="fin-kpi__icon"></i>
            </div>
            <div class="fin-kpi__body">
                <p class="fin-kpi__label">Collections <?= (int) $year ?></p>
                <p class="fin-kpi__value">KES <?= $fmt($colYtd) ?></p>
                <p class="fin-kpi__hint">Total received this year</p>
            </div>
        </article>
        <article class="fin-kpi fin-kpi--expenses">
            <div class="fin-kpi__icon-wrap">
                <i data-lucide="wallet" class="fin-kpi__icon"></i>
            </div>
            <div class="fin-kpi__body">
                <p class="fin-kpi__label">Expenses <?= (int) $year ?></p>
                <p class="fin-kpi__value">KES <?= $fmt($expYtd) ?></p>
                <p class="fin-kpi__hint">Sunday cash spending</p>
            </div>
        </article>
        <article class="fin-kpi <?= $netOp >= 0 ? 'fin-kpi--surplus' : 'fin-kpi--deficit' ?>">
            <div class="fin-kpi__icon-wrap">
                <i data-lucide="scale" class="fin-kpi__icon"></i>
            </div>
            <div class="fin-kpi__body">
                <p class="fin-kpi__label">Operating balance</p>
                <p class="fin-kpi__value"><?= $netOp < 0 ? '-' : '' ?>KES <?= $fmt(abs($netOp)) ?></p>
                <p class="fin-kpi__hint">Collections minus expenses</p>
            </div>
        </article>
        <article class="fin-kpi fin-kpi--arrears">
            <div class="fin-kpi__icon-wrap">
                <i data-lucide="alert-circle" class="fin-kpi__icon"></i>
            </div>
            <div class="fin-kpi__body">
                <p class="fin-kpi__label">Outstanding bills</p>
                <p class="fin-kpi__value">KES <?= $fmt($arrearsOwing) ?></p>
                <p class="fin-kpi__hint"><?= (int) ($d['arrears_count'] ?? 0) ?> unpaid bill<?= ((int) ($d['arrears_count'] ?? 0)) === 1 ? '' : 's' ?></p>
            </div>
        </article>
    </div>

    <div class="fin-dashboard__secondary">
        <article class="fin-highlight <?= $netPos >= 0 ? 'fin-highlight--positive' : 'fin-highlight--negative' ?>">
            <p class="fin-highlight__label">True net position</p>
            <p class="fin-highlight__value"><?= $netPos < 0 ? '-' : '' ?>KES <?= $fmt(abs($netPos)) ?></p>
            <p class="fin-highlight__formula">Operating balance (<?= $netOp < 0 ? '-' : '' ?>KES <?= $fmt(abs($netOp)) ?>) minus outstanding bills (KES <?= $fmt($arrearsOwing) ?>)</p>
        </article>
        <?php if ($budgetSnap['has_fy_budget'] ?? false): ?>
        <article class="fin-highlight fin-highlight--budget">
            <div class="fin-budget-pill <?= $budgetStatusClass ?>"><?= htmlspecialchars($budgetSnap['status_label'] ?? 'Budget') ?></div>
            <p class="fin-highlight__label"><?= htmlspecialchars($budgetSnap['fy_label'] ?? 'Annual budget') ?></p>
            <?php if (($budgetSnap['budget_expenses'] ?? 0) > 0 || ($budgetSnap['actual_expenses'] ?? 0) > 0): ?>
            <p class="fin-highlight__label fin-highlight__label--sub"><?= htmlspecialchars($budgetSnap['label'] ?? '') ?></p>
            <p class="fin-highlight__value">KES <?= $fmt((float) ($budgetSnap['actual_expenses'] ?? 0)) ?> <span class="fin-highlight__of">of KES <?= $fmt((float) ($budgetSnap['budget_expenses'] ?? 0)) ?></span></p>
            <?php if (($budgetSnap['expense_used_pct'] ?? null) !== null): ?>
            <div class="fin-budget-meter" role="progressbar" aria-valuenow="<?= (float) $budgetSnap['expense_used_pct'] ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="fin-budget-meter__fill <?= ($budgetSnap['expense_used_pct'] ?? 0) > 100 ? 'fin-budget-meter__fill--over' : '' ?>"
                     style="width: <?= min(100, (float) $budgetSnap['expense_used_pct']) ?>%"></div>
            </div>
            <p class="fin-highlight__formula"><?= (float) $budgetSnap['expense_used_pct'] ?>% of this month's expense budget used</p>
            <?php endif; ?>
            <?php else: ?>
            <p class="fin-highlight__value">KES <?= $fmt((float) ($budgetSnap['fy_budget_expenses'] ?? 0)) ?></p>
            <p class="fin-highlight__formula">Annual expense budget — no line items set for <?= htmlspecialchars($budgetSnap['label'] ?? 'this month') ?> yet</p>
            <?php endif; ?>
            <a href="/admin/finance?tab=reports&sub=budget&budget_year=<?= $budgetYearDash ?>&month=<?= htmlspecialchars($month) ?>" class="fin-link fin-budget-link">Budget report →</a>
        </article>
        <?php else: ?>
        <article class="fin-highlight fin-highlight--neutral">
            <p class="fin-highlight__label">Running balance</p>
            <p class="fin-highlight__value"><?= $runningBal < 0 ? '-' : '' ?>KES <?= $fmt(abs($runningBal)) ?></p>
            <p class="fin-highlight__formula">Cumulative across <?= (int) ($d['weeks_recorded'] ?? 0) ?> Sunday<?= ((int) ($d['weeks_recorded'] ?? 0)) === 1 ? '' : 's' ?> with activity</p>
        </article>
        <?php endif; ?>
    </div>

    <?php if ($budgetSnap['has_fy_budget'] ?? false): ?>
    <div class="fin-dashboard__secondary fin-dashboard__secondary--single">
        <article class="fin-highlight fin-highlight--neutral">
            <p class="fin-highlight__label">Running balance</p>
            <p class="fin-highlight__value"><?= $runningBal < 0 ? '-' : '' ?>KES <?= $fmt(abs($runningBal)) ?></p>
            <p class="fin-highlight__formula">Cumulative across <?= (int) ($d['weeks_recorded'] ?? 0) ?> Sunday<?= ((int) ($d['weeks_recorded'] ?? 0)) === 1 ? '' : 's' ?> with activity</p>
        </article>
    </div>
    <?php endif; ?>

    <div class="fin-panel-grid">
        <div class="fin-panel fin-panel--wide">
            <div class="fin-panel__head">
                <h3 class="fin-panel__title">Monthly overview</h3>
                <a href="/admin/finance?tab=reports&sub=budget&month=<?= htmlspecialchars($month) ?>" class="fin-link">Budget report →</a>
            </div>
            <div class="fin-table-wrap" tabindex="0" role="region" aria-label="Monthly performance">
                <table class="fin-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="fin-table__num">Collections</th>
                            <th class="fin-table__num">Expenses</th>
                            <th class="fin-table__num">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($d['months'] ?? [] as $m):
                            $bal = (float) ($m['balance'] ?? 0);
                            if (!($m['has_activity'] ?? false)) continue;
                        ?>
                        <tr>
                            <td><span class="fin-table__primary"><?= htmlspecialchars($m['label'] ?? '') ?></span></td>
                            <td class="fin-table__num fin-table__money">KES <?= $fmt((float) ($m['collections'] ?? 0)) ?></td>
                            <td class="fin-table__num fin-table__money">KES <?= $fmt((float) ($m['expenses'] ?? 0)) ?></td>
                            <td class="fin-table__num">
                                <span class="fin-badge <?= $bal >= 0 ? 'fin-badge--surplus' : 'fin-badge--deficit' ?>">
                                    <?= $bal < 0 ? '-' : '' ?>KES <?= $fmt(abs($bal)) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ($colYtd <= 0 && $expYtd <= 0): ?>
                        <tr>
                            <td colspan="4" class="fin-table__empty">No activity recorded yet. <a href="/admin/finance/sunday" class="fin-link">Record your first Sunday →</a></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($colYtd > 0 || $expYtd > 0): ?>
                    <tfoot>
                        <tr>
                            <td class="fin-table__foot-label">Year total</td>
                            <td class="fin-table__num fin-table__foot">KES <?= $fmt($colYtd) ?></td>
                            <td class="fin-table__num fin-table__foot">KES <?= $fmt($expYtd) ?></td>
                            <td class="fin-table__num">
                                <span class="fin-badge <?= $netOp >= 0 ? 'fin-badge--surplus' : 'fin-badge--deficit' ?> fin-badge--lg">
                                    <?= $netOp < 0 ? '-' : '' ?>KES <?= $fmt(abs($netOp)) ?>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="fin-panel">
            <div class="fin-panel__head">
                <h3 class="fin-panel__title">Unpaid bills</h3>
                <a href="/admin/finance?tab=bills&year=<?= (int) $year ?>" class="fin-link">Manage →</a>
            </div>
            <?php if (empty($d['top_arrears'])): ?>
            <p class="fin-panel__empty">No outstanding bills. You're all caught up.</p>
            <?php else: ?>
            <ul class="fin-arrear-list">
                <?php foreach ($d['top_arrears'] as $bill): ?>
                <li class="fin-arrear-list__item">
                    <div class="fin-arrear-list__info">
                        <span class="fin-arrear-list__name"><?= htmlspecialchars($bill['expense_item'] ?? '') ?></span>
                        <span class="fin-arrear-list__period"><?= htmlspecialchars($bill['month_incurred'] ?? '') ?></span>
                    </div>
                    <span class="fin-arrear-list__amount">KES <?= $fmt((float) ($bill['balance_owing'] ?? 0)) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($d['recent_weeks'])): ?>
    <div class="fin-panel">
        <div class="fin-panel__head">
            <h3 class="fin-panel__title">Recent Sundays</h3>
            <a href="/admin/finance/sunday?month=<?= htmlspecialchars($month) ?>" class="fin-link">Record Sunday →</a>
        </div>
        <div class="fin-sundays-table" role="table" aria-label="Recent Sunday entries">
            <div class="fin-sundays-table__row fin-sundays-table__row--head" role="row">
                <div class="fin-sundays-table__cell fin-sundays-table__cell--date" role="columnheader">Date</div>
                <div class="fin-sundays-table__cell fin-sundays-table__cell--num" role="columnheader">In</div>
                <div class="fin-sundays-table__cell fin-sundays-table__cell--num" role="columnheader">Out</div>
                <div class="fin-sundays-table__cell fin-sundays-table__cell--num" role="columnheader">Week</div>
                <div class="fin-sundays-table__cell fin-sundays-table__cell--num" role="columnheader">Running</div>
            </div>
            <?php foreach ($d['recent_weeks'] as $w):
                $weekBal = (float) ($w['balance'] ?? 0);
                $runBal = (float) ($w['running_balance'] ?? 0);
            ?>
            <div class="fin-sundays-table__row" role="row">
                <div class="fin-sundays-table__cell fin-sundays-table__cell--date" role="cell">
                    <a href="/admin/finance/sunday?month=<?= htmlspecialchars(substr($w['week_date'], 0, 7)) ?>&week_date=<?= htmlspecialchars($w['week_date']) ?>"
                       class="fin-link fin-sundays-table__date-link">
                        <?= date('j M Y', strtotime($w['week_date'])) ?>
                    </a>
                    <span class="fin-sundays-table__date-sub"><?= htmlspecialchars($w['sun_label'] ?? '') ?></span>
                </div>
                <div class="fin-sundays-table__cell fin-sundays-table__cell--num" role="cell">KES <?= $fmt((float) ($w['collections'] ?? 0)) ?></div>
                <div class="fin-sundays-table__cell fin-sundays-table__cell--num" role="cell">KES <?= $fmt((float) ($w['expenses'] ?? 0)) ?></div>
                <div class="fin-sundays-table__cell fin-sundays-table__cell--num" role="cell">
                    <span class="fin-badge <?= $weekBal >= 0 ? 'fin-badge--surplus' : 'fin-badge--deficit' ?> fin-badge--sm">
                        <?= $weekBal < 0 ? '-' : '' ?>KES <?= $fmt(abs($weekBal)) ?>
                    </span>
                </div>
                <div class="fin-sundays-table__cell fin-sundays-table__cell--num fin-sundays-table__cell--running" role="cell">
                    <?= $runBal < 0 ? '-' : '' ?>KES <?= $fmt(abs($runBal)) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
