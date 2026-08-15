<?php
/** Record Sunday modal — opened from Ledger / finance hero without leaving the page. */
$month = $sundayMonth ?? ($month ?? date('Y-m'));
$weekDate = $sundayWeekDate ?? '';
$sundays = $sundaySundays ?? [];
$categories = $sundayCategories ?? [];
$sessionsByDate = $sundaySessionsByDate ?? [];
$presets = $sundayPresets ?? [];
$paymentMethods = $sundayPaymentMethods ?? ($paymentMethods ?? []);
$tabKey = $tab ?? 'dashboard';
if ($tabKey === 'arrears') {
    $tabKey = 'bills';
}
if (in_array($tabKey, ['weekly', 'collections'], true)) {
    $tabKey = 'ledger';
}
if ($tabKey === 'statement') {
    $tabKey = 'reports';
}
$ledgerSub = $ledgerSub ?? (($_GET['sub'] ?? '') === 'collections' ? 'collections' : 'expenses');
$returnSub = $tabKey === 'ledger' ? $ledgerSub : '';

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
    $group = trim($meta['department_label'] ?? '') ?: 'Other';
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

$categorySlugs = array_keys($categories);
$jsConfig = [
    'weekDate' => $weekDate,
    'sessionsByDate' => $sessionsByDate,
    'methods' => array_keys($paymentMethods),
    'categories' => $categorySlugs,
    'presets' => $presets,
    'presetTotals' => [
        'standard' => array_sum($presets['standard'] ?? []),
        'full' => array_sum($presets['full'] ?? []),
    ],
];

