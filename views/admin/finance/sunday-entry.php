<?php
/** Record Sunday — full page entry for collections and/or expenses. */
$month = $month ?? date('Y-m');
$weekDate = $weekDate ?? '';
$sundays = $sundays ?? [];
$categories = $categories ?? [];
$sessionsByDate = $sessionsByDate ?? [];
$presets = $presets ?? [];
$paymentMethods = $paymentMethods ?? [];
$panel = in_array(($panel ?? ''), ['collections', 'expenses'], true) ? $panel : '';
$returnTab = in_array(($returnTab ?? ''), ['dashboard', 'bills', 'ledger', 'reconciliation', 'budget', 'reports'], true)
    ? $returnTab
    : 'ledger';
$returnSub = in_array(($returnSub ?? ''), ['expenses', 'collections'], true) ? $returnSub : '';
$saved = isset($_GET['saved']);

$monthLabel = date('F Y', strtotime($month . '-01'));
$yearNum = (int) substr($month, 0, 4);
$monthNum = (int) substr($month, 5, 2);
$prevMonth = $monthNum > 1
    ? sprintf('%04d-%02d', $yearNum, $monthNum - 1)
    : sprintf('%04d-12', $yearNum - 1);
$nextMonth = $monthNum < 12
    ? sprintf('%04d-%02d', $yearNum, $monthNum + 1)
    : sprintf('%04d-01', $yearNum + 1);

$paymentIcons = [
    'cash' => 'banknote',
    'paybill' => 'smartphone',
    'cheque' => 'landmark',
];

$expenseGroups = [];
foreach ($categories as $slug => $meta) {
    $group = trim($meta['group_label'] ?? '')
        ?: (trim($meta['department_label'] ?? '') ?: 'Other');
    $expenseGroups[$group][] = array_merge($meta, ['slug' => $slug]);
}

$multiItemExpenseGroups = [];
$singleExpenseItems = [];
foreach ($expenseGroups as $groupName => $items) {
    if (count($items) > 1) {
        $multiItemExpenseGroups[$groupName] = $items;
    } else {
        foreach ($items as $item) {
            $singleExpenseItems[] = $item;
        }
    }
}

$backQs = array_filter([
    'tab' => $returnTab,
    'month' => $month,
    'year' => $yearNum,
    'sub' => $returnTab === 'ledger' ? ($returnSub !== '' ? $returnSub : null) : null,
]);
$backUrl = '/admin/finance?' . http_build_query($backQs);
$backLabel = match ($returnTab) {
    'dashboard' => 'Overview',
    'bills' => 'Bills',
    'reconciliation' => 'Reconciliation',
    'budget' => 'Budget',
    'reports' => 'Reports',
    default => 'Records',
};

$monthNavBase = array_filter([
    'panel' => $panel !== '' ? $panel : null,
    'return_tab' => $returnTab,
    'return_sub' => $returnSub !== '' ? $returnSub : null,
]);
$prevMonthUrl = '/admin/finance/sunday?' . http_build_query(array_merge($monthNavBase, ['month' => $prevMonth]));
$nextMonthUrl = '/admin/finance/sunday?' . http_build_query(array_merge($monthNavBase, ['month' => $nextMonth]));

