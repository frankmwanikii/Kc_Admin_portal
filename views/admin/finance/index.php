<?php
$fmt = static fn (float $n): string => number_format($n, 0);
$tab = $tab ?? 'dashboard';
if ($tab === 'arrears') $tab = 'bills';
if ($tab === 'weekly' || $tab === 'collections') $tab = 'ledger';
if ($tab === 'reconciliation' || $tab === 'statement') $tab = 'reports';
$tabDashboard = $tab === 'dashboard';
$tabBills = $tab === 'bills';
$tabLedger = $tab === 'ledger';
$tabReports = $tab === 'reports';
$ledgerSub = ($_GET['sub'] ?? '') === 'collections' ? 'collections' : 'expenses';
$reportSub = $_GET['sub'] ?? 'reconciliation';
if (!in_array($reportSub, ['reconciliation', 'statement', 'budget'], true)) {
    $reportSub = 'reconciliation';
}
?>
<div class="fin-hub" x-cloak x-data="financeHub(<?= htmlspecialchars(json_encode($hubConfig ?? ['year' => (int) ($year ?? date('Y')), 'paymentMethods' => $paymentMethods ?? []]), ENT_QUOTES) ?>)">
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
                       placeholder="Search arrears..."
                       aria-label="Search arrears">
                <span class="arrears-count" x-text="filteredArrears.length + (filteredArrears.length === 1 ? ' arrear' : ' arrears')"></span>
                <form method="get" class="inline-flex">
                    <input type="hidden" name="tab" value="arrears">
                    <select name="year" onchange="this.form.submit()" class="arrears-year-select" aria-label="Budget year">
                        <?php for ($y = (int) date('Y') + 1; $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
            <button type="button" @click="openNewArrear()" class="arrears-btn-new">+ New Arrear</button>
        </div>

        <div class="arrears-card finance-table-card">
            <div class="finance-table-caption">
                <span class="finance-table-caption-label">Expense arrears</span>
                <span class="finance-table-caption-badge"><?= (int) $year ?></span>
                <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
            </div>
            <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Expense arrears — scroll horizontally on small screens">
                <table class="arrears-table">
                    <thead>
                        <tr>
                            <th>Expense item</th>
                            <th>Period incurred</th>
                            <th>Date paid</th>
                            <th class="ft-th-accent ft-th--right">Amount paid</th>
                            <th class="ft-th-accent ft-th--right">Amount due</th>
                            <th class="ft-th-accent ft-th--right">Balance owing</th>
                            <th class="ft-th-accent">Status</th>
                            <th class="ft-th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="filteredArrears.length === 0">
                            <td colspan="8" class="arrears-empty">
                                <span x-show="search.trim()">No arrears match your search.</span>
                                <span x-show="!search.trim()">No arrears recorded for <?= (int) $year ?>. Click <strong>+ New Arrear</strong> to add one.</span>
                            </td>
                        </tr>
                        <template x-for="row in paginatedArrears" :key="row.id">
                            <tr class="arrears-row">
                                <td>
                                    <span class="arrears-accent" x-text="row.category_label || row.expense_item"></span>
                                </td>
                                <td class="arrears-muted" x-text="row.month_incurred"></td>
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
                                    <span class="arrears-amount"
                                          :class="Number(row.amount_paid) > 0 ? '' : 'arrears-amount--muted'"
                                          x-text="formatMoneyPlain(row.amount_paid)"></span>
                                </td>
                                <td class="ft-td-accent">
                                    <span class="arrears-amount" x-text="formatMoneyPlain(row.amount_due)"></span>
                                </td>
                                <td class="ft-td-accent">
                                    <span class="arrears-amount"
                                          :class="Number(row.balance_owing) > 0 ? 'arrears-amount--owing' : ''"
                                          x-text="formatMoneyPlain(row.balance_owing)"></span>
                                </td>
                                <td class="ft-td-accent">
                                    <span class="arrears-status" :class="statusClass(row.payment_status)" x-text="statusLabel(row.payment_status)"></span>
                                </td>
                                <td class="arrears-actions ft-td-actions ft-td-actions--sticky"
                                    :class="openMenu === row.id && 'weekly-actions--open'">
                                    <button type="button"
                                            class="arrears-view-btn"
                                            @click.stop="toggleMenu(row.id, $event)"
                                            :aria-expanded="openMenu === row.id"
                                            :aria-label="'View options for ' + row.expense_item">
                                        View
                                        <i data-lucide="chevron-down"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="finance-table-footer">
                            <td colspan="3" class="finance-table-footer-label">Year totals</td>
                            <td class="ft-td-accent">
                                <span class="finance-table-footer-amount">KES <?= $fmt($arrearsTotals['paid'] ?? 0) ?></span>
                            </td>
                            <td class="ft-td-accent">
                                <span class="finance-table-footer-amount">KES <?= $fmt($arrearsTotals['due'] ?? 0) ?></span>
                            </td>
                            <td class="ft-td-accent">
                                <span class="finance-table-footer-amount finance-table-footer-amount--grand">KES <?= $fmt($arrearsTotals['balance'] ?? 0) ?></span>
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
            $itemLabel = 'arrears';
            $navLabel = 'Expense arrears pages';
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
                            <p class="finance-modal-eyebrow">Arrear details</p>
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
                                <p class="finance-detail-label">Period incurred</p>
                                <p class="finance-detail-value" x-text="viewRow.month_incurred"></p>
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
                              onsubmit="return confirm('Delete this arrear entry?')"
                              class="finance-modal-delete-form">
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
                            <p class="finance-modal-eyebrow">Expense arrears</p>
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
                      id="arrear-edit-form"
                      @submit.prevent="submitArrearEdit($event)">
                    <input type="hidden" name="budget_year" :value="editRow.budget_year">
                    <header class="finance-modal-header">
                        <div class="finance-modal-header-text">
                            <p class="finance-modal-eyebrow">Expense arrears</p>
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
                            <label class="finance-label" for="edit-expense-group">Department</label>
                            <select id="edit-expense-group"
                                    required
                                    class="finance-input"
                                    x-model="editRow.expense_group"
                                    @change="onEditGroupChange()">
                                <option value="">Select department…</option>
                                <template x-for="grp in expenseGroups" :key="grp.slug">
                                    <option :value="grp.slug" x-text="grp.label"></option>
                                </template>
                            </select>
                        </div>
                        <template x-if="isMinistryDepartments(editRow.expense_group)">
                            <div class="finance-catalog-fields finance-field--full">
                            <div class="finance-field finance-field--full">
                                <label class="finance-label" for="edit-department">Category</label>
                                <select id="edit-department"
                                        name="department_id"
                                        required
                                        class="finance-input"
                                        x-model="editRow.department_id"
                                        @change="onEditDepartmentChange()">
                                    <option value="">Select category…</option>
                                    <template x-for="dept in departmentsForGroup('ministry_departments')" :key="dept.id">
                                        <option :value="dept.id" x-text="dept.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="finance-field finance-field--full" x-show="editRow.department_id" x-cloak>
                                <label class="finance-label" for="edit-ministry-item">Expense item</label>
                                <select id="edit-ministry-item"
                                        name="category_id"
                                        required
                                        class="finance-input"
                                        x-model="editRow.category_id">
                                    <option value="">Select expense item…</option>
                                    <template x-for="cat in expenseItemsForDepartment(editRow.department_id)" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.label"></option>
                                    </template>
                                    <option value="__new__">+ Add custom category item…</option>
                                </select>
                            </div>
                            </div>
                        </template>
                        <template x-if="isAdminExpenses(editRow.expense_group)">
                            <div class="finance-catalog-fields finance-field--full">
                            <div class="finance-field finance-field--full">
                                <label class="finance-label" for="edit-admin-department">Category</label>
                                <select id="edit-admin-department"
                                        name="department_id"
                                        required
                                        class="finance-input"
                                        x-model="editRow.department_id"
                                        @change="onEditDepartmentChange()">
                                    <option value="">Select category…</option>
                                    <template x-for="dept in departmentsForGroup('admin_expenses')" :key="dept.id">
                                        <option :value="dept.id" x-text="dept.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="finance-field finance-field--full" x-show="editRow.department_id" x-cloak>
                                <label class="finance-label" for="edit-admin-item">Expense item</label>
                                <select id="edit-admin-item"
                                        name="category_id"
                                        required
                                        class="finance-input"
                                        x-model="editRow.category_id">
                                    <option value="">Select expense item…</option>
                                    <template x-for="cat in expenseItemsForDepartment(editRow.department_id)" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.label"></option>
                                    </template>
                                    <option value="__new__">+ Add custom category item…</option>
                                </select>
                            </div>
                            </div>
                        </template>
                        <div class="finance-field finance-field--full"
                             x-show="(isAdminExpenses(editRow.expense_group) && editRow.category_id === '__new__') || (isMinistryDepartments(editRow.expense_group) && editRow.category_id === '__new__')"
                             x-cloak>
                            <label class="finance-label" for="edit-new-category">Custom category item</label>
                            <input type="text"
                                   id="edit-new-category"
                                   name="new_category_label"
                                   class="finance-input"
                                   x-model="editRow.new_category_label"
                                   placeholder="e.g. Generator fuel">
                            <p class="finance-field-hint">Saved to the database for future selections.</p>
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="edit-month-incurred">Period incurred</label>
                            <input type="text"
                                   id="edit-month-incurred"
                                   name="month_incurred"
                                   required
                                   class="finance-input"
                                   x-model="editRow.month_incurred"
                                   placeholder="e.g. 2025">
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
                            <p class="finance-modal-eyebrow">Expense arrears</p>
                            <h4 class="finance-modal-title">New arrear entry</h4>
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
                            <label class="finance-label" for="new-expense-group">Department</label>
                            <select id="new-expense-group"
                                    required
                                    class="finance-input"
                                    x-model="newArrear.expense_group"
                                    @change="onNewGroupChange()">
                                <option value="">Select department…</option>
                                <template x-for="grp in expenseGroups" :key="grp.slug">
                                    <option :value="grp.slug" x-text="grp.label"></option>
                                </template>
                            </select>
                        </div>
                        <template x-if="isMinistryDepartments(newArrear.expense_group)">
                            <div class="finance-catalog-fields finance-field--full">
                            <div class="finance-field finance-field--full">
                                <label class="finance-label" for="new-department">Category</label>
                                <select id="new-department"
                                        name="department_id"
                                        required
                                        class="finance-input"
                                        x-model="newArrear.department_id"
                                        @change="onNewDepartmentChange()">
                                    <option value="">Select category…</option>
                                    <template x-for="dept in departmentsForGroup('ministry_departments')" :key="dept.id">
                                        <option :value="dept.id" x-text="dept.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="finance-field finance-field--full" x-show="newArrear.department_id" x-cloak>
                                <label class="finance-label" for="new-ministry-item">Expense item</label>
                                <select id="new-ministry-item"
                                        name="category_id"
                                        required
                                        class="finance-input"
                                        x-model="newArrear.category_id">
                                    <option value="">Select expense item…</option>
                                    <template x-for="cat in expenseItemsForDepartment(newArrear.department_id)" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.label"></option>
                                    </template>
                                    <option value="__new__">+ Add custom category item…</option>
                                </select>
                            </div>
                            </div>
                        </template>
                        <template x-if="isAdminExpenses(newArrear.expense_group)">
                            <div class="finance-catalog-fields finance-field--full">
                            <div class="finance-field finance-field--full">
                                <label class="finance-label" for="new-admin-department">Category</label>
                                <select id="new-admin-department"
                                        name="department_id"
                                        required
                                        class="finance-input"
                                        x-model="newArrear.department_id"
                                        @change="onNewDepartmentChange()">
                                    <option value="">Select category…</option>
                                    <template x-for="dept in departmentsForGroup('admin_expenses')" :key="dept.id">
                                        <option :value="dept.id" x-text="dept.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="finance-field finance-field--full" x-show="newArrear.department_id" x-cloak>
                                <label class="finance-label" for="new-admin-item">Expense item</label>
                                <select id="new-admin-item"
                                        name="category_id"
                                        required
                                        class="finance-input"
                                        x-model="newArrear.category_id">
                                    <option value="">Select expense item…</option>
                                    <template x-for="cat in expenseItemsForDepartment(newArrear.department_id)" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.label"></option>
                                    </template>
                                    <option value="__new__">+ Add custom category item…</option>
                                </select>
                            </div>
                            </div>
                        </template>
                        <div class="finance-field finance-field--full"
                             x-show="(isAdminExpenses(newArrear.expense_group) && newArrear.category_id === '__new__') || (isMinistryDepartments(newArrear.expense_group) && newArrear.category_id === '__new__')"
                             x-cloak>
                            <label class="finance-label" for="new-new-category">Custom category item</label>
                            <input type="text"
                                   id="new-new-category"
                                   name="new_category_label"
                                   class="finance-input"
                                   x-model="newArrear.new_category_label"
                                   placeholder="e.g. Generator fuel">
                            <p class="finance-field-hint">Saved to the database for future selections.</p>
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="new-month-incurred">Period incurred</label>
                            <input type="text"
                                   id="new-month-incurred"
                                   name="month_incurred"
                                   required
                                   class="finance-input"
                                   x-model="newArrear.month_incurred"
                                   placeholder="e.g. Jan – Mar 2026">
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
    <div class="fin-ledger">
        <div class="fin-subtabs no-print">
            <a href="/admin/finance?tab=ledger&year=<?= (int) $year ?>&month=<?= htmlspecialchars($month) ?>&sub=expenses"
               class="fin-subtabs__item <?= $ledgerSub === 'expenses' ? 'fin-subtabs__item--active' : '' ?>">Expenses</a>
            <a href="/admin/finance?tab=ledger&year=<?= (int) $year ?>&month=<?= htmlspecialchars($month) ?>&sub=collections"
               class="fin-subtabs__item <?= $ledgerSub === 'collections' ? 'fin-subtabs__item--active' : '' ?>">Collections</a>
        </div>

    <?php if ($ledgerSub === 'expenses'): ?>
    <?php
    $sundays = $weekly['sundays'] ?? [];
    $monthLabel = date('F Y', strtotime($month . '-01'));
    ?>
    <div class="arrears-page weekly-page fin-section">
        <h2 class="arrears-title">Weekly Expenses</h2>
        <p class="finance-tab-hint">Money spent each Sunday — use Record Sunday to enter or update.</p>

        <div class="arrears-toolbar-row">
            <div class="arrears-toolbar-left">
                <input type="search"
                       x-model="weeklySearch"
                       class="arrears-search"
                       placeholder="Search categories..."
                       aria-label="Search expense categories">
                <span class="arrears-count" x-text="filteredWeekly.length + (filteredWeekly.length === 1 ? ' category' : ' categories')"></span>
                <form method="get" class="inline-flex">
                    <input type="hidden" name="tab" value="ledger">
                    <input type="hidden" name="sub" value="expenses">
                    <input type="hidden" name="year" value="<?= (int) $year ?>">
                    <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()" class="arrears-year-select" aria-label="Budget month">
                </form>
            </div>
            <div class="weekly-toolbar-actions">
                <a href="/admin/finance/sunday?month=<?= htmlspecialchars($month) ?>" class="arrears-btn-new no-underline">Record Sunday</a>
                <button type="button" @click="openCategoryForm()" class="arrears-btn-outline">Add category</button>
            </div>
        </div>

        <div class="arrears-card finance-table-card">
            <div class="finance-table-caption">
                <span class="finance-table-caption-label">Weekly expenses</span>
                <span class="finance-table-caption-badge"><?= htmlspecialchars($monthLabel) ?></span>
                <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
            </div>
            <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Weekly expenses — scroll horizontally on small screens">
                <table class="arrears-table weekly-table">
                    <thead>
                        <tr>
                            <th class="weekly-col-category">Category</th>
                            <?php foreach ($sundays as $i => $sun): ?>
                            <th class="weekly-col-sunday">
                                <span class="weekly-sun-head-date"><?= date('d M', strtotime($sun)) ?></span>
                                <span class="weekly-sun-head-label">Sun <?= $i + 1 ?></span>
                            </th>
                            <?php endforeach; ?>
                            <th class="weekly-col-total">Total</th>
                            <th class="weekly-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="filteredWeekly.length === 0">
                            <td colspan="<?= count($sundays) + 3 ?>" class="arrears-empty">
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
                                <?php foreach ($sundays as $sun): ?>
                                <td class="weekly-amount-cell">
                                    <span class="arrears-amount"
                                          :class="Number(row.amounts['<?= $sun ?>'] || 0) > 0 ? '' : 'arrears-amount--muted'"
                                          x-text="formatMoneyPlain(row.amounts['<?= $sun ?>'])"></span>
                                </td>
                                <?php endforeach; ?>
                                <td class="weekly-amount-cell weekly-col-total">
                                    <span class="arrears-amount weekly-total-cell"
                                          :class="Number(row.total) > 0 ? '' : 'arrears-amount--muted'"
                                          x-text="formatMoneyPlain(row.total)"></span>
                                </td>
                                <td class="arrears-actions weekly-col-actions"
                                    :class="weeklyMenu === row.slug && 'weekly-actions--open'">
                                    <button type="button"
                                            class="arrears-view-btn"
                                            @click.stop="toggleWeeklyMenu(row.slug, $event)"
                                            :aria-expanded="weeklyMenu === row.slug"
                                            :aria-label="'Actions for ' + row.label">
                                        View
                                        <i data-lucide="chevron-down"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="finance-table-footer weekly-footer">
                            <td class="weekly-col-category finance-table-footer-label">Weekly total</td>
                            <?php foreach ($sundays as $sun): ?>
                            <td class="weekly-amount-cell">
                                <span class="arrears-amount finance-table-footer-amount"
                                      x-text="formatMoneyPlain(filteredWeeklyWeekTotals['<?= $sun ?>'])"></span>
                            </td>
                            <?php endforeach; ?>
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
    <?php else: ?>
    <?php
    $monthLabel = date('F Y', strtotime($month . '-01'));
    $collectionSundays = $weeklyCollections['sundays'] ?? [];
    ?>
    <div class="arrears-page collections-page fin-section">
        <h2 class="arrears-title">Weekly Collections</h2>
        <p class="finance-tab-hint">Giving received each Sunday by payment method.</p>

        <div class="arrears-toolbar-row">
            <div class="arrears-toolbar-left">
                <span class="arrears-count" x-text="weeklyCollectionRows.length + ' methods'"></span>
                <form method="get" class="inline-flex items-center gap-2">
                    <input type="hidden" name="tab" value="ledger">
                    <input type="hidden" name="sub" value="collections">
                    <input type="hidden" name="year" value="<?= (int) $year ?>">
                    <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()" class="arrears-year-select" aria-label="Month">
                </form>
            </div>
            <a href="/admin/finance/sunday?month=<?= htmlspecialchars($month) ?>" class="arrears-btn-new no-underline">Record Sunday</a>
        </div>

        <div class="arrears-card finance-table-card">
            <div class="finance-table-caption">
                <span class="finance-table-caption-label">Weekly collections</span>
                <span class="finance-table-caption-badge"><?= htmlspecialchars($monthLabel) ?></span>
                <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
            </div>
            <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Weekly collections grid">
                <table class="arrears-table weekly-table">
                    <thead>
                        <tr>
                            <th class="weekly-col-category">Method</th>
                            <?php foreach ($collectionSundays as $i => $sun): ?>
                            <th class="weekly-col-sunday">
                                <span class="weekly-sun-head-date"><?= date('d M', strtotime($sun)) ?></span>
                                <span class="weekly-sun-head-label">Sun <?= $i + 1 ?></span>
                            </th>
                            <?php endforeach; ?>
                            <?php if (count($collectionSundays) === 0): ?>
                            <th class="weekly-col-sunday">No Sundays</th>
                            <?php endif; ?>
                            <th class="weekly-col-total">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="weeklyCollectionRows.length === 0">
                            <td colspan="<?= max(count($collectionSundays), 1) + 2 ?>" class="arrears-empty">
                                No Sundays in <?= htmlspecialchars($monthLabel) ?>.
                            </td>
                        </tr>
                        <template x-for="row in weeklyCollectionRows" :key="row.method">
                            <tr class="arrears-row">
                                <td class="weekly-col-category">
                                    <span class="collections-method" :class="'collections-method--' + row.method" x-text="row.label"></span>
                                    <span class="block text-xs arrears-muted mt-0.5" x-show="row.desc" x-text="row.desc"></span>
                                </td>
                                <?php foreach ($collectionSundays as $sun): ?>
                                <td class="weekly-amount-cell">
                                    <span class="arrears-amount"
                                          :class="Number(row.amounts['<?= $sun ?>'] || 0) > 0 ? '' : 'arrears-amount--muted'"
                                          x-text="formatMoneyPlain(row.amounts['<?= $sun ?>'])"></span>
                                </td>
                                <?php endforeach; ?>
                                <td class="weekly-amount-cell weekly-col-total">
                                    <span class="arrears-amount weekly-total-cell"
                                          :class="Number(row.total) > 0 ? '' : 'arrears-amount--muted'"
                                          x-text="formatMoneyPlain(row.total)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="finance-table-footer weekly-footer">
                            <td class="weekly-col-category finance-table-footer-label">Weekly total</td>
                            <?php foreach ($collectionSundays as $sun): ?>
                            <td class="weekly-amount-cell">
                                <span class="arrears-amount finance-table-footer-amount"
                                      x-text="formatMoneyPlain(weeklyCollectionWeekTotals['<?= $sun ?>'])"></span>
                            </td>
                            <?php endforeach; ?>
                            <td class="weekly-amount-cell weekly-col-total ft-td-accent">
                                <span class="arrears-amount finance-table-footer-amount finance-table-footer-amount--grand"
                                      x-text="formatMoneyPlain(weeklyCollectionMonthTotal)"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    </div>
    <?php elseif ($tabReports): ?>
    <div class="fin-reports">
        <div class="fin-subtabs no-print">
            <a href="/admin/finance?tab=reports&year=<?= (int) $year ?>&month=<?= htmlspecialchars($month) ?>&sub=reconciliation"
               class="fin-subtabs__item <?= $reportSub === 'reconciliation' ? 'fin-subtabs__item--active' : '' ?>">Reconciliation</a>
            <a href="/admin/finance?tab=reports&year=<?= (int) $year ?>&month=<?= htmlspecialchars($month) ?>&sub=budget&budget_year=<?= (int) ($budgetYear ?? 2026) ?>"
               class="fin-subtabs__item <?= $reportSub === 'budget' ? 'fin-subtabs__item--active' : '' ?>">Budget</a>
            <a href="/admin/finance?tab=reports&year=<?= (int) $year ?>&month=<?= htmlspecialchars($month) ?>&sub=statement&view=monthly"
               class="fin-subtabs__item <?= $reportSub === 'statement' ? 'fin-subtabs__item--active' : '' ?>">Statement</a>
        </div>

    <?php if ($reportSub === 'reconciliation'): ?>
    <?php
    $monthLabel = date('F Y', strtotime($month . '-01'));
    $monthCollections = (float) ($reconciliation['month_collections'] ?? 0);
    $monthExpenses = (float) ($reconciliation['month_expenses'] ?? 0);
    $monthBalance = (float) ($reconciliation['month_balance'] ?? 0);
    $balanceStatClass = $monthBalance >= 0 ? 'reconciliation-stat--surplus' : 'reconciliation-stat--deficit';
    ?>
    <div class="arrears-page reconciliation-page">
        <h2 class="arrears-title">Reconciliation</h2>
        <p class="text-sm text-slate-500 -mt-3 mb-5">Compare collections against expenses — <?= htmlspecialchars($monthLabel) ?></p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
            <div class="reconciliation-stat reconciliation-stat--collected">
                <p class="reconciliation-stat-label">Total collected</p>
                <p class="reconciliation-stat-value">KES <?= $fmt($monthCollections) ?></p>
            </div>
            <div class="reconciliation-stat reconciliation-stat--expenses">
                <p class="reconciliation-stat-label">Expenses incurred</p>
                <p class="reconciliation-stat-value">KES <?= $fmt($monthExpenses) ?></p>
            </div>
            <div class="reconciliation-stat <?= $balanceStatClass ?>">
                <p class="reconciliation-stat-label">Balance</p>
                <p class="reconciliation-stat-value"><?= $monthBalance < 0 ? '-' : '' ?>KES <?= $fmt(abs($monthBalance)) ?></p>
            </div>
        </div>

        <div class="arrears-toolbar-row">
            <div class="arrears-toolbar-left">
                <form method="get" class="inline-flex items-center gap-2">
                    <input type="hidden" name="tab" value="reports">
                    <input type="hidden" name="sub" value="reconciliation">
                    <select name="year" onchange="this.form.submit()" class="arrears-year-select" aria-label="Year">
                        <?php for ($y = (int) date('Y') + 1; $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" onchange="this.form.submit()" class="arrears-year-select" aria-label="Month">
                </form>
            </div>
        </div>

        <div class="arrears-card finance-table-card reconciliation-card">
            <div class="finance-table-caption">
                <span class="finance-table-caption-label">Weekly reconciliation</span>
                <span class="finance-table-caption-badge">Collections vs expenses</span>
                <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
            </div>
            <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Weekly reconciliation">
                <table class="arrears-table reconciliation-table">
                    <thead>
                        <tr>
                            <th>Week</th>
                            <th class="ft-th-accent ft-th--right">Collections</th>
                            <th class="ft-th-accent ft-th--right">Expenses</th>
                            <th class="ft-th-actions ft-th--right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-show="reconciliation.weeks.length === 0">
                            <td colspan="4" class="arrears-empty">No Sunday weeks in this month.</td>
                        </tr>
                        <template x-for="(week, index) in reconciliation.weeks" :key="week.week_date">
                            <tr class="arrears-row">
                                <td>
                                    <div class="arrears-date">
                                        <span class="arrears-date-main" x-text="dateMain(week.week_date)"></span>
                                        <span class="arrears-date-sub" x-text="'Sun ' + (index + 1)"></span>
                                    </div>
                                </td>
                                <td class="ft-td-accent">
                                    <span class="arrears-amount" x-text="formatMoneyPlain(week.collections)"></span>
                                </td>
                                <td class="ft-td-accent">
                                    <span class="arrears-amount" x-text="formatMoneyPlain(week.expenses)"></span>
                                </td>
                                <td class="ft-td-actions">
                                    <span class="reconciliation-balance"
                                          :class="week.balance >= 0 ? 'reconciliation-balance--surplus' : 'reconciliation-balance--deficit'"
                                          x-text="formatMoneyPlain(week.balance)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="finance-table-footer">
                            <td class="finance-table-footer-label">Month total</td>
                            <td class="ft-td-accent">
                                <span class="finance-table-footer-amount" x-text="formatMoney(reconciliation.month_collections)"></span>
                            </td>
                            <td class="ft-td-accent">
                                <span class="finance-table-footer-amount" x-text="formatMoney(reconciliation.month_expenses)"></span>
                            </td>
                            <td class="ft-td-actions">
                                <span class="finance-table-footer-amount finance-table-footer-amount--grand"
                                      :class="reconciliation.month_balance >= 0 ? 'reconciliation-balance--surplus' : 'reconciliation-balance--deficit'"
                                      x-text="formatMoney(reconciliation.month_balance)"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($reportSub === 'budget'): ?>
    <?php require __DIR__ . '/budget-tab.php'; ?>
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
             :style="'top:' + arrearDropdownPos.top + 'px;left:' + arrearDropdownPos.left + 'px'">
            <template x-if="openMenuRow">
                <div>
                    <button type="button" @click="openView(openMenuRow.id)" class="arrears-dropdown-item">View details</button>
                    <button type="button" @click="openEdit(openMenuRow.id)" class="arrears-dropdown-item">Edit expense</button>
                    <button type="button" @click="openRecordPayment(openMenuRow.id)" class="arrears-dropdown-item">Record payment</button>
                    <form :action="'/admin/finance/arrears/' + openMenuRow.id + '/delete'" method="post" onsubmit="return confirm('Delete this arrear entry?')">
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
             :style="'top:' + weeklyDropdownPos.top + 'px;left:' + weeklyDropdownPos.left + 'px'">
            <template x-if="weeklyMenuRow">
                <div>
                    <a :href="'/admin/finance/sunday?month=' + encodeURIComponent(weeklyMonth)"
                       class="arrears-dropdown-item no-underline">
                        Edit in Record Sunday
                    </a>
                    <button type="button"
                            @click="openWeeklyEdit(weeklyMenuRow.slug)"
                            class="arrears-dropdown-item">
                        Edit category
                    </button>
                    <form :action="'/admin/finance/weekly/categories/' + weeklyMenuRow.slug + '/delete'"
                          method="post"
                          onsubmit="return confirm('Delete this category and all its expense entries?')">
                        <input type="hidden" name="month" :value="weeklyMonth">
                        <button type="submit" class="arrears-dropdown-item arrears-dropdown-item--danger">Delete</button>
                    </form>
                </div>
            </template>
        </div>
    </template>

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
                            <label class="finance-label" for="new-weekly-group">Department</label>
                            <select id="new-weekly-group"
                                    required
                                    class="finance-input"
                                    x-model="newCategory.expense_group"
                                    @change="onNewWeeklyGroupChange()">
                                <option value="">Select department…</option>
                                <template x-for="grp in expenseGroups" :key="grp.slug">
                                    <option :value="grp.slug" x-text="grp.label"></option>
                                </template>
                            </select>
                        </div>
                        <template x-if="isMinistryDepartments(newCategory.expense_group)">
                            <div class="finance-catalog-fields">
                            <div class="finance-field">
                                <label class="finance-label" for="new-weekly-department">Category</label>
                                <select id="new-weekly-department"
                                        name="department_id"
                                        required
                                        class="finance-input"
                                        x-model="newCategory.department_id"
                                        @change="newCategory.expense_category_id = ''; newCategory.new_category_item_label = ''">
                                    <option value="">Select category…</option>
                                    <template x-for="dept in departmentsForGroup('ministry_departments')" :key="dept.id">
                                        <option :value="dept.id" x-text="dept.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="finance-field" x-show="newCategory.department_id" x-cloak>
                                <label class="finance-label" for="new-weekly-ministry-item">Expense item</label>
                                <select id="new-weekly-ministry-item"
                                        name="expense_category_id"
                                        required
                                        class="finance-input"
                                        x-model="newCategory.expense_category_id">
                                    <option value="">Select expense item…</option>
                                    <template x-for="cat in expenseItemsForDepartment(newCategory.department_id)" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.label"></option>
                                    </template>
                                    <option value="__new__">+ Add custom category item…</option>
                                </select>
                            </div>
                            <div class="finance-field" x-show="newCategory.expense_category_id === '__new__'" x-cloak>
                                <label class="finance-label" for="new-weekly-custom-item">Custom category item</label>
                                <input type="text"
                                       id="new-weekly-custom-item"
                                       name="new_category_item_label"
                                       class="finance-input"
                                       x-model="newCategory.new_category_item_label"
                                       placeholder="e.g. Sound technician">
                                <p class="finance-field-hint">Saved to the database for future selections.</p>
                            </div>
                            <input type="hidden" name="label" :value="weeklyLineLabel(newCategory)">
                            </div>
                        </template>
                        <template x-if="isAdminExpenses(newCategory.expense_group)">
                            <div class="finance-catalog-fields">
                            <div class="finance-field">
                                <label class="finance-label" for="new-weekly-admin-department">Category</label>
                                <select id="new-weekly-admin-department"
                                        name="department_id"
                                        required
                                        class="finance-input"
                                        x-model="newCategory.department_id"
                                        @change="newCategory.expense_category_id = ''; newCategory.new_category_item_label = ''">
                                    <option value="">Select category…</option>
                                    <template x-for="dept in departmentsForGroup('admin_expenses')" :key="dept.id">
                                        <option :value="dept.id" x-text="dept.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="finance-field" x-show="newCategory.department_id" x-cloak>
                                <label class="finance-label" for="new-weekly-admin-line">Expense item</label>
                                <select id="new-weekly-admin-line"
                                        name="expense_category_id"
                                        required
                                        class="finance-input"
                                        x-model="newCategory.expense_category_id">
                                    <option value="">Select expense item…</option>
                                    <template x-for="cat in expenseItemsForDepartment(newCategory.department_id)" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.label"></option>
                                    </template>
                                    <option value="__new__">+ Add custom category item…</option>
                                </select>
                            </div>
                            <div class="finance-field" x-show="newCategory.expense_category_id === '__new__'" x-cloak>
                                <label class="finance-label" for="new-weekly-admin-custom">Custom category item</label>
                                <input type="text"
                                       id="new-weekly-admin-custom"
                                       name="new_category_item_label"
                                       class="finance-input"
                                       x-model="newCategory.new_category_item_label"
                                       placeholder="e.g. Generator fuel">
                                <p class="finance-field-hint">Saved to the database for future selections.</p>
                            </div>
                            <input type="hidden" name="label" :value="weeklyLineLabel(newCategory)">
                            </div>
                        </template>
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
                                <label class="finance-label" for="weekly-edit-group">Department</label>
                                <select id="weekly-edit-group"
                                        required
                                        class="finance-input"
                                        x-model="weeklyEditRow.expense_group"
                                        @change="onWeeklyEditGroupChange()">
                                    <option value="">Select department…</option>
                                    <template x-for="grp in expenseGroups" :key="grp.slug">
                                        <option :value="grp.slug" x-text="grp.label"></option>
                                    </template>
                                </select>
                            </div>
                            <template x-if="isMinistryDepartments(weeklyEditRow.expense_group)">
                                <div class="finance-catalog-fields">
                                <div class="finance-field">
                                    <label class="finance-label" for="weekly-edit-department">Category</label>
                                    <select id="weekly-edit-department"
                                            name="department_id"
                                            required
                                            class="finance-input"
                                            x-model="weeklyEditRow.department_id"
                                            @change="weeklyEditRow.expense_category_id = ''; weeklyEditRow.new_category_item_label = ''">
                                        <option value="">Select category…</option>
                                        <template x-for="dept in departmentsForGroup('ministry_departments')" :key="dept.id">
                                            <option :value="dept.id" x-text="dept.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="finance-field" x-show="weeklyEditRow.department_id" x-cloak>
                                    <label class="finance-label" for="weekly-edit-ministry-item">Expense item</label>
                                    <select id="weekly-edit-ministry-item"
                                            name="expense_category_id"
                                            required
                                            class="finance-input"
                                            x-model="weeklyEditRow.expense_category_id">
                                        <option value="">Select expense item…</option>
                                        <template x-for="cat in expenseItemsForDepartment(weeklyEditRow.department_id)" :key="cat.id">
                                            <option :value="cat.id" x-text="cat.label"></option>
                                        </template>
                                        <option value="__new__">+ Add custom category item…</option>
                                    </select>
                                </div>
                                <div class="finance-field" x-show="weeklyEditRow.expense_category_id === '__new__'" x-cloak>
                                    <label class="finance-label" for="weekly-edit-custom-item">Custom category item</label>
                                    <input type="text"
                                           id="weekly-edit-custom-item"
                                           name="new_category_item_label"
                                           class="finance-input"
                                           x-model="weeklyEditRow.new_category_item_label"
                                           placeholder="e.g. Sound technician">
                                </div>
                                <input type="hidden" name="label" :value="weeklyLineLabel(weeklyEditRow)">
                                </div>
                            </template>
                            <template x-if="isAdminExpenses(weeklyEditRow.expense_group)">
                                <div class="finance-catalog-fields">
                                <div class="finance-field">
                                    <label class="finance-label" for="weekly-edit-admin-department">Category</label>
                                    <select id="weekly-edit-admin-department"
                                            name="department_id"
                                            required
                                            class="finance-input"
                                            x-model="weeklyEditRow.department_id"
                                            @change="weeklyEditRow.expense_category_id = ''; weeklyEditRow.new_category_item_label = ''">
                                        <option value="">Select category…</option>
                                        <template x-for="dept in departmentsForGroup('admin_expenses')" :key="dept.id">
                                            <option :value="dept.id" x-text="dept.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="finance-field" x-show="weeklyEditRow.department_id" x-cloak>
                                    <label class="finance-label" for="weekly-edit-admin-line">Expense item</label>
                                    <select id="weekly-edit-admin-line"
                                            name="expense_category_id"
                                            required
                                            class="finance-input"
                                            x-model="weeklyEditRow.expense_category_id">
                                        <option value="">Select expense item…</option>
                                        <template x-for="cat in expenseItemsForDepartment(weeklyEditRow.department_id)" :key="cat.id">
                                            <option :value="cat.id" x-text="cat.label"></option>
                                        </template>
                                        <option value="__new__">+ Add custom category item…</option>
                                    </select>
                                </div>
                                <div class="finance-field" x-show="weeklyEditRow.expense_category_id === '__new__'" x-cloak>
                                    <label class="finance-label" for="weekly-edit-admin-custom">Custom category item</label>
                                    <input type="text"
                                           id="weekly-edit-admin-custom"
                                           name="new_category_item_label"
                                           class="finance-input"
                                           x-model="weeklyEditRow.new_category_item_label"
                                           placeholder="e.g. Generator fuel">
                                </div>
                                <input type="hidden" name="label" :value="weeklyLineLabel(weeklyEditRow)">
                                </div>
                            </template>
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
                              onsubmit="return confirm('Delete this category and all its expense entries?')"
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
</div>
