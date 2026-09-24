<?php
$fmt = static fn (float $n): string => number_format($n, 0);
$tab = $tab ?? 'dashboard';
if ($tab === 'arrears') $tab = 'bills';
if ($tab === 'weekly' || $tab === 'collections') $tab = 'ledger';
if ($tab === 'reconciliation') $tab = 'dashboard';
if ($tab === 'statement') $tab = 'reports';
if ($tab === 'reports' && ($reportSub ?? '') === 'budget') $tab = 'budget';
$tabDashboard = $tab === 'dashboard';
$tabBills = $tab === 'bills';
$tabLedger = $tab === 'ledger';
$tabBudget = $tab === 'budget';
$tabReports = $tab === 'reports';
$ledgerSub = ($_GET['sub'] ?? '') === 'collections' ? 'collections' : 'expenses';
if ($tabReports) {
    $reportSub = in_array(($reportSub ?? ''), ['statement', 'position'], true)
        ? $reportSub
        : 'statement';
}
?>
<div class="fin-hub" x-cloak x-data="financeHub(<?= htmlspecialchars(json_encode($hubConfig ?? ['year' => (int) ($year ?? date('Y')), 'paymentMethods' => $paymentMethods ?? []]), ENT_QUOTES) ?>)">
    <div class="fin-ajax-toast" x-show="toast" x-cloak x-transition.opacity role="status" aria-live="polite">
        <template x-if="toast">
            <div class="fin-ajax-toast__inner" :class="toast.type === 'error' ? 'fin-ajax-toast__inner--error' : 'fin-ajax-toast__inner--success'">
                <i :data-lucide="toast.type === 'error' ? 'circle-alert' : 'circle-check'" class="w-4 h-4"></i>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    <?php require __DIR__ . '/_nav.php'; ?>

    <?php if ($tabDashboard): ?>
    <?php require __DIR__ . '/dashboard-tab.php'; ?>

    <?php elseif ($tabBills): ?>
    <div class="arrears-page fin-section">
        <h2 class="arrears-title">Outstanding Bills</h2>
        <p class="finance-tab-hint">Bills the church owes — track what's paid and what's still due.</p>

        <div class="arrears-toolbar-row">
            <div class="arrears-toolbar-left">
                <input type="search"
                       x-model="search"
                       class="arrears-search"
                       placeholder="Search bills..."
                       aria-label="Search bills">
                <span class="arrears-count" x-text="filteredArrears.length + (filteredArrears.length === 1 ? ' bill' : ' bills')"></span>
                <div class="arrears-toolbar-filters">
                    <?php
                    $monthPickerLabel = 'Filter by month incurred';
                    $monthPickerTarget = 'bills-filter';
                    $monthPickerClass = 'fin-month-picker--toolbar';
                    $monthLabel = 'All months';
                    require __DIR__ . '/_month-picker.php';
                    ?>
                    <form method="get" class="inline-flex" @submit.prevent>
                        <input type="hidden" name="tab" value="bills">
                        <select name="year"
                                :value="year"
                                @change="changeFinanceYear(Number($event.target.value))"
                                class="arrears-year-select"
                                aria-label="Year">
                            <?php
                            $yearOptions = $financeYears ?? range((int) date('Y') + 1, 2024);
                            foreach ($yearOptions as $y):
                            ?>
                            <option value="<?= (int) $y ?>" <?= (int) $year === (int) $y ? 'selected' : '' ?>><?= (int) $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            <button type="button" @click="openNewArrear()" class="arrears-btn-new">+ New Bill</button>
        </div>

        <div class="arrears-card finance-table-card">
            <div class="finance-table-caption">
                <span class="finance-table-caption-label">Outstanding bills</span>
                <span class="finance-table-caption-badge"><?= (int) $year ?></span>
                <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
            </div>
            <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Outstanding bills — scroll horizontally on small screens">
                <table class="arrears-table">
                    <thead>
                        <tr>
                            <th>Expense item</th>
                            <th>Month incurred</th>
                            <th>Date paid</th>
                            <th class="ft-th-accent ft-th--right">Amount paid</th>
                            <th class="ft-th-accent ft-th--right">Amount due</th>
                            <th class="ft-th-accent ft-th--right">Balance owing</th>
                            <th class="ft-th-accent">Status</th>
                            <th class="ft-th-actions"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="filteredArrears.length === 0">
                            <td colspan="8" class="arrears-empty">
                                <span x-show="search.trim() || billsMonthFilter">No bills match your filters.</span>
                                <span x-show="!search.trim() && !billsMonthFilter">No bills recorded for <?= (int) $year ?>. Click <strong>+ New Bill</strong> to add one.</span>
                            </td>
                        </tr>
                        <template x-for="row in paginatedArrears" :key="row.id">
                            <tr class="arrears-row">
                                <td>
                                    <span class="arrears-accent" x-text="row.category_label || row.expense_item"></span>
                                </td>
                                <td class="arrears-muted" x-text="formatMonthIncurred(row.month_incurred)"></td>
                                <td>
                                    <template x-if="dateMain(row.date_paid)">
                                        <div class="arrears-date">
                                            <span class="arrears-date-main" x-text="dateMain(row.date_paid)"></span>
                                            <span class="arrears-date-sub" x-text="dateYear(row.date_paid)"></span>
                                        </div>
                                    </template>
                                    <template x-if="!dateMain(row.date_paid)">
                                        <span class="arrears-muted">—</span>
                                    </template>
                                </td>
                                <td class="ft-td-accent">
                                    <template x-if="isInlineEditing('arrear', row.id, 'amount_paid')">
                                        <input type="number"
                                               class="arrears-inline-input"
                                               min="0"
                                               step="1"
                                               inputmode="numeric"
                                               x-model.number="inlineEdit.value"
                                               @click.stop
                                               @keydown.enter.prevent="commitInlineEdit(row)"
                                               @keydown.escape.prevent="cancelInlineEdit()"
                                               @blur="commitInlineEdit(row)"
                                               x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                               :aria-label="'Edit amount paid for ' + (row.expense_item || 'bill')">
                                    </template>
                                    <template x-if="!isInlineEditing('arrear', row.id, 'amount_paid')">
                                        <button type="button"
                                                class="arrears-amount arrears-amount--editable"
                                                :class="Number(row.amount_paid) > 0 ? '' : 'arrears-amount--muted'"
                                                @click.stop="startInlineEdit(row, 'amount_paid')"
                                                x-text="formatMoneyPlain(row.amount_paid)"
                                                title="Click to edit amount paid"
                                                :aria-label="'Edit amount paid for ' + (row.expense_item || 'bill')"></button>
                                    </template>
                                </td>
                                <td class="ft-td-accent">
                                    <template x-if="isInlineEditing('arrear', row.id, 'amount_due')">
                                        <input type="number"
                                               class="arrears-inline-input"
                                               min="0"
                                               step="1"
                                               inputmode="numeric"
                                               x-model.number="inlineEdit.value"
                                               @click.stop
                                               @keydown.enter.prevent="commitInlineEdit(row)"
                                               @keydown.escape.prevent="cancelInlineEdit()"
                                               @blur="commitInlineEdit(row)"
                                               x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                               :aria-label="'Edit amount due for ' + (row.expense_item || 'bill')">
                                    </template>
                                    <template x-if="!isInlineEditing('arrear', row.id, 'amount_due')">
                                        <button type="button"
                                                class="arrears-amount arrears-amount--editable"
                                                @click.stop="startInlineEdit(row, 'amount_due')"
                                                x-text="formatMoneyPlain(row.amount_due)"
                                                title="Click to edit amount due"
                                                :aria-label="'Edit amount due for ' + (row.expense_item || 'bill')"></button>
                                    </template>
                                </td>
                                <td class="ft-td-accent">
                                    <span class="arrears-amount"
                                          :class="Number(row.balance_owing) > 0 ? 'arrears-amount--owing' : ''"
                                          x-text="formatMoneyPlain(row.balance_owing)"
                                          title="Balance owing updates automatically"></span>
                                </td>
                                <td class="ft-td-accent">
                                    <span class="arrears-status" :class="statusClass(row.payment_status)" x-text="statusLabel(row.payment_status)"></span>
                                </td>
                                <td class="arrears-actions ft-td-actions"
                                    :class="openMenu === row.id && 'weekly-actions--open'">
                                    <button type="button"
                                            class="arrears-view-btn arrears-view-btn--icon"
                                            @click.stop="toggleMenu(row.id, $event)"
                                            :aria-expanded="openMenu === row.id"
                                            :aria-label="'Actions for ' + row.expense_item"
                                            title="Actions">
                                        <i data-lucide="ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="finance-table-footer">
                            <td colspan="3" class="finance-table-footer-label">Year totals</td>
                            <td class="ft-td-accent">
                                <span class="finance-table-footer-amount" x-text="'KES ' + formatMoneyPlain(arrearsTotals.paid)"></span>
                            </td>
                            <td class="ft-td-accent">
                                <span class="finance-table-footer-amount" x-text="'KES ' + formatMoneyPlain(arrearsTotals.due)"></span>
                            </td>
                            <td class="ft-td-accent">
                                <span class="finance-table-footer-amount finance-table-footer-amount--grand" x-text="'KES ' + formatMoneyPlain(arrearsTotals.balance)"></span>
                            </td>
                            <td class="ft-td-accent"></td>
                            <td class="ft-td-actions"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php
            $pageKey = 'arrearsPage';
            $listKey = 'filteredArrears';
            $itemLabel = 'bills';
            $navLabel = 'Bills pages';
            require __DIR__ . '/../partials/table-pagination.php';
            ?>
        </div>
    </div>

    <!-- View arrear modal -->
    <div x-show="viewRow"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="viewRow = null">
        <div class="finance-modal-backdrop" @click="viewRow = null"></div>
        <div class="finance-modal finance-modal--wide" x-transition>
            <template x-if="viewRow">
                <div>
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Bill details</p>
                            <h4 class="finance-modal-title" x-text="viewRow.expense_item"></h4>
                            <span class="arrear-view-status"
                                  :class="statusClass(viewRow.payment_status)"
                                  x-text="statusLabel(viewRow.payment_status)"></span>
                        </div>
                        <button type="button"
                                @click="viewRow = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body arrear-view-body">
                        <div class="arrear-view-stats">
                            <div class="arrear-view-stat">
                                <p class="arrear-view-stat-label">Amount due</p>
                                <p class="arrear-view-stat-value" x-text="formatMoney(viewRow.amount_due)"></p>
                            </div>
                            <div class="arrear-view-stat arrear-view-stat--balance"
                                 :class="Number(viewRow.balance_owing) > 0 ? 'arrear-view-stat--owing' : 'arrear-view-stat--clear'">
                                <p class="arrear-view-stat-label">Balance owing</p>
                                <p class="arrear-view-stat-value" x-text="formatMoney(viewRow.balance_owing)"></p>
                            </div>
                            <div class="arrear-view-stat">
                                <p class="arrear-view-stat-label">Amount paid</p>
                                <p class="arrear-view-stat-value arrear-view-stat-value--paid" x-text="formatMoney(viewRow.amount_paid)"></p>
                            </div>
                        </div>
                        <div class="finance-detail-grid">
                            <div class="finance-detail-item">
                                <p class="finance-detail-label">Department</p>
                                <p class="finance-detail-value" x-text="viewRow.group_label || '—'"></p>
                            </div>
                            <div class="finance-detail-item">
                                <p class="finance-detail-label">Category</p>
                                <p class="finance-detail-value" x-text="viewRow.department_label || '—'"></p>
                            </div>
                            <div class="finance-detail-item">
                                <p class="finance-detail-label">Expense item</p>
                                <p class="finance-detail-value" x-text="viewRow.category_label || viewRow.expense_item"></p>
                            </div>
                            <div class="finance-detail-item">
                                <p class="finance-detail-label">Month incurred</p>
                                <p class="finance-detail-value" x-text="formatMonthIncurred(viewRow.month_incurred)"></p>
                            </div>
                            <div class="finance-detail-item">
                                <p class="finance-detail-label">Date paid</p>
                                <p class="finance-detail-value" x-text="formatDate(viewRow.date_paid)"></p>
                            </div>
                            <div class="finance-detail-item finance-detail-item--full">
                                <p class="finance-detail-label">Paid by / reference</p>
                                <p class="finance-detail-value" x-text="viewRow.paid_by_ref || '—'"></p>
                            </div>
                            <div class="finance-detail-item finance-detail-item--full" x-show="viewRow.notes">
                                <p class="finance-detail-label">Notes</p>
                                <p class="finance-detail-value finance-detail-notes" x-text="viewRow.notes"></p>
                            </div>
                        </div>
                    </div>
                    <footer class="finance-modal-footer">
                        <form :action="'/admin/finance/arrears/' + viewRow.id + '/delete'"
                              method="post"
                              @submit.prevent="deleteArrearAjax(viewRow.id)"
                              class="finance-modal-delete-form">
                            <input type="hidden" name="budget_year" :value="year">
                            <button type="submit" class="finance-btn-danger">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                Delete
                            </button>
                        </form>
                        <div class="finance-modal-actions">
                            <button type="button" @click="openRecordPayment(viewRow.id); viewRow = null" class="finance-btn-primary">
                                <i data-lucide="banknote" class="w-4 h-4"></i>
                                Record payment
                            </button>
                        </div>
                    </footer>
                </div>
            </template>
        </div>
    </div>

    <!-- Record payment modal -->
    <div x-show="paymentRow"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="paymentRow = null">
        <div class="finance-modal-backdrop" @click="paymentRow = null"></div>
        <div class="finance-modal finance-modal--wide" x-transition>
            <template x-if="paymentRow">
                <form method="post"
                      :action="'/admin/finance/arrears/' + paymentRow.id"
                      id="arrear-payment-form"
                      @submit.prevent="submitArrearPayment($event)">
                    <input type="hidden" name="budget_year" :value="paymentRow.budget_year">
                    <input type="hidden" name="department_id" :value="paymentRow.department_id">
                    <input type="hidden" name="category_id" :value="paymentRow.category_id">
                    <input type="hidden" name="expense_item" :value="paymentRow.expense_item">
                    <input type="hidden" name="month_incurred" :value="paymentRow.month_incurred">
                    <input type="hidden" name="amount_due" :value="paymentRow.amount_due">
                    <input type="hidden" name="amount_paid" :value="paymentComputedPaid">
                    <input type="hidden" name="notes" :value="paymentRow.notes || ''">
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Bills</p>
                            <h4 class="finance-modal-title">Record payment</h4>
                            <p class="finance-modal-subtitle" x-text="paymentRow.expense_item"></p>
                        </div>
                        <button type="button"
                                @click="paymentRow = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body">
                        <div class="arrear-view-stats arrear-view-stats--compact">
                            <div class="arrear-view-stat">
                                <p class="arrear-view-stat-label">Amount due</p>
                                <p class="arrear-view-stat-value" x-text="formatMoney(paymentRow.amount_due)"></p>
                            </div>
                            <div class="arrear-view-stat">
                                <p class="arrear-view-stat-label">Already paid</p>
                                <p class="arrear-view-stat-value arrear-view-stat-value--paid" x-text="formatMoney(paymentRow.original_amount_paid)"></p>
                            </div>
                            <div class="arrear-view-stat arrear-view-stat--balance"
                                 :class="paymentComputedBalance > 0 ? 'arrear-view-stat--owing' : 'arrear-view-stat--clear'">
                                <p class="arrear-view-stat-label">Balance owing</p>
                                <p class="arrear-view-stat-value" x-text="formatMoney(paymentComputedBalance)"></p>
                            </div>
                        </div>
                        <div class="arrear-payment-panel finance-field--full">
                            <p class="arrear-payment-panel-title">New payment</p>
                            <p class="arrear-payment-panel-hint">Add a payment toward this bill — totals update instantly.</p>
                            <div class="arrear-payment-panel-grid">
                                <div class="finance-field">
                                    <label class="finance-label" for="payment-record-amount">Payment amount (KES)</label>
                                    <input type="number"
                                           id="payment-record-amount"
                                           min="0"
                                           step="0.01"
                                           required
                                           class="finance-input finance-input--highlight"
                                           x-model="paymentRow.record_payment"
                                           x-ref="paymentAmountInput"
                                           placeholder="0">
                                </div>
                                <div class="finance-field">
                                    <label class="finance-label" for="payment-date-paid">Payment date</label>
                                    <input type="date"
                                           id="payment-date-paid"
                                           name="date_paid"
                                           required
                                           class="finance-input"
                                           x-model="paymentRow.date_paid">
                                </div>
                            </div>
                            <div class="finance-field finance-field--full">
                                <label class="finance-label" for="payment-paid-by">Paid by / reference</label>
                                <input type="text"
                                       id="payment-paid-by"
                                       name="paid_by_ref"
                                       class="finance-input"
                                       x-model="paymentRow.paid_by_ref"
                                       placeholder="e.g. M-Pesa ref, treasurer name">
                            </div>
                            <div class="arrear-payment-live">
                                <div class="arrear-payment-live-item">
                                    <span class="arrear-payment-live-label">Total paid after</span>
                                    <span class="arrear-payment-live-value arrear-payment-live-value--paid"
                                          x-text="formatMoney(paymentComputedPaid)"></span>
                                </div>
                                <div class="arrear-payment-live-item">
                                    <span class="arrear-payment-live-label">Balance owing</span>
                                    <span class="arrear-payment-live-value"
                                          :class="paymentComputedBalance > 0 ? 'arrear-payment-live-value--owing' : 'arrear-payment-live-value--clear'"
                                          x-text="formatMoney(paymentComputedBalance)"></span>
                                </div>
                                <div class="arrear-payment-live-item">
                                    <span class="arrear-payment-live-label">Status</span>
                                    <span class="arrears-status"
                                          :class="statusClass(paymentComputedStatus)"
                                          x-text="statusLabel(paymentComputedStatus)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <footer class="finance-modal-footer">
                        <div class="finance-modal-actions finance-modal-actions--end">
                            <button type="button" @click="paymentRow = null" class="finance-btn-secondary">Cancel</button>
                            <button type="submit" class="finance-btn-primary">
                                <i data-lucide="banknote" class="w-4 h-4"></i>
                                Save payment
                            </button>
                        </div>
                    </footer>
                </form>
            </template>
        </div>
    </div>

    <!-- Edit arrear modal -->
    <div x-show="editRow"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="editRow = null">
        <div class="finance-modal-backdrop" @click="editRow = null"></div>
        <div class="finance-modal finance-modal--xl" x-transition>
            <template x-if="editRow">
                <form method="post"
                      :action="'/admin/finance/arrears/' + editRow.id"
                      :key="'arrear-edit-' + editFormKey"
                      id="arrear-edit-form"
                      @submit.prevent="submitArrearEdit($event)">
                    <input type="hidden" name="budget_year" :value="editRow.budget_year">
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Bills</p>
                            <h4 class="finance-modal-title">Edit expense</h4>
                            <p class="finance-modal-subtitle">Update the title, category, amounts, and notes for this bill.</p>
                        </div>
                        <button type="button"
                                @click="editRow = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body finance-modal-body--grid">
                        <div class="finance-field finance-field--full">
                            <label class="finance-label" for="edit-expense-title">Expense title</label>
                            <input type="text"
                                   id="edit-expense-title"
                                   name="expense_item"
                                   required
                                   class="finance-input"
                                   x-model="editRow.expense_item"
                                   placeholder="e.g. Beisa Hotel (2025)">
                            <p class="finance-field-hint">This is the name shown in the outstanding bills table.</p>
                        </div>
                        <div class="finance-field finance-field--full">
                            <label class="finance-label" for="edit-expense-group">Category</label>
                            <select id="edit-expense-group"
                                    required
                                    class="finance-input"
                                    x-model="editRow.expense_group"
                                    @change="onEditGroupChange()">
                                <option value="">Select category…</option>
                                <template x-for="grp in expenseGroups" :key="grp.slug">
                                    <option :value="grp.slug"
                                            :selected="editRow.expense_group === grp.slug"
                                            x-text="grp.label"></option>
                                </template>
                            </select>
                        </div>
                        <div class="finance-field finance-field--full" x-show="editRow.expense_group" x-cloak>
                            <label class="finance-label" for="edit-expense-item">Expense item</label>
                            <select id="edit-expense-item"
                                    name="category_id"
                                    required
                                    class="finance-input"
                                    x-model="editRow.category_id"
                                    @change="onEditExpenseItemChange()">
                                <option value="">Select expense item…</option>
                                <template x-for="cat in expenseItemsForGroup(editRow.expense_group, editRow.category_id)" :key="cat.id">
                                    <option :value="String(cat.id)"
                                            :selected="String(editRow.category_id) === String(cat.id)"
                                            x-text="cat.label"></option>
                                </template>
                                <option value="__new__">+ Add custom expense item…</option>
                            </select>
                            <input type="hidden" name="department_id" :value="editRow.department_id">
                        </div>
                        <div class="finance-field finance-field--full"
                             x-show="editRow.expense_group && editRow.category_id === '__new__'"
                             x-cloak>
                            <label class="finance-label" for="edit-new-category">Custom expense item</label>
                            <input type="text"
                                   id="edit-new-category"
                                   name="new_category_label"
                                   class="finance-input"
                                   x-model="editRow.new_category_label"
                                   placeholder="e.g. Generator fuel">
                            <p class="finance-field-hint">Saved to the database for future selections.</p>
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="edit-month-incurred">Month incurred</label>
                            <input type="hidden"
                                   id="edit-month-incurred"
                                   name="month_incurred"
                                   required
                                   :value="editRow.month_incurred">
                            <?php
                            $monthPickerLabel = 'Month incurred';
                            $monthPickerTarget = 'bill-edit';
                            $monthPickerClass = 'fin-month-picker--field';
                            $monthLabel = '';
                            require __DIR__ . '/_month-picker.php';
                            ?>
                            <p class="finance-field-hint">Pick the month this bill relates to.</p>
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="edit-amount-due">Amount due (KES)</label>
                            <input type="number"
                                   id="edit-amount-due"
                                   name="amount_due"
                                   min="0"
                                   step="0.01"
                                   required
                                   class="finance-input"
                                   x-model="editRow.amount_due">
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="edit-amount-paid">Total amount paid (KES)</label>
                            <input type="number"
                                   id="edit-amount-paid"
                                   name="amount_paid"
                                   min="0"
                                   step="0.01"
                                   required
                                   class="finance-input"
                                   x-model="editRow.amount_paid">
                            <p class="finance-field-hint">Use <strong>Record payment</strong> from the menu to add a new payment instead.</p>
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="edit-date-paid">Date paid</label>
                            <input type="date"
                                   id="edit-date-paid"
                                   name="date_paid"
                                   class="finance-input"
                                   x-model="editRow.date_paid">
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="edit-paid-by">Paid by / reference</label>
                            <input type="text"
                                   id="edit-paid-by"
                                   name="paid_by_ref"
                                   class="finance-input"
                                   x-model="editRow.paid_by_ref">
                        </div>
                        <div class="arrear-payment-live finance-field--full">
                            <div class="arrear-payment-live-item">
                                <span class="arrear-payment-live-label">Balance owing</span>
                                <span class="arrear-payment-live-value"
                                      :class="editComputedBalance > 0 ? 'arrear-payment-live-value--owing' : 'arrear-payment-live-value--clear'"
                                      x-text="formatMoney(editComputedBalance)"></span>
                            </div>
                            <div class="arrear-payment-live-item">
                                <span class="arrear-payment-live-label">Status</span>
                                <span class="arrears-status"
                                      :class="statusClass(editComputedStatus)"
                                      x-text="statusLabel(editComputedStatus)"></span>
                            </div>
                        </div>
                        <div class="finance-field finance-field--full">
                            <label class="finance-label" for="edit-notes">Notes</label>
                            <textarea id="edit-notes"
                                      name="notes"
                                      rows="3"
                                      class="finance-input finance-textarea"
                                      x-model="editRow.notes"></textarea>
                        </div>
                    </div>
                    <footer class="finance-modal-footer">
                        <div class="finance-modal-actions finance-modal-actions--end">
                            <button type="button" @click="editRow = null" class="finance-btn-secondary">Cancel</button>
                            <button type="submit" class="finance-btn-primary">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                Save changes
                            </button>
                        </div>
                    </footer>
                </form>
            </template>
        </div>
    </div>

    <!-- New arrear modal -->
    <div x-show="newArrear"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="newArrear = null">
        <div class="finance-modal-backdrop" @click="newArrear = null"></div>
        <div class="finance-modal finance-modal--xl" x-transition>
            <template x-if="newArrear">
                <form method="post" action="/admin/finance/arrears" @submit="submitNewArrear($event)">
                    <input type="hidden" name="budget_year" :value="newArrear.budget_year">
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Bills</p>
                            <h4 class="finance-modal-title">New bill</h4>
                        </div>
                        <button type="button"
                                @click="newArrear = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body finance-modal-body--grid">
                        <div class="finance-field finance-field--full">
                            <label class="finance-label" for="new-expense-group">Category</label>
                            <select id="new-expense-group"
                                    required
                                    class="finance-input"
                                    x-model="newArrear.expense_group"
                                    @change="onNewGroupChange()">
                                <option value="">Select category…</option>
                                <template x-for="grp in expenseGroups" :key="grp.slug">
                                    <option :value="grp.slug" x-text="grp.label"></option>
                                </template>
                            </select>
                        </div>
                        <div class="finance-field finance-field--full" x-show="newArrear.expense_group" x-cloak>
                            <label class="finance-label" for="new-expense-item">Expense item</label>
                            <select id="new-expense-item"
                                    name="category_id"
                                    required
                                    class="finance-input"
                                    x-model="newArrear.category_id"
                                    @change="onNewExpenseItemChange()">
                                <option value="">Select expense item…</option>
                                <template x-for="cat in expenseItemsForGroup(newArrear.expense_group)" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.label"></option>
                                </template>
                                <option value="__new__">+ Add custom expense item…</option>
                            </select>
                            <input type="hidden" name="department_id" :value="newArrear.department_id">
                        </div>
                        <div class="finance-field finance-field--full"
                             x-show="newArrear.expense_group && newArrear.category_id === '__new__'"
                             x-cloak>
                            <label class="finance-label" for="new-new-category">Custom expense item</label>
                            <input type="text"
                                   id="new-new-category"
                                   name="new_category_label"
                                   class="finance-input"
                                   x-model="newArrear.new_category_label"
                                   placeholder="e.g. Generator fuel">
                            <p class="finance-field-hint">Saved to the database for future selections.</p>
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="new-month-incurred">Month incurred</label>
                            <input type="hidden"
                                   id="new-month-incurred"
                                   name="month_incurred"
                                   required
                                   :value="newArrear.month_incurred">
                            <?php
                            $monthPickerLabel = 'Month incurred';
                            $monthPickerTarget = 'bill-new';
                            $monthPickerClass = 'fin-month-picker--field';
                            $monthLabel = '';
                            require __DIR__ . '/_month-picker.php';
                            ?>
                            <p class="finance-field-hint">Pick the month this bill relates to.</p>
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="new-amount-due">Amount due (KES)</label>
                            <input type="number"
                                   id="new-amount-due"
                                   name="amount_due"
                                   min="0"
                                   step="0.01"
                                   required
                                   class="finance-input"
                                   x-model="newArrear.amount_due"
                                   placeholder="0">
                        </div>
                        <div class="arrear-payment-panel finance-field--full">
                            <p class="arrear-payment-panel-title">Payment details</p>
                            <p class="arrear-payment-panel-hint">Optional — enter any amount already paid toward this expense.</p>
                            <div class="arrear-payment-panel-grid">
                                <div class="finance-field">
                                    <label class="finance-label" for="new-amount-paid">Amount paid (KES)</label>
                                    <input type="number"
                                           id="new-amount-paid"
                                           name="amount_paid"
                                           min="0"
                                           step="0.01"
                                           class="finance-input finance-input--highlight"
                                           x-model="newArrear.amount_paid"
                                           placeholder="0">
                                </div>
                                <div class="finance-field">
                                    <label class="finance-label" for="new-date-paid">Date paid</label>
                                    <input type="date"
                                           id="new-date-paid"
                                           name="date_paid"
                                           class="finance-input"
                                           x-model="newArrear.date_paid">
                                </div>
                            </div>
                            <div class="arrear-payment-live">
                                <div class="arrear-payment-live-item">
                                    <span class="arrear-payment-live-label">Total paid</span>
                                    <span class="arrear-payment-live-value arrear-payment-live-value--paid"
                                          x-text="formatMoney(newArrearComputedPaid)"></span>
                                </div>
                                <div class="arrear-payment-live-item">
                                    <span class="arrear-payment-live-label">Balance owing</span>
                                    <span class="arrear-payment-live-value"
                                          :class="newArrearComputedBalance > 0 ? 'arrear-payment-live-value--owing' : 'arrear-payment-live-value--clear'"
                                          x-text="formatMoney(newArrearComputedBalance)"></span>
                                </div>
                                <div class="arrear-payment-live-item">
                                    <span class="arrear-payment-live-label">Status</span>
                                    <span class="arrears-status"
                                          :class="statusClass(newArrearComputedStatus)"
                                          x-text="statusLabel(newArrearComputedStatus)"></span>
                                </div>
                            </div>
                        </div>
                        <div class="finance-field finance-field--full">
                            <label class="finance-label" for="new-paid-by">Paid by / reference</label>
                            <input type="text"
                                   id="new-paid-by"
                                   name="paid_by_ref"
                                   class="finance-input"
                                   x-model="newArrear.paid_by_ref"
                                   placeholder="Name or payment reference">
                        </div>
                        <div class="finance-field finance-field--full">
                            <label class="finance-label" for="new-notes">Notes <span class="finance-label-optional">(optional)</span></label>
                            <textarea id="new-notes"
                                      name="notes"
                                      rows="3"
                                      class="finance-input finance-textarea"
                                      x-model="newArrear.notes"
                                      placeholder="Any additional details"></textarea>
                        </div>
                    </div>
                    <footer class="finance-modal-footer">
                        <div class="finance-modal-actions finance-modal-actions--end">
                            <button type="button" @click="newArrear = null" class="finance-btn-secondary">Cancel</button>
                            <button type="submit" class="finance-btn-primary">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                Save entry
                            </button>
                        </div>
                    </footer>
                </form>
            </template>
        </div>
    </div>

    <?php elseif ($tabLedger): ?>
    <?php
    $sundays = $weekly['sundays'] ?? ($weeklyCollections['sundays'] ?? []);
    $collectionSundays = $weeklyCollections['sundays'] ?? $sundays;
    if ($collectionSundays === [] && $sundays !== []) {
        $collectionSundays = $sundays;
    }
    if ($sundays === [] && $collectionSundays !== []) {
        $sundays = $collectionSundays;
    }
    $monthLabel = date('F Y', strtotime($month . '-01'));
    ?>
    <div class="fin-ledger">
        <div class="fin-subtabs no-print" role="tablist" aria-label="Sundays view">
            <button type="button"
                    class="fin-subtabs__item"
                    :class="ledgerSub === 'expenses' && 'fin-subtabs__item--active'"
                    role="tab"
                    :aria-selected="ledgerSub === 'expenses'"
                    @click="setLedgerSub('expenses')">
                <i data-lucide="wallet" aria-hidden="true"></i>
                Expenses
            </button>
            <button type="button"
                    class="fin-subtabs__item"
                    :class="ledgerSub === 'collections' && 'fin-subtabs__item--active'"
                    role="tab"
                    :aria-selected="ledgerSub === 'collections'"
                    @click="setLedgerSub('collections')">
                <i data-lucide="hand-coins" aria-hidden="true"></i>
                Collections
            </button>
        </div>

    <div class="arrears-page weekly-page fin-section" x-show="ledgerSub === 'expenses'" x-cloak>
        <h2 class="arrears-title">Sunday expenses</h2>
        <p class="finance-tab-hint">Money spent each Sunday — use Record Sunday to enter or update.</p>

        <div class="arrears-toolbar-row">
            <div class="arrears-toolbar-left">
                <input type="search"
                       x-model="weeklySearch"
                       class="arrears-search"
                       placeholder="Search categories..."
                       aria-label="Search expense categories">
                <span class="arrears-count" x-text="filteredWeekly.length + (filteredWeekly.length === 1 ? ' category' : ' categories')"></span>
                <form method="get" class="inline-flex" @submit.prevent>
                    <input type="hidden" name="tab" value="ledger">
                    <input type="hidden" name="sub" :value="ledgerSub" value="expenses">
                    <input type="hidden" name="year" :value="year" value="<?= (int) $year ?>">
                    <input type="hidden" name="month" :value="weeklyMonth" value="<?= htmlspecialchars($month ?? '') ?>">
                    <?php
                    $monthPickerLabel = 'Budget month';
                    require __DIR__ . '/_month-picker.php';
                    ?>
                </form>
            </div>
            <div class="weekly-toolbar-actions">
                <a href="/admin/finance/sunday?month=<?= htmlspecialchars($month ?? date('Y-m')) ?>&amp;panel=expenses&amp;return_tab=ledger&amp;return_sub=expenses"
                   class="arrears-btn-new">Record Sunday</a>
                <button type="button" @click="openCategoryForm()" class="arrears-btn-outline">Add category</button>
            </div>
        </div>

        <div class="arrears-card finance-table-card">
            <div class="finance-table-caption">
                <span class="finance-table-caption-label">Weekly expenses</span>
                <span class="finance-table-caption-badge" x-text="monthLabel"><?= htmlspecialchars($monthLabel) ?></span>
                <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
            </div>
            <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Weekly expenses — scroll horizontally on small screens">
                <table class="arrears-table weekly-table">
                    <thead>
                        <tr>
                            <th class="weekly-col-category">Category</th>
                            <template x-for="(sun, index) in weeklySundays" :key="sun">
                                <th class="weekly-col-sunday">
                                    <span class="weekly-sun-head-date" x-text="formatSundayShort(sun)"></span>
                                    <span class="weekly-sun-head-label" x-text="'Sun ' + (index + 1)"></span>
                                </th>
                            </template>
                            <th class="weekly-col-total">Total</th>
                            <th class="weekly-col-actions"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="filteredWeekly.length === 0">
                            <td :colspan="weeklySundays.length + 3" class="arrears-empty">
                                <span x-show="weeklySearch.trim()">No categories match your search.</span>
                                <span x-show="!weeklySearch.trim()">No expense categories yet. Click <strong>Add expenses</strong> to create one.</span>
                            </td>
                        </tr>
                        <template x-for="row in paginatedWeekly" :key="row.slug">
                            <tr class="arrears-row">
                                <td class="weekly-col-category">
                                    <span class="arrears-accent" x-text="row.label"></span>
                                    <span class="block text-xs arrears-muted mt-0.5" x-show="row.hint" x-text="row.hint"></span>
                                </td>
                                <template x-for="sun in weeklySundays" :key="row.slug + '-' + sun">
                                    <td class="weekly-amount-cell">
                                        <template x-if="isInlineEditing('weeklyExpense', row.slug, sun)">
                                            <input type="number"
                                                   class="arrears-inline-input"
                                                   min="0"
                                                   step="1"
                                                   inputmode="numeric"
                                                   x-model.number="inlineEdit.value"
                                                   @click.stop
                                                   @keydown.enter.prevent="commitWeeklyInlineEdit('weeklyExpense', row.slug)"
                                                   @keydown.escape.prevent="cancelInlineEdit()"
                                                   @blur="commitWeeklyInlineEdit('weeklyExpense', row.slug)"
                                                   x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                                   :aria-label="'Edit ' + row.label + ' for ' + formatSundayShort(sun)">
                                        </template>
                                        <template x-if="!isInlineEditing('weeklyExpense', row.slug, sun)">
                                            <button type="button"
                                                    class="arrears-amount arrears-amount--editable"
                                                    :class="Number(row.amounts?.[sun] || 0) > 0 ? '' : 'arrears-amount--muted'"
                                                    @click.stop="startWeeklyInlineEdit('weeklyExpense', row.slug, sun, row.amounts?.[sun])"
                                                    x-text="formatMoneyPlain(row.amounts?.[sun])"
                                                    title="Click to edit amount"
                                                    :aria-label="'Edit ' + row.label + ' for ' + formatSundayShort(sun)"></button>
                                        </template>
                                    </td>
                                </template>
                                <td class="weekly-amount-cell weekly-col-total">
                                    <span class="arrears-amount weekly-total-cell"
                                          :class="Number(row.total) > 0 ? '' : 'arrears-amount--muted'"
                                          x-text="formatMoneyPlain(row.total)"></span>
                                </td>
                                <td class="arrears-actions weekly-col-actions"
                                    :class="weeklyMenu === row.slug && 'weekly-actions--open'">
                                    <button type="button"
                                            class="arrears-view-btn arrears-view-btn--icon"
                                            @click.stop="toggleWeeklyMenu(row.slug, $event)"
                                            :aria-expanded="weeklyMenu === row.slug"
                                            :aria-label="'Actions for ' + row.label"
                                            title="Actions">
                                        <i data-lucide="ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="finance-table-footer weekly-footer">
                            <td class="weekly-col-category finance-table-footer-label">Weekly total</td>
                            <template x-for="sun in weeklySundays" :key="'ft-' + sun">
                                <td class="weekly-amount-cell">
                                    <span class="arrears-amount finance-table-footer-amount"
                                          x-text="formatMoneyPlain(filteredWeeklyWeekTotals[sun])"></span>
                                </td>
                            </template>
                            <td class="weekly-amount-cell weekly-col-total ft-td-accent">
                                <span class="arrears-amount finance-table-footer-amount finance-table-footer-amount--grand"
                                      x-text="formatMoneyPlain(filteredWeeklyMonthTotal)"></span>
                            </td>
                            <td class="weekly-col-actions ft-td-actions"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php
            $pageKey = 'weeklyPage';
            $listKey = 'filteredWeekly';
            $itemLabel = 'categories';
            $navLabel = 'Weekly expenses pages';
            require __DIR__ . '/../partials/table-pagination.php';
            ?>
        </div>
    </div>

    <div class="arrears-page collections-page fin-section" x-show="ledgerSub === 'collections'" x-cloak>
        <h2 class="arrears-title">Sunday collections</h2>
        <p class="finance-tab-hint">Giving received each Sunday by payment method.</p>

        <div class="arrears-toolbar-row">
            <div class="arrears-toolbar-left">
                <span class="arrears-count" x-text="weeklyCollectionRows.length + ' methods'"></span>
                <form method="get" class="inline-flex items-center gap-2" @submit.prevent>
                    <input type="hidden" name="tab" value="ledger">
                    <input type="hidden" name="sub" :value="ledgerSub" value="collections">
                    <input type="hidden" name="year" :value="year" value="<?= (int) $year ?>">
                    <input type="hidden" name="month" :value="weeklyMonth" value="<?= htmlspecialchars($month ?? '') ?>">
                    <?php
                    $monthPickerLabel = 'Month';
                    require __DIR__ . '/_month-picker.php';
                    ?>
                </form>
            </div>
            <a href="/admin/finance/sunday?month=<?= htmlspecialchars($month ?? date('Y-m')) ?>&amp;panel=collections&amp;return_tab=ledger&amp;return_sub=collections"
               class="arrears-btn-new">Record Sunday</a>
        </div>

        <div class="arrears-card finance-table-card">
            <div class="finance-table-caption">
                <span class="finance-table-caption-label">Sunday collections</span>
                <span class="finance-table-caption-badge" x-text="monthLabel"><?= htmlspecialchars($monthLabel) ?></span>
                <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
            </div>
            <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Weekly collections grid">
                <table class="arrears-table weekly-table">
                    <thead>
                        <tr>
                            <th class="weekly-col-category">Method</th>
                            <template x-for="(sun, index) in weeklyCollectionSundays" :key="sun">
                                <th class="weekly-col-sunday">
                                    <span class="weekly-sun-head-date" x-text="formatSundayShort(sun)"></span>
                                    <span class="weekly-sun-head-label" x-text="'Sun ' + (index + 1)"></span>
                                </th>
                            </template>
                            <th class="weekly-col-sunday" x-show="weeklyCollectionSundays.length === 0">No Sundays</th>
                            <th class="weekly-col-total">Total</th>
                            <th class="weekly-col-actions"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="weeklyCollectionRows.length === 0">
                            <td :colspan="Math.max(weeklyCollectionSundays.length, 1) + 3" class="arrears-empty">
                                No Sundays in <span x-text="monthLabel"></span>.
                            </td>
                        </tr>
                        <template x-for="row in weeklyCollectionRows" :key="row.method">
                            <tr class="arrears-row">
                                <td class="weekly-col-category">
                                    <span class="collections-method" :class="'collections-method--' + row.method" x-text="row.label"></span>
                                    <span class="block text-xs arrears-muted mt-0.5" x-show="row.desc" x-text="row.desc"></span>
                                </td>
                                <template x-for="sun in weeklyCollectionSundays" :key="row.method + '-' + sun">
                                    <td class="weekly-amount-cell">
                                        <template x-if="isInlineEditing('weeklyCollection', row.method, sun)">
                                            <input type="number"
                                                   class="arrears-inline-input"
                                                   min="0"
                                                   step="1"
                                                   inputmode="numeric"
                                                   x-model.number="inlineEdit.value"
                                                   @click.stop
                                                   @keydown.enter.prevent="commitWeeklyInlineEdit('weeklyCollection', row.method)"
                                                   @keydown.escape.prevent="cancelInlineEdit()"
                                                   @blur="commitWeeklyInlineEdit('weeklyCollection', row.method)"
                                                   x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                                   :aria-label="'Edit ' + row.label + ' for ' + formatSundayShort(sun)">
                                        </template>
                                        <template x-if="!isInlineEditing('weeklyCollection', row.method, sun)">
                                            <button type="button"
                                                    class="arrears-amount arrears-amount--editable"
                                                    :class="Number(row.amounts?.[sun] || 0) > 0 ? '' : 'arrears-amount--muted'"
                                                    @click.stop="startWeeklyInlineEdit('weeklyCollection', row.method, sun, row.amounts?.[sun])"
                                                    x-text="formatMoneyPlain(row.amounts?.[sun])"
                                                    title="Click to edit amount"
                                                    :aria-label="'Edit ' + row.label + ' for ' + formatSundayShort(sun)"></button>
                                        </template>
                                    </td>
                                </template>
                                <td class="weekly-amount-cell weekly-col-total">
                                    <span class="arrears-amount weekly-total-cell"
                                          :class="Number(row.total) > 0 ? '' : 'arrears-amount--muted'"
                                          x-text="formatMoneyPlain(row.total)"></span>
                                </td>
                                <td class="arrears-actions weekly-col-actions"
                                    :class="collectionMenu === row.method && 'weekly-actions--open'">
                                    <button type="button"
                                            class="arrears-view-btn arrears-view-btn--icon"
                                            @click.stop="toggleCollectionMenu(row.method, $event)"
                                            :aria-expanded="collectionMenu === row.method"
                                            :aria-label="'Actions for ' + row.label"
                                            title="Actions">
                                        <i data-lucide="ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="finance-table-footer weekly-footer">
                            <td class="weekly-col-category finance-table-footer-label">Weekly total</td>
                            <template x-for="sun in weeklyCollectionSundays" :key="'cft-' + sun">
                                <td class="weekly-amount-cell">
                                    <span class="arrears-amount finance-table-footer-amount"
                                          x-text="formatMoneyPlain(weeklyCollectionWeekTotals[sun])"></span>
                                </td>
                            </template>
                            <td class="weekly-amount-cell weekly-col-total ft-td-accent">
                                <span class="arrears-amount finance-table-footer-amount finance-table-footer-amount--grand"
                                      x-text="formatMoneyPlain(weeklyCollectionMonthTotal)"></span>
                            </td>
                            <td class="weekly-col-actions ft-td-actions"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    </div>
    <?php elseif ($tabBudget): ?>
    <?php require __DIR__ . '/budget-tab.php'; ?>

    <?php elseif ($tabReports): ?>
    <div class="fin-reports">
        <?php
        $reportSub = in_array(($reportSub ?? ''), ['statement', 'position'], true)
            ? $reportSub
            : 'statement';
        ?>
        <div class="fin-reports-subnav no-print" role="tablist" aria-label="Report type">
            <a href="/admin/finance?tab=reports&amp;sub=statement&amp;year=<?= (int) $year ?>&amp;month=<?= htmlspecialchars(urlencode($month)) ?>&amp;view=<?= htmlspecialchars(urlencode($statementView ?? 'monthly')) ?>"
               class="fin-reports-subnav__btn<?= $reportSub === 'statement' ? ' fin-reports-subnav__btn--active' : '' ?>"
               role="tab"
               aria-selected="<?= $reportSub === 'statement' ? 'true' : 'false' ?>">
                Operating statement
            </a>
            <a href="/admin/finance?tab=reports&amp;sub=position&amp;year=<?= (int) $year ?>"
               class="fin-reports-subnav__btn<?= $reportSub === 'position' ? ' fin-reports-subnav__btn--active' : '' ?>"
               role="tab"
               aria-selected="<?= $reportSub === 'position' ? 'true' : 'false' ?>">
                Income &amp; Expenditure
            </a>
        </div>

        <?php if ($reportSub === 'position'): ?>
            <?php require __DIR__ . '/position-tab.php'; ?>
        <?php else: ?>
            <?php
            $tabStatement = true;
            $statementView = $_GET['view'] ?? 'monthly';
            require __DIR__ . '/statement-tab.php';
            ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <template x-teleport="body">
        <div x-show="openMenu"
             x-cloak
             @click.outside="if (!arrearMenuIgnoreOutside) openMenu = null"
             @keydown.escape.window="openMenu = null"
             class="arrears-dropdown arrears-dropdown--fixed"
             :style="'top:' + arrearDropdownPos.top + 'px;right:' + arrearDropdownPos.right + 'px;left:auto'">
            <template x-if="openMenuRow">
                <div>
                    <button type="button" @click="openView(openMenuRow.id)" class="arrears-dropdown-item">View details</button>
                    <button type="button" @click="openEdit(openMenuRow.id)" class="arrears-dropdown-item">Edit expense</button>
                    <button type="button" @click="openRecordPayment(openMenuRow.id)" class="arrears-dropdown-item">Record payment</button>
                    <form :action="'/admin/finance/arrears/' + openMenuRow.id + '/delete'"
                          method="post"
                          @submit.prevent="deleteArrearAjax(openMenuRow.id)">
                        <input type="hidden" name="budget_year" :value="year">
                        <button type="submit" class="arrears-dropdown-item arrears-dropdown-item--danger">Delete</button>
                    </form>
                </div>
            </template>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="weeklyMenu"
             x-cloak
             @click.outside="if (!weeklyMenuIgnoreOutside) weeklyMenu = null"
             @keydown.escape.window="weeklyMenu = null"
             class="arrears-dropdown arrears-dropdown--fixed"
             :style="'top:' + weeklyDropdownPos.top + 'px;right:' + weeklyDropdownPos.right + 'px;left:auto'">
            <template x-if="weeklyMenuRow">
                <div>
                    <button type="button"
                            @click="openWeeklyView(weeklyMenuRow.slug)"
                            class="arrears-dropdown-item">
                        View details
                    </button>
                    <button type="button"
                            @click="weeklyMenu = null; goToSundayRecord(null, 'expenses')"
                            class="arrears-dropdown-item">
                        Edit in Record Sunday
                    </button>
                    <button type="button"
                            @click="openWeeklyEdit(weeklyMenuRow.slug)"
                            class="arrears-dropdown-item">
                        Edit category
                    </button>
                    <form :action="'/admin/finance/weekly/categories/' + weeklyMenuRow.slug + '/delete'"
                          method="post"
                          @submit.prevent="deleteWeeklyCategoryAjax(weeklyMenuRow.slug)">
                        <input type="hidden" name="month" :value="weeklyMonth">
                        <button type="submit" class="arrears-dropdown-item arrears-dropdown-item--danger">Delete</button>
                    </form>
                </div>
            </template>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="reconciliationMenu"
             x-cloak
             @click.outside="if (!reconciliationMenuIgnoreOutside) reconciliationMenu = null"
             @keydown.escape.window="reconciliationMenu = null"
             class="arrears-dropdown arrears-dropdown--fixed"
             :style="'top:' + reconciliationDropdownPos.top + 'px;right:' + reconciliationDropdownPos.right + 'px;left:auto'">
            <template x-if="reconciliationMenuRow">
                <div>
                    <button type="button"
                            @click="openReconciliationView(reconciliationMenuRow.week_date)"
                            class="arrears-dropdown-item">
                        View details
                    </button>
                    <button type="button"
                            @click="const d = reconciliationMenuRow.week_date; reconciliationMenu = null; goToSundayRecord(d)"
                            class="arrears-dropdown-item">
                        Edit in Record Sunday
                    </button>
                    <button type="button"
                            @click="openWeeklyStatement(reconciliationMenuRow.week_date)"
                            class="arrears-dropdown-item">
                        View weekly statement
                    </button>
                    <button type="button"
                            @click="goToLedger('expenses')"
                            class="arrears-dropdown-item">
                        Open Sunday expenses
                    </button>
                    <button type="button"
                            @click="goToLedger('collections')"
                            class="arrears-dropdown-item">
                        Open Sunday collections
                    </button>
                </div>
            </template>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="collectionMenu"
             x-cloak
             @click.outside="if (!collectionMenuIgnoreOutside) collectionMenu = null"
             @keydown.escape.window="collectionMenu = null"
             class="arrears-dropdown arrears-dropdown--fixed"
             :style="'top:' + collectionDropdownPos.top + 'px;right:' + collectionDropdownPos.right + 'px;left:auto'">
            <template x-if="collectionMenuRow">
                <div>
                    <button type="button"
                            @click="openCollectionView(collectionMenuRow.method)"
                            class="arrears-dropdown-item">
                        View details
                    </button>
                    <button type="button"
                            @click="openCollectionEdit(collectionMenuRow.method)"
                            class="arrears-dropdown-item">
                        Edit amounts
                    </button>
                    <button type="button"
                            @click="collectionMenu = null; goToSundayRecord(null, 'collections')"
                            class="arrears-dropdown-item">
                        Edit in Record Sunday
                    </button>
                    <form :action="'/admin/finance/collections/weekly/methods/' + encodeURIComponent(collectionMenuRow.method) + '/clear'"
                          method="post"
                          @submit.prevent="clearCollectionMethodAjax(collectionMenuRow.method)">
                        <input type="hidden" name="month" :value="weeklyMonth">
                        <button type="submit" class="arrears-dropdown-item arrears-dropdown-item--danger">Delete</button>
                    </form>
                </div>
            </template>
        </div>
    </template>

    <!-- Collection method view modal -->
    <div x-show="collectionViewRow"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="collectionViewRow = null">
        <div class="finance-modal-backdrop" @click="collectionViewRow = null"></div>
        <div class="finance-modal" x-transition>
            <template x-if="collectionViewRow">
                <div>
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Weekly collections</p>
                            <h4 class="finance-modal-title" x-text="collectionViewRow.label"></h4>
                            <p class="finance-modal-subtitle" x-show="collectionViewRow.desc" x-text="collectionViewRow.desc"></p>
                        </div>
                        <button type="button"
                                @click="collectionViewRow = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body">
                        <div class="finance-field">
                            <span class="finance-label">Month total</span>
                            <p class="finance-modal-intro" style="margin:0">
                                KES <span x-text="formatMoneyPlain(collectionViewRow.total)"></span>
                            </p>
                        </div>
                        <div class="collections-method-breakdown">
                            <template x-for="sun in weeklyCollectionSundays" :key="sun">
                                <div class="collections-method-breakdown__row">
                                    <span x-text="formatSundayShort(sun)"></span>
                                    <strong x-text="'KES ' + formatMoneyPlain(collectionViewRow.amounts[sun] || 0)"></strong>
                                </div>
                            </template>
                        </div>
                    </div>
                    <footer class="finance-modal-footer">
                        <div class="finance-modal-actions finance-modal-actions--end">
                            <button type="button" @click="collectionViewRow = null" class="finance-btn-secondary">Close</button>
                            <button type="button"
                                    @click="const m = collectionViewRow.method; collectionViewRow = null; openCollectionEdit(m)"
                                    class="finance-btn-primary">
                                Edit amounts
                            </button>
                        </div>
                    </footer>
                </div>
            </template>
        </div>
    </div>

    <!-- Weekly expense category view modal -->
    <div x-show="weeklyViewRow"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="weeklyViewRow = null">
        <div class="finance-modal-backdrop" @click="weeklyViewRow = null"></div>
        <div class="finance-modal" x-transition>
            <template x-if="weeklyViewRow">
                <div>
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Weekly expenses</p>
                            <h4 class="finance-modal-title" x-text="weeklyViewRow.label"></h4>
                            <p class="finance-modal-subtitle" x-show="weeklyViewRow.hint" x-text="weeklyViewRow.hint"></p>
                        </div>
                        <button type="button"
                                @click="weeklyViewRow = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body">
                        <div class="finance-field">
                            <span class="finance-label">Month total</span>
                            <p class="finance-modal-intro" style="margin:0">
                                KES <span x-text="formatMoneyPlain(weeklyViewRow.total)"></span>
                            </p>
                        </div>
                        <div class="collections-method-breakdown">
                            <template x-for="sun in weeklySundays" :key="sun">
                                <div class="collections-method-breakdown__row">
                                    <span x-text="formatSundayShort(sun)"></span>
                                    <strong x-text="'KES ' + formatMoneyPlain(weeklyViewRow.amounts[sun] || 0)"></strong>
                                </div>
                            </template>
                        </div>
                    </div>
                    <footer class="finance-modal-footer">
                        <div class="finance-modal-actions finance-modal-actions--end">
                            <button type="button" @click="weeklyViewRow = null" class="finance-btn-secondary">Close</button>
                            <button type="button"
                                    @click="weeklyViewRow = null; goToSundayRecord(null, 'expenses')"
                                    class="finance-btn-primary">
                                Edit in Record Sunday
                            </button>
                        </div>
                    </footer>
                </div>
            </template>
        </div>
    </div>

    <!-- Reconciliation week view modal -->
    <div x-show="reconciliationViewRow"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="reconciliationViewRow = null">
        <div class="finance-modal-backdrop" @click="reconciliationViewRow = null"></div>
        <div class="finance-modal" x-transition>
            <template x-if="reconciliationViewRow">
                <div>
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Reconciliation</p>
                            <h4 class="finance-modal-title" x-text="dateMain(reconciliationViewRow.week_date)"></h4>
                            <p class="finance-modal-subtitle" x-text="monthLabel"></p>
                        </div>
                        <button type="button"
                                @click="reconciliationViewRow = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="reconciliation-stat reconciliation-stat--collected">
                                <p class="reconciliation-stat-label">Collections</p>
                                <p class="reconciliation-stat-value">KES <span x-text="formatMoneyPlain(reconciliationViewRow.collections)"></span></p>
                            </div>
                            <div class="reconciliation-stat reconciliation-stat--expenses">
                                <p class="reconciliation-stat-label">Expenses</p>
                                <p class="reconciliation-stat-value">KES <span x-text="formatMoneyPlain(reconciliationViewRow.expenses)"></span></p>
                            </div>
                            <div class="reconciliation-stat" :class="reconciliationViewRow.balance >= 0 ? 'reconciliation-stat--surplus' : 'reconciliation-stat--deficit'">
                                <p class="reconciliation-stat-label">Balance</p>
                                <p class="reconciliation-stat-value">
                                    <span x-text="(reconciliationViewRow.balance < 0 ? '-' : '') + 'KES ' + formatMoneyPlain(Math.abs(reconciliationViewRow.balance || 0))"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <footer class="finance-modal-footer">
                        <div class="finance-modal-actions finance-modal-actions--end">
                            <button type="button" @click="reconciliationViewRow = null" class="finance-btn-secondary">Close</button>
                            <button type="button"
                                    @click="const d = reconciliationViewRow.week_date; reconciliationViewRow = null; goToSundayRecord(d)"
                                    class="finance-btn-primary">
                                Edit in Record Sunday
                            </button>
                        </div>
                    </footer>
                </div>
            </template>
        </div>
    </div>

    <!-- Collection method edit modal -->
    <div x-show="collectionEditRow"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="collectionEditRow = null">
        <div class="finance-modal-backdrop" @click="collectionEditRow = null"></div>
        <div class="finance-modal" x-transition>
            <template x-if="collectionEditRow">
                <form method="post"
                      :action="'/admin/finance/collections/weekly/methods/' + encodeURIComponent(collectionEditRow.method)"
                      @submit="submitCollectionEditAjax($event)">
                    <input type="hidden" name="month" :value="weeklyMonth">
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Weekly collections</p>
                            <h4 class="finance-modal-title">Edit amounts</h4>
                            <p class="finance-modal-subtitle" x-text="collectionEditRow.label"></p>
                        </div>
                        <button type="button"
                                @click="collectionEditRow = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body">
                        <template x-for="sun in weeklyCollectionSundays" :key="sun">
                            <div class="finance-field">
                                <label class="finance-label" :for="'collection-edit-' + sun" x-text="formatSundayShort(sun)"></label>
                                <div class="fin-amt-row__field" style="max-width:12rem">
                                    <span class="fin-amt-row__currency" aria-hidden="true">KES</span>
                                    <input type="number"
                                           :id="'collection-edit-' + sun"
                                           :name="'amounts[' + sun + ']'"
                                           min="0"
                                           step="1"
                                           inputmode="numeric"
                                           class="fin-amt-row__input finance-input"
                                           x-model.number="collectionEditRow.amounts[sun]"
                                           @focus="$el.select()">
                                </div>
                            </div>
                        </template>
                        <p class="finance-field-hint">
                            Month total: KES <span x-text="formatMoneyPlain(collectionEditTotal)"></span>
                        </p>
                    </div>
                    <footer class="finance-modal-footer">
                        <div class="finance-modal-actions finance-modal-actions--end">
                            <button type="button" @click="collectionEditRow = null" class="finance-btn-secondary">Cancel</button>
                            <button type="submit" class="finance-btn-primary">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                Save changes
                            </button>
                        </div>
                    </footer>
                </form>
            </template>
        </div>
    </div>

    <!-- New weekly category modal -->
    <div x-show="newCategory"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="newCategory = null">
        <div class="finance-modal-backdrop" @click="newCategory = null"></div>
        <div class="finance-modal" x-transition>
            <template x-if="newCategory">
                <form method="post" action="/admin/finance/weekly/categories" id="weekly-category-new-form" @submit="submitNewWeeklyCategory($event)">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Weekly expenses</p>
                            <h4 class="finance-modal-title">Add expenses</h4>
                        </div>
                        <button type="button"
                                @click="newCategory = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <div class="finance-modal-body">
                        <p class="finance-modal-intro">Add a new expense line to the weekly expenses grid.</p>
                        <div class="finance-field">
                            <label class="finance-label" for="new-weekly-group">Category</label>
                            <select id="new-weekly-group"
                                    required
                                    class="finance-input"
                                    x-model="newCategory.expense_group"
                                    @change="onNewWeeklyGroupChange()">
                                <option value="">Select category…</option>
                                <template x-for="grp in expenseGroups" :key="grp.slug">
                                    <option :value="grp.slug" x-text="grp.label"></option>
                                </template>
                            </select>
                        </div>
                        <div class="finance-field" x-show="newCategory.expense_group" x-cloak>
                            <label class="finance-label" for="new-weekly-expense-item">Expense item</label>
                            <select id="new-weekly-expense-item"
                                    name="expense_category_id"
                                    required
                                    class="finance-input"
                                    x-model="newCategory.expense_category_id"
                                    @change="onNewWeeklyExpenseItemChange()">
                                <option value="">Select expense item…</option>
                                <template x-for="cat in expenseItemsForGroup(newCategory.expense_group)" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.label"></option>
                                </template>
                                <option value="__new__">+ Add custom expense item…</option>
                            </select>
                            <input type="hidden" name="department_id" :value="newCategory.department_id">
                            <input type="hidden" name="label" :value="weeklyLineLabel(newCategory)">
                        </div>
                        <div class="finance-field" x-show="newCategory.expense_group && newCategory.expense_category_id === '__new__'" x-cloak>
                            <label class="finance-label" for="new-weekly-custom-item">Custom expense item</label>
                            <input type="text"
                                   id="new-weekly-custom-item"
                                   name="new_category_item_label"
                                   class="finance-input"
                                   x-model="newCategory.new_category_item_label"
                                   placeholder="e.g. Sound technician">
                            <p class="finance-field-hint">Saved to the database for future selections.</p>
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="new-category-hint">Description <span class="finance-label-optional">(optional)</span></label>
                            <input type="text"
                                   id="new-category-hint"
                                   name="hint"
                                   class="finance-input"
                                   x-model="newCategory.hint"
                                   placeholder="e.g. Sunday allowance">
                            <p class="finance-field-hint">Shown in the expenses table under this name.</p>
                        </div>
                    </div>
                    <footer class="finance-modal-footer">
                        <div class="finance-modal-actions finance-modal-actions--end">
                            <button type="button" @click="newCategory = null" class="finance-btn-secondary">Cancel</button>
                            <button type="submit" class="finance-btn-primary">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Add expenses
                            </button>
                        </div>
                    </footer>
                </form>
            </template>
        </div>
    </div>

    <!-- Edit weekly category modal -->
    <div x-show="weeklyEditRow"
         x-cloak
         class="finance-modal-overlay"
         @keydown.escape.window="weeklyEditRow = null">
        <div class="finance-modal-backdrop" @click="weeklyEditRow = null"></div>
        <div class="finance-modal" x-transition>
            <template x-if="weeklyEditRow">
                <div>
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Weekly expenses</p>
                            <h4 class="finance-modal-title">Edit category</h4>
                        </div>
                        <button type="button"
                                @click="weeklyEditRow = null"
                                class="finance-modal-close"
                                aria-label="Close">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </header>
                    <form id="weekly-category-edit-form"
                          method="post"
                          :action="'/admin/finance/weekly/categories/' + weeklyEditRow.slug"
                          @submit="submitWeeklyCategoryEdit($event)">
                        <input type="hidden" name="month" :value="weeklyMonth">
                        <div class="finance-modal-body">
                            <div class="finance-field">
                                <label class="finance-label" for="weekly-edit-group">Category</label>
                                <select id="weekly-edit-group"
                                        required
                                        class="finance-input"
                                        x-model="weeklyEditRow.expense_group"
                                        @change="onWeeklyEditGroupChange()">
                                    <option value="">Select category…</option>
                                    <template x-for="grp in expenseGroups" :key="grp.slug">
                                        <option :value="grp.slug" x-text="grp.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="finance-field" x-show="weeklyEditRow.expense_group" x-cloak>
                                <label class="finance-label" for="weekly-edit-expense-item">Expense item</label>
                                <select id="weekly-edit-expense-item"
                                        name="expense_category_id"
                                        required
                                        class="finance-input"
                                        x-model="weeklyEditRow.expense_category_id"
                                        @change="onWeeklyEditExpenseItemChange()">
                                    <option value="">Select expense item…</option>
                                    <template x-for="cat in expenseItemsForGroup(weeklyEditRow.expense_group, weeklyEditRow.expense_category_id)" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.label"></option>
                                    </template>
                                    <option value="__new__">+ Add custom expense item…</option>
                                </select>
                                <input type="hidden" name="department_id" :value="weeklyEditRow.department_id">
                                <input type="hidden" name="label" :value="weeklyLineLabel(weeklyEditRow)">
                            </div>
                            <div class="finance-field" x-show="weeklyEditRow.expense_group && weeklyEditRow.expense_category_id === '__new__'" x-cloak>
                                <label class="finance-label" for="weekly-edit-custom-item">Custom expense item</label>
                                <input type="text"
                                       id="weekly-edit-custom-item"
                                       name="new_category_item_label"
                                       class="finance-input"
                                       x-model="weeklyEditRow.new_category_item_label"
                                       placeholder="e.g. Sound technician">
                            </div>
                            <div class="finance-field">
                                <label class="finance-label" for="weekly-edit-hint">Description <span class="finance-label-optional">(optional)</span></label>
                                <input type="text"
                                       id="weekly-edit-hint"
                                       name="hint"
                                       class="finance-input"
                                       x-model="weeklyEditRow.hint"
                                       placeholder="e.g. Sunday allowance">
                                <p class="finance-field-hint">Shown in the expenses table under this name.</p>
                            </div>
                        </div>
                    </form>
                    <footer class="finance-modal-footer">
                        <form method="post"
                              :action="'/admin/finance/weekly/categories/' + weeklyEditRow.slug + '/delete'"
                              @submit.prevent="deleteWeeklyCategoryAjax(weeklyEditRow.slug)"
                              class="finance-modal-delete-form">
                            <input type="hidden" name="month" :value="weeklyMonth">
                            <button type="submit" class="finance-btn-danger">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                Delete
                            </button>
                        </form>
                        <div class="finance-modal-actions">
                            <button type="button" @click="weeklyEditRow = null" class="finance-btn-secondary">Cancel</button>
                            <button type="submit" form="weekly-category-edit-form" class="finance-btn-primary">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                Save changes
                            </button>
                        </div>
                    </footer>
                </div>
            </template>
        </div>
    </div>

    <?php /* Record Sunday is a dedicated page: /admin/finance/sunday */ ?>
</div>