$jsConfig = [
    'weekDate' => $weekDate,
    'sessionsByDate' => $sessionsByDate,
    'weeklySundays' => array_values($sundays),
    'weeklyMonth' => $month,
    'methods' => array_keys($paymentMethods),
    'categories' => array_keys($categories),
    'presets' => $presets,
    'presetTotals' => [
        'standard' => array_sum($presets['standard'] ?? []),
        'full' => array_sum($presets['full'] ?? []),
    ],
    'activePanel' => $panel !== '' ? $panel : 'expenses',
    'panelLock' => $panel !== '' ? $panel : null,
];
?>
<div class="fin-sunday-page" x-cloak x-data="sundayEntryForm(<?= htmlspecialchars(json_encode($jsConfig), ENT_QUOTES) ?>)">
    <header class="fin-sunday-top">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="fin-sunday-back">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <?= htmlspecialchars($backLabel) ?>
        </a>
        <div class="fin-sunday-top__hero">
            <div class="fin-sunday-top__copy">
                <p class="fin-sunday-top__eyebrow">Records</p>
                <h1 class="fin-sunday-top__title">Record Sunday</h1>
                <p class="fin-sunday-top__sub">
                    <?php if ($panel === 'collections'): ?>
                    Enter offering &amp; tithe for one Sunday.
                    <?php elseif ($panel === 'expenses'): ?>
                    Enter cash paid out for one Sunday.
                    <?php else: ?>
                    Enter what came in and what went out. Totals update as you type.
                    <?php endif; ?>
                </p>
            </div>
            <div class="fin-sunday-steps" aria-label="How to record">
                <div class="fin-sunday-step"><span class="fin-sunday-step__num">1</span> Pick Sunday</div>
                <div class="fin-sunday-step"><span class="fin-sunday-step__num">2</span> Enter amounts</div>
                <div class="fin-sunday-step"><span class="fin-sunday-step__num">3</span> Save</div>
            </div>
        </div>
    </header>

    <?php if ($saved): ?>
    <div class="fin-toast fin-toast--success" role="status">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        Saved. Your records and reports are updated.
    </div>
    <?php endif; ?>

    <form method="post" action="/admin/finance/sunday" class="fin-sunday-form" @submit="validateBeforeSubmit($event)">
        <input type="hidden" name="return_tab" value="<?= htmlspecialchars($returnTab) ?>">
        <input type="hidden" name="return_sub" value="<?= htmlspecialchars($returnSub) ?>">

        <section class="fin-sunday-controls">
            <div class="fin-sunday-cal">
                <div class="fin-sunday-cal__head">
                    <a href="<?= htmlspecialchars($prevMonthUrl) ?>" class="fin-sunday-cal__nav" aria-label="Previous month">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>
                    <div class="fin-sunday-cal__title">
                        <span class="fin-sunday-cal__month"><?= htmlspecialchars($monthLabel) ?></span>
                        <span class="fin-sunday-cal__caption">Choose a Sunday</span>
                    </div>
                    <a href="<?= htmlspecialchars($nextMonthUrl) ?>" class="fin-sunday-cal__nav" aria-label="Next month">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <input type="hidden" name="week_date" :value="weekDate" required>

                <div class="fin-sunday-cal__days" role="listbox" aria-label="Sundays this month">
                    <template x-for="sun in sundayDates" :key="sun">
                        <button type="button"
                                role="option"
                                class="fin-sunday-cal__day"
                                :class="{
                                    'fin-sunday-cal__day--active': weekDate === sun,
                                    'fin-sunday-cal__day--saved': sundayHasData(sun)
                                }"
                                :aria-selected="weekDate === sun"
                                :aria-label="formatLong(sun)"
                                @click="selectSunday(sun)">
                            <span class="fin-sunday-cal__dow">Sun</span>
                            <span class="fin-sunday-cal__num" x-text="dayNum(sun)"></span>
                            <span class="fin-sunday-cal__dot" aria-hidden="true"></span>
                        </button>
                    </template>
                    <?php if ($sundays === []): ?>
                    <p class="fin-sunday-cal__empty">No Sundays in this month</p>
                    <?php endif; ?>
                </div>

                <div class="fin-sunday-cal__meta">
                    <span class="fin-sunday-cal__selected" x-show="weekDate" x-cloak>
                        <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                        <span x-text="formatLong(weekDate)"></span>
                    </span>
                    <span class="fin-sunday-status" x-show="hasSavedData" x-cloak>
                        <i data-lucide="history" class="w-3.5 h-3.5"></i>
                        Editing saved entry
                    </span>
                </div>
            </div>
        </section>

        <?php if ($panel === ''): ?>
        <div class="fin-sunday-seg" role="tablist" aria-label="Collections or expenses">
            <button type="button"
                    role="tab"
                    class="fin-sunday-seg__tab"
                    :class="activePanel === 'collections' && 'fin-sunday-seg__tab--active'"
                    :aria-selected="activePanel === 'collections'"
                    @click="setActivePanel('collections')">
                <span class="fin-sunday-seg__label">Collections</span>
                <span class="fin-sunday-seg__chip" x-text="formatMoney(collectionsTotal)"></span>
            </button>
            <button type="button"
                    role="tab"
                    class="fin-sunday-seg__tab"
                    :class="activePanel === 'expenses' && 'fin-sunday-seg__tab--active'"
                    :aria-selected="activePanel === 'expenses'"
                    @click="setActivePanel('expenses')">
                <span class="fin-sunday-seg__label">Expenses</span>
                <span class="fin-sunday-seg__chip" x-text="formatMoney(expensesTotal)"></span>
            </button>
        </div>
        <?php endif; ?>

        <div class="fin-sunday-panels fin-sunday-panels--stacked">
            <section class="fin-sunday-panel fin-sunday-panel--in fin-sunday-panel--solo"
                     x-show="activePanel === 'collections'"
                     <?= $panel === 'collections' ? '' : 'x-cloak' ?>
                     role="tabpanel">
                <header class="fin-sunday-panel__bar">
                    <p class="fin-sunday-panel__hint">Offering &amp; tithe for this Sunday</p>
                    <div class="fin-sunday-panel__bar-actions">
                        <span class="fin-sunday-panel__bar-total">
                            Total <strong x-text="formatMoney(collectionsTotal)"></strong>
                        </span>
                        <button type="button" class="fin-sunday-panel__clear" @click="clearCollections()">Clear</button>
                    </div>
                </header>
                <div class="fin-amount-grid fin-amount-grid--collections">
                    <?php foreach ($paymentMethods as $method => $meta):
                        $icon = $paymentIcons[$method] ?? 'circle-dollar-sign';
                    ?>
                    <div class="fin-amt-row"
                         :class="Number(collectionFields['<?= htmlspecialchars($method) ?>']) > 0 && 'fin-amt-row--filled'">
                        <div class="fin-amt-row__meta">
                            <span class="fin-amt-row__icon fin-amt-row__icon--<?= htmlspecialchars($method) ?>">
                                <i data-lucide="<?= htmlspecialchars($icon) ?>" class="w-3.5 h-3.5"></i>
                            </span>
                            <div class="fin-amt-row__text">
                                <label class="fin-amt-row__label" for="col_<?= htmlspecialchars($method) ?>">
                                    <?= htmlspecialchars($meta['label']) ?>
                                </label>
                                <?php if (!empty($meta['desc'])): ?>
                                <span class="fin-amt-row__hint"><?= htmlspecialchars($meta['desc']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="fin-amt-row__field">
                            <span class="fin-amt-row__currency" aria-hidden="true">KES</span>
                            <input type="number"
                                   id="col_<?= htmlspecialchars($method) ?>"
                                   name="collections[<?= htmlspecialchars($method) ?>]"
                                   min="0"
                                   step="1"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   placeholder="0"
                                   class="fin-amt-row__input"
                                   x-model.number="collectionFields['<?= htmlspecialchars($method) ?>']"
                                   @input="recalc()"
                                   @focus="$el.select()"
                                   aria-label="<?= htmlspecialchars($meta['label']) ?> amount in Kenyan Shillings">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="fin-sunday-panel fin-sunday-panel--out fin-sunday-panel--solo"
                     x-show="activePanel === 'expenses'"
                     <?= $panel === 'expenses' ? '' : 'x-cloak' ?>
                     role="tabpanel">
                <header class="fin-sunday-panel__bar">
                    <p class="fin-sunday-panel__hint">Cash paid out this Sunday</p>
                    <div class="fin-sunday-panel__bar-actions">
                        <span class="fin-sunday-panel__bar-total">
                            Total <strong x-text="formatMoney(expensesTotal)"></strong>
                        </span>
                        <button type="button" class="fin-sunday-panel__clear" @click="clearExpenses()">Clear</button>
                    </div>
                </header>
                <div class="fin-expense-sections">
                    <?php foreach ($multiItemExpenseGroups as $groupName => $items): ?>
                    <section class="fin-expense-section">
                        <h3 class="fin-expense-group__title"><?= htmlspecialchars($groupName) ?></h3>
                        <div class="fin-amount-grid fin-amount-grid--expenses">
                            <?php foreach ($items as $item):
                                $slug = $item['slug'];
                            ?>
                            <div class="fin-amt-row fin-amt-row--expense"
                                 :class="Number(expenseFields['<?= htmlspecialchars($slug) ?>']) > 0 && 'fin-amt-row--filled'">
                                <div class="fin-amt-row__meta">
                                    <div class="fin-amt-row__text">
                                        <label class="fin-amt-row__label" for="exp_<?= htmlspecialchars($slug) ?>">
                                            <?= htmlspecialchars($item['label']) ?>
                                        </label>
                                        <?php if (!empty($item['hint'])): ?>
                                        <span class="fin-amt-row__hint"><?= htmlspecialchars($item['hint']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="fin-amt-row__field">
                                    <span class="fin-amt-row__currency" aria-hidden="true">KES</span>
                                    <input type="number"
                                           id="exp_<?= htmlspecialchars($slug) ?>"
                                           name="expenses[<?= htmlspecialchars($slug) ?>]"
                                           min="0"
                                           step="1"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           placeholder="0"
                                           class="fin-amt-row__input"
                                           x-model.number="expenseFields['<?= htmlspecialchars($slug) ?>']"
                                           @input="recalc()"
                                           @focus="$el.select()"
                                           aria-label="<?= htmlspecialchars($item['label']) ?> amount in Kenyan Shillings">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endforeach; ?>

                    <?php if ($singleExpenseItems): ?>
                    <div class="fin-amount-grid fin-amount-grid--expenses">
                        <?php foreach ($singleExpenseItems as $item):
                            $slug = $item['slug'];
                        ?>
                        <div class="fin-amt-row fin-amt-row--expense"
                             :class="Number(expenseFields['<?= htmlspecialchars($slug) ?>']) > 0 && 'fin-amt-row--filled'">
                            <div class="fin-amt-row__meta">
                                <div class="fin-amt-row__text">
                                    <label class="fin-amt-row__label" for="exp_<?= htmlspecialchars($slug) ?>">
                                        <?= htmlspecialchars($item['label']) ?>
                                    </label>
                                    <?php if (!empty($item['hint'])): ?>
                                    <span class="fin-amt-row__hint"><?= htmlspecialchars($item['hint']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="fin-amt-row__field">
                                <span class="fin-amt-row__currency" aria-hidden="true">KES</span>
                                <input type="number"
                                       id="exp_<?= htmlspecialchars($slug) ?>"
                                       name="expenses[<?= htmlspecialchars($slug) ?>]"
                                       min="0"
                                       step="1"
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       placeholder="0"
                                       class="fin-amt-row__input"
                                       x-model.number="expenseFields['<?= htmlspecialchars($slug) ?>']"
                                       @input="recalc()"
                                       @focus="$el.select()"
                                       aria-label="<?= htmlspecialchars($item['label']) ?> amount in Kenyan Shillings">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section class="fin-sunday-notes">
            <label for="notes" class="fin-label">
                <i data-lucide="sticky-note" class="fin-label-icon"></i>
                Notes <span class="fin-label-optional">(optional)</span>
            </label>
            <textarea id="notes"
                      name="notes"
                      rows="3"
                      class="fin-input fin-textarea"
                      x-model="notes"
                      placeholder="e.g. Cash was not banked — used directly for Sunday expenses"></textarea>
        </section>

        <footer class="fin-sunday-footer">
            <div class="fin-sunday-footer__summary" :class="weekBalance >= 0 ? 'fin-sunday-live--surplus' : 'fin-sunday-live--deficit'">
                <span class="fin-sunday-footer__label" x-text="balanceLabel + ':'"></span>
                <span class="fin-sunday-footer__value" x-text="formatMoney(weekBalance)"></span>
            </div>
            <div class="fin-sunday-footer__actions">
                <a href="<?= htmlspecialchars($backUrl) ?>" class="fin-btn fin-btn--ghost">Cancel</a>
                <button type="submit" class="fin-btn fin-btn--primary fin-btn--lg fin-btn--save">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    Save Sunday
                </button>
            </div>
        </footer>
    </form>
</div>