$monthNavBase = [
    'tab' => $tabKey,
    'year' => $year ?? (int) date('Y'),
    'record' => '1',
];
if ($returnSub !== '') {
    $monthNavBase['sub'] = $returnSub;
}
$prevMonthUrl = '/admin/finance?' . http_build_query(array_merge($monthNavBase, ['month' => $prevMonth]));
$nextMonthUrl = '/admin/finance?' . http_build_query(array_merge($monthNavBase, ['month' => $nextMonth]));
?>
<div x-show="showSundayModal"
     x-cloak
     class="finance-modal-overlay"
     @keydown.escape.window="closeSundayModal()"
     @close-sunday-modal.window="closeSundayModal()"
     @sunday-form-submit.window="submitSundayAjax($event.detail.form)">
    <div class="finance-modal-backdrop" @click="closeSundayModal()"></div>
    <div class="finance-modal finance-modal--sunday" x-transition role="dialog" aria-modal="true" aria-labelledby="sunday-modal-title">
        <header class="finance-modal-header">
            <div class="finance-modal-header-text">
                <p class="finance-modal-eyebrow">Ledger</p>
                <h4 class="finance-modal-title" id="sunday-modal-title">Record Sunday</h4>
                <p class="finance-modal-subtitle">Enter collections and expenses for one Sunday — saves to the ledger.</p>
            </div>
            <button type="button"
                    @click="closeSundayModal()"
                    class="finance-modal-close"
                    aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </header>

        <template x-if="showSundayModal">
            <div class="fin-sunday-modal" x-data="sundayEntryForm(buildSundayFormConfig())">
                <form method="post" action="/admin/finance/sunday" class="fin-sunday-form fin-sunday-form--modal" @submit.prevent="validateBeforeSubmit($event); $dispatch('sunday-form-submit', { form: $event.target })">
                    <input type="hidden" name="return_tab" value="<?= htmlspecialchars($tabKey) ?>">
                    <?php if ($tabKey === 'ledger'): ?>
                    <input type="hidden" name="return_sub" :value="$root.ledgerSub || 'expenses'" value="<?= htmlspecialchars($returnSub !== '' ? $returnSub : 'expenses') ?>">
                    <?php else: ?>
                    <input type="hidden" name="return_sub" value="">
                    <?php endif; ?>

                    <div class="finance-modal-body finance-modal-body--sunday">
                        <section class="fin-sunday-controls">
                            <div class="fin-sunday-toolbar">
                                <div class="fin-sunday-month-nav">
                                    <button type="button" class="fin-sunday-month-nav__btn" aria-label="Previous month" @click.prevent="$root.shiftSundayMonth(-1)">
                                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    </button>
                                    <span class="fin-sunday-month-nav__label" x-text="$root.monthLabel"><?= htmlspecialchars($monthLabel) ?></span>
                                    <button type="button" class="fin-sunday-month-nav__btn" aria-label="Next month" @click.prevent="$root.shiftSundayMonth(1)">
                                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                    </button>
                                </div>

                                <div class="fin-sunday-date-field">
                                    <label for="modal_week_date" class="fin-sunday-date-field__label">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                        Sunday
                                    </label>
                                    <select id="modal_week_date"
                                            name="week_date"
                                            required
                                            class="fin-sunday-date-field__select"
                                            x-model="weekDate"
                                            @change="onDateChange()">
                                        <template x-for="sun in ($root.weeklySundays || Object.keys(sessionsByDate))" :key="sun">
                                            <option :value="sun" x-text="$root.formatSundayLong(sun)"></option>
                                        </template>
                                    </select>
                                </div>

                                <span class="fin-sunday-status" x-show="hasSavedData" x-cloak>
                                    <i data-lucide="history" class="w-3.5 h-3.5"></i>
                                    Editing saved entry
                                </span>
                            </div>
                        </section>

                        <div class="fin-sunday-live" :class="weekBalance >= 0 ? 'fin-sunday-live--surplus' : 'fin-sunday-live--deficit'">
                            <div class="fin-sunday-live__item">
                                <span class="fin-sunday-live__label">Money in</span>
                                <span class="fin-sunday-live__value" x-text="formatMoney(collectionsTotal)"></span>
                            </div>
                            <div class="fin-sunday-live__divider" aria-hidden="true">−</div>
                            <div class="fin-sunday-live__item">
                                <span class="fin-sunday-live__label">Money out</span>
                                <span class="fin-sunday-live__value" x-text="formatMoney(expensesTotal)"></span>
                            </div>
                            <div class="fin-sunday-live__divider" aria-hidden="true">=</div>
                            <div class="fin-sunday-live__item fin-sunday-live__item--result">
                                <span class="fin-sunday-live__label" x-text="balanceLabel"></span>
                                <span class="fin-sunday-live__value fin-sunday-live__value--lg" x-text="formatMoney(weekBalance)"></span>
                            </div>
                        </div>

                        <div class="fin-sunday-panels">
                            <section class="fin-sunday-panel fin-sunday-panel--in">
                                <header class="fin-sunday-panel__head">
                                    <div class="fin-sunday-panel__title-wrap">
                                        <span class="fin-sunday-panel__badge fin-sunday-panel__badge--in">
                                            <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                                        </span>
                                        <div>
                                            <h2 class="fin-sunday-panel__title">Collections</h2>
                                            <p class="fin-sunday-panel__sub">Offering &amp; tithe received this Sunday</p>
                                        </div>
                                    </div>
                                    <div class="fin-sunday-panel__head-actions">
                                        <div class="fin-sunday-panel__total">
                                            <span class="fin-sunday-panel__total-label">Total in</span>
                                            <span class="fin-sunday-panel__total-value" x-text="formatMoney(collectionsTotal)"></span>
                                        </div>
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
                                                <label class="fin-amt-row__label" for="modal_col_<?= htmlspecialchars($method) ?>">
                                                    <?= htmlspecialchars($meta['label']) ?>
                                                </label>
                                                <?php if (!empty($meta['desc'])): ?>
                                                <span class="fin-amt-row__hint"><?= htmlspecialchars($meta['desc']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="fin-amt-row__field">
                                            <span class="fin-amt-row__currency">KES</span>
                                            <input type="number"
                                                   id="modal_col_<?= htmlspecialchars($method) ?>"
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

                            <section class="fin-sunday-panel fin-sunday-panel--out">
                                <header class="fin-sunday-panel__head">
                                    <div class="fin-sunday-panel__title-wrap">
                                        <span class="fin-sunday-panel__badge fin-sunday-panel__badge--out">
                                            <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                                        </span>
                                        <div>
                                            <h2 class="fin-sunday-panel__title">Expenses</h2>
                                            <p class="fin-sunday-panel__sub">Cash paid out this Sunday</p>
                                        </div>
                                    </div>
                                    <div class="fin-sunday-panel__head-actions">
                                        <div class="fin-sunday-panel__total fin-sunday-panel__total--out">
                                            <span class="fin-sunday-panel__total-label">Total out</span>
                                            <span class="fin-sunday-panel__total-value" x-text="formatMoney(expensesTotal)"></span>
                                        </div>
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
                                                        <label class="fin-amt-row__label" for="modal_exp_<?= htmlspecialchars($slug) ?>">
                                                            <?= htmlspecialchars($item['label']) ?>
                                                        </label>
                                                        <?php if (!empty($item['hint'])): ?>
                                                        <span class="fin-amt-row__hint"><?= htmlspecialchars($item['hint']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="fin-amt-row__field">
                                                    <span class="fin-amt-row__currency">KES</span>
                                                    <input type="number"
                                                           id="modal_exp_<?= htmlspecialchars($slug) ?>"
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
                                                    <label class="fin-amt-row__label" for="modal_exp_<?= htmlspecialchars($slug) ?>">
                                                        <?= htmlspecialchars($item['label']) ?>
                                                    </label>
                                                    <?php if (!empty($item['hint'])): ?>
                                                    <span class="fin-amt-row__hint"><?= htmlspecialchars($item['hint']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="fin-amt-row__field">
                                                <span class="fin-amt-row__currency">KES</span>
                                                <input type="number"
                                                       id="modal_exp_<?= htmlspecialchars($slug) ?>"
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
                            <label for="modal_notes" class="fin-label">
                                <i data-lucide="sticky-note" class="fin-label-icon"></i>
                                Notes <span class="fin-label-optional">(optional)</span>
                            </label>
                            <textarea id="modal_notes"
                                      name="notes"
                                      rows="2"
                                      class="fin-input fin-textarea"
                                      x-model="notes"
                                      placeholder="e.g. Cash was not banked — used directly for Sunday expenses"></textarea>
                        </section>
                    </div>

                    <footer class="finance-modal-footer fin-sunday-modal__footer">
                        <div class="fin-sunday-footer__summary" :class="weekBalance >= 0 ? 'fin-sunday-live--surplus' : 'fin-sunday-live--deficit'">
                            <span class="fin-sunday-footer__label" x-text="balanceLabel + ':'"></span>
                            <span class="fin-sunday-footer__value" x-text="formatMoney(weekBalance)"></span>
                        </div>
                        <div class="finance-modal-actions">
                            <button type="button" @click="$dispatch('close-sunday-modal')" class="finance-btn-secondary">Cancel</button>
                            <button type="submit" class="finance-btn-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Save Sunday
                            </button>
                        </div>
                    </footer>
                </form>
            </div>
        </template>
    </div>
</div>
