(function () {
    'use strict';

    function financeHubFactory(config) {
        return {
            newArrear: null,
            newCategory: null,
            weeklyMenu: null,
            weeklyEditRow: null,
            newCollection: null,
            collectionSearch: '',
            openMenu: null,
            arrearDropdownPos: { top: 0, left: 0 },
            arrearMenuIgnoreOutside: false,
            viewRow: null,
            editRow: null,
            paymentRow: null,
            search: '',
            arrears: config.arrears || [],
            collections: config.collections || [],
            paymentMethods: config.paymentMethods || {},
            year: config.year,
            weeklySearch: '',
            weeklyRows: config.weeklyRows || [],
            weeklySundays: config.weeklySundays || [],
            weeklyMonth: config.weeklyMonth || '',
            weeklyCollectionRows: config.weeklyCollectionRows || [],
            weeklyCollectionSundays: config.weeklyCollectionSundays || [],
            reconciliation: config.reconciliation || { weeks: [], month_expenses: 0, month_collections: 0, month_balance: 0 },
            yearReconciliation: config.yearReconciliation || { months: [], year_expenses: 0, year_collections: 0, year_balance: 0 },
            expenseGroups: config.expenseGroups || [],
            tablePerPage: 10,
            arrearsPage: 1,
            collectionsPage: 1,
            weeklyPage: 1,
            weeklyDropdownPos: { top: 0, left: 0 },
            weeklyMenuIgnoreOutside: false,

            init() {
                this.$nextTick(() => window.lucide?.createIcons());
                this.$watch('search', () => { this.arrearsPage = 1; });
                this.$watch('collectionSearch', () => { this.collectionsPage = 1; });
                this.$watch('weeklySearch', () => { this.weeklyPage = 1; });
                this.$watch('filteredArrears', () => {
                    this.clampPage('arrearsPage', this.filteredArrears);
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('filteredCollections', () => {
                    this.clampPage('collectionsPage', this.filteredCollections);
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('filteredWeekly', () => {
                    this.clampPage('weeklyPage', this.filteredWeekly);
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                ['arrearsPage', 'collectionsPage', 'weeklyPage'].forEach((key) => {
                    this.$watch(key, () => {
                        this.$nextTick(() => window.lucide?.createIcons());
                    });
                });
                this.$watch('weeklyEditRow', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('viewRow', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('editRow', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('paymentRow', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                });
            },

            get weeklyMenuRow() {
                if (!this.weeklyMenu) return null;
                return this.weeklyRows.find((r) => r.slug === this.weeklyMenu) || null;
            },

            get openMenuRow() {
                if (!this.openMenu) return null;
                return this.findArrear(this.openMenu);
            },

            openWeeklyEdit(slug) {
                const row = this.weeklyRows.find((r) => r.slug === slug);
                if (!row) return;
                this.weeklyEditRow = {
                    slug: row.slug,
                    label: row.label,
                    hint: row.hint || '',
                    department_id: row.department_id || '',
                    expense_group: row.expense_group || this.groupForDepartment(row.department_id),
                    expense_category_id: row.expense_category_id || '',
                    new_category_item_label: '',
                };
                this.weeklyMenu = null;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            get filteredCollections() {
                const q = this.collectionSearch.trim().toLowerCase();
                if (!q) return this.collections;
                return this.collections.filter((r) => {
                    const hay = [
                        r.reference,
                        r.fund_type,
                        r.notes,
                        r.payment_method,
                        this.methodLabel(r.payment_method),
                    ].filter(Boolean).join(' ').toLowerCase();
                    return hay.includes(q);
                });
            },

            methodLabel(method) {
                return this.paymentMethods[method]?.label || method;
            },

            get filteredArrears() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.arrears;
                return this.arrears.filter((r) => {
                    const hay = [
                        r.expense_item,
                        r.group_label,
                        r.department_label,
                        r.category_label,
                        r.account_code,
                        r.month_incurred,
                        r.paid_by_ref,
                        r.notes,
                        r.payment_status,
                    ].filter(Boolean).join(' ').toLowerCase();
                    return hay.includes(q);
                });
            },

            departmentsForGroup(groupSlug) {
                if (!groupSlug) return [];
                const grp = this.expenseGroups.find((g) => g.slug === groupSlug);
                return grp?.departments || [];
            },

            isAdminExpenses(groupSlug) {
                return groupSlug === 'admin_expenses';
            },

            isMinistryDepartments(groupSlug) {
                return groupSlug === 'ministry_departments';
            },

            administrationDepartmentId() {
                const grp = this.expenseGroups.find((g) => g.slug === 'admin_expenses');
                const admin = grp?.departments?.find((d) => d.slug === 'administration');
                return admin?.id || '';
            },

            adminExpenseLineItems() {
                return this.categoriesForDepartment(this.administrationDepartmentId());
            },

            expenseItemsForDepartment(deptId) {
                const dept = this.departmentById(deptId);
                if (!dept) return [];
                const items = dept.categories || [];
                if (items.length <= 1) return items;
                return items.filter((cat) => cat.label !== dept.label);
            },

            weeklyLineLabel(row) {
                if (!row) return '';
                if (row.expense_category_id === '__new__') {
                    return String(row.new_category_item_label || '').trim();
                }
                if (this.isAdminExpenses(row.expense_group)) {
                    const items = this.expenseItemsForDepartment(row.department_id);
                    const cat = items.find(
                        (c) => Number(c.id) === Number(row.expense_category_id)
                    );
                    return cat?.label || String(row.new_category_item_label || row.label || '').trim();
                }
                const items = this.expenseItemsForDepartment(row.department_id);
                const cat = items.find((c) => Number(c.id) === Number(row.expense_category_id));
                return cat?.label || String(row.new_category_item_label || row.label || '').trim();
            },

            departmentById(deptId) {
                const id = Number(deptId);
                if (!id) return null;
                for (const grp of this.expenseGroups) {
                    const dept = grp.departments.find((d) => Number(d.id) === id);
                    if (dept) {
                        return { ...dept, group_slug: grp.slug, group_label: grp.label };
                    }
                }
                return null;
            },

            groupForDepartment(deptId) {
                const dept = this.departmentById(deptId);
                return dept?.group_slug || '';
            },

            categoriesForDepartment(deptId) {
                return this.departmentById(deptId)?.categories || [];
            },

            onNewGroupChange() {
                if (!this.newArrear) return;
                this.newArrear.department_id = '';
                this.newArrear.category_id = '';
                this.newArrear.new_category_label = '';
                if (this.isAdminExpenses(this.newArrear.expense_group)) {
                    this.newArrear.department_id = this.administrationDepartmentId();
                }
            },

            onEditGroupChange() {
                if (!this.editRow) return;
                this.editRow.department_id = '';
                this.editRow.category_id = '';
                this.editRow.new_category_label = '';
                if (this.isAdminExpenses(this.editRow.expense_group)) {
                    this.editRow.department_id = this.administrationDepartmentId();
                }
            },

            onNewDepartmentChange() {
                if (!this.newArrear) return;
                this.newArrear.category_id = '';
                this.newArrear.new_category_label = '';
            },

            onEditDepartmentChange() {
                if (!this.editRow) return;
                this.editRow.category_id = '';
                this.editRow.new_category_label = '';
            },

            onNewWeeklyGroupChange() {
                if (!this.newCategory) return;
                this.newCategory.department_id = '';
                this.newCategory.expense_category_id = '';
                this.newCategory.new_category_item_label = '';
                if (this.isAdminExpenses(this.newCategory.expense_group)) {
                    this.newCategory.department_id = this.administrationDepartmentId();
                }
            },

            onWeeklyEditGroupChange() {
                if (!this.weeklyEditRow) return;
                this.weeklyEditRow.department_id = '';
                this.weeklyEditRow.expense_category_id = '';
                this.weeklyEditRow.new_category_item_label = '';
                if (this.isAdminExpenses(this.weeklyEditRow.expense_group)) {
                    this.weeklyEditRow.department_id = this.administrationDepartmentId();
                }
            },

            validateArrearCatalog(row) {
                if (!row.expense_group) {
                    window.alert('Select a department (Admin Expenses or Ministry & Departments).');
                    return false;
                }
                if (this.isAdminExpenses(row.expense_group)) {
                    if (!row.department_id) {
                        row.department_id = this.administrationDepartmentId();
                    }
                    if (!Number(row.department_id)) {
                        window.alert('Select a category for this expense.');
                        return false;
                    }
                    if (row.category_id === '__new__') {
                        if (!String(row.new_category_label || '').trim()) {
                            window.alert('Enter a custom category item name.');
                            return false;
                        }
                    } else if (!Number(row.category_id)) {
                        window.alert('Select an expense item (e.g. Rent, Water, Electricity).');
                        return false;
                    }
                } else {
                    if (!Number(row.department_id)) {
                        window.alert('Select a category for this expense.');
                        return false;
                    }
                    if (row.category_id === '__new__') {
                        if (!String(row.new_category_label || '').trim()) {
                            window.alert('Enter a custom category item name.');
                            return false;
                        }
                    } else if (!Number(row.category_id)) {
                        window.alert('Select an expense item (e.g. Drummer, Keyboardist).');
                        return false;
                    }
                }
                const due = Number(row.amount_due) || 0;
                const paid = Number(row.amount_paid) || 0;
                if (paid > due) {
                    window.alert('Amount paid cannot exceed amount due.');
                    return false;
                }
                return true;
            },

            submitNewArrear(event) {
                if (!this.newArrear || !this.validateArrearCatalog(this.newArrear)) {
                    event.preventDefault();
                }
            },

            get filteredWeekly() {
                const q = this.weeklySearch.trim().toLowerCase();
                if (!q) return this.weeklyRows;
                return this.weeklyRows.filter((r) => {
                    const hay = [r.label, r.hint, r.slug, r.department_label, r.group_label].filter(Boolean).join(' ').toLowerCase();
                    return hay.includes(q);
                });
            },

            get filteredWeeklyWeekTotals() {
                const totals = {};
                this.weeklySundays.forEach((s) => { totals[s] = 0; });
                this.filteredWeekly.forEach((row) => {
                    this.weeklySundays.forEach((s) => {
                        totals[s] += Number(row.amounts?.[s]) || 0;
                    });
                });
                return totals;
            },

            get filteredWeeklyMonthTotal() {
                return this.filteredWeekly.reduce((sum, r) => sum + (Number(r.total) || 0), 0);
            },

            get weeklyCollectionWeekTotals() {
                const totals = {};
                this.weeklyCollectionSundays.forEach((s) => { totals[s] = 0; });
                this.weeklyCollectionRows.forEach((row) => {
                    this.weeklyCollectionSundays.forEach((s) => {
                        totals[s] += Number(row.amounts?.[s]) || 0;
                    });
                });
                return totals;
            },

            get weeklyCollectionMonthTotal() {
                return this.weeklyCollectionRows.reduce((sum, r) => sum + (Number(r.total) || 0), 0);
            },

            paginationTotalPages(list) {
                return Math.max(1, Math.ceil(list.length / this.tablePerPage));
            },

            paginate(list, page) {
                const totalPages = this.paginationTotalPages(list);
                const p = Math.min(page, totalPages);
                const start = (p - 1) * this.tablePerPage;
                return list.slice(start, start + this.tablePerPage);
            },

            paginationFrom(list, page) {
                if (!list.length) return 0;
                const p = Math.min(page, this.paginationTotalPages(list));
                return (p - 1) * this.tablePerPage + 1;
            },

            paginationTo(list, page) {
                if (!list.length) return 0;
                const p = Math.min(page, this.paginationTotalPages(list));
                return Math.min(p * this.tablePerPage, list.length);
            },

            paginationPages(list) {
                const n = this.paginationTotalPages(list);
                return Array.from({ length: n }, (_, i) => i + 1);
            },

            clampPage(pageKey, list) {
                const max = this.paginationTotalPages(list);
                if (this[pageKey] > max) this[pageKey] = max;
                if (this[pageKey] < 1) this[pageKey] = 1;
            },

            prevPage(pageKey) {
                if (this[pageKey] > 1) {
                    this[pageKey] -= 1;
                    this.openMenu = null;
                    this.weeklyMenu = null;
                }
            },

            nextPage(pageKey, list) {
                if (this[pageKey] < this.paginationTotalPages(list)) {
                    this[pageKey] += 1;
                    this.openMenu = null;
                    this.weeklyMenu = null;
                }
            },

            goPage(pageKey, num, list) {
                const p = Number(num);
                const max = this.paginationTotalPages(list);
                if (p >= 1 && p <= max) {
                    this[pageKey] = p;
                    this.openMenu = null;
                    this.weeklyMenu = null;
                }
            },

            get paginatedArrears() {
                return this.paginate(this.filteredArrears, this.arrearsPage);
            },

            get paginatedCollections() {
                return this.paginate(this.filteredCollections, this.collectionsPage);
            },

            get paginatedWeekly() {
                return this.paginate(this.filteredWeekly, this.weeklyPage);
            },

            positionFixedDropdown(rect, menuWidth = 200, menuHeight = 168) {
                const margin = 8;
                const gap = 6;
                const spaceBelow = window.innerHeight - rect.bottom - margin;
                const spaceAbove = rect.top - margin;
                const placeAbove = spaceBelow < menuHeight && spaceAbove > spaceBelow;
                const top = placeAbove
                    ? Math.max(margin, rect.top - menuHeight - gap)
                    : rect.bottom + gap;
                const left = Math.min(
                    Math.max(margin, rect.right - menuWidth),
                    window.innerWidth - menuWidth - margin
                );
                return { top, left };
            },

            toggleMenu(id, event) {
                if (this.openMenu === id) {
                    this.openMenu = null;
                    return;
                }
                const btn = event?.currentTarget;
                if (btn) {
                    const rect = btn.getBoundingClientRect();
                    this.arrearDropdownPos = this.positionFixedDropdown(rect);
                }
                this.openMenu = id;
                this.weeklyMenu = null;
                this.arrearMenuIgnoreOutside = true;
                requestAnimationFrame(() => {
                    this.arrearMenuIgnoreOutside = false;
                });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            toggleWeeklyMenu(slug, event) {
                if (this.weeklyMenu === slug) {
                    this.weeklyMenu = null;
                    return;
                }
                const btn = event?.currentTarget;
                if (btn) {
                    const rect = btn.getBoundingClientRect();
                    this.weeklyDropdownPos = this.positionFixedDropdown(rect, 220, 132);
                }
                this.weeklyMenu = slug;
                this.openMenu = null;
                this.weeklyMenuIgnoreOutside = true;
                requestAnimationFrame(() => {
                    this.weeklyMenuIgnoreOutside = false;
                });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            positionActionDropdown(button) {
                if (!button) return;
                const dropdown = button.nextElementSibling;
                if (!dropdown?.classList.contains('arrears-dropdown')) return;

                document.querySelectorAll('.arrears-dropdown--fixed').forEach((el) => {
                    if (el !== dropdown) {
                        el.classList.remove('arrears-dropdown--fixed');
                        el.style.top = '';
                        el.style.left = '';
                    }
                });

                dropdown.classList.add('arrears-dropdown--fixed');
                const rect = button.getBoundingClientRect();
                const width = dropdown.offsetWidth || 168;
                const left = Math.min(
                    Math.max(8, rect.right - width),
                    window.innerWidth - width - 8
                );
                dropdown.style.top = `${rect.bottom + 6}px`;
                dropdown.style.left = `${left}px`;
            },

            findArrear(id) {
                return this.arrears.find((r) => Number(r.id) === Number(id)) || null;
            },

            openView(id) {
                this.viewRow = this.findArrear(id);
                this.openMenu = null;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            openNewArrear() {
                this.newArrear = {
                    expense_group: '',
                    department_id: '',
                    category_id: '',
                    new_category_label: '',
                    month_incurred: '',
                    amount_due: '',
                    amount_paid: '0',
                    date_paid: '',
                    paid_by_ref: '',
                    notes: '',
                    budget_year: this.year,
                };
                this.$nextTick(() => window.lucide?.createIcons());
            },

            openCategoryForm() {
                this.newCategory = {
                    label: '',
                    hint: '',
                    expense_group: '',
                    department_id: '',
                    expense_category_id: '',
                    new_category_item_label: '',
                };
                this.$nextTick(() => window.lucide?.createIcons());
            },

            validateWeeklyCategory(row) {
                if (!row.expense_group) {
                    window.alert('Select a department (Admin Expenses or Ministry & Departments).');
                    return false;
                }
                if (this.isAdminExpenses(row.expense_group)) {
                    if (!row.department_id) {
                        row.department_id = this.administrationDepartmentId();
                    }
                    if (!Number(row.department_id)) {
                        window.alert('Select a category for this expense.');
                        return false;
                    }
                    if (row.expense_category_id === '__new__') {
                        if (!String(row.new_category_item_label || '').trim()) {
                            window.alert('Enter a custom category item name.');
                            return false;
                        }
                    } else if (!Number(row.expense_category_id)) {
                        window.alert('Select an expense item (e.g. Rent, Water, Electricity).');
                        return false;
                    }
                } else {
                    if (!Number(row.department_id)) {
                        window.alert('Select a category for this expense.');
                        return false;
                    }
                    if (row.expense_category_id === '__new__') {
                        if (!String(row.new_category_item_label || '').trim()) {
                            window.alert('Enter a custom category item name.');
                            return false;
                        }
                    } else if (!Number(row.expense_category_id)) {
                        window.alert('Select an expense item (e.g. Drummer, Keyboardist).');
                        return false;
                    }
                }
                row.label = this.weeklyLineLabel(row);
                if (!String(row.label || '').trim()) {
                    window.alert('Expense line name is required.');
                    return false;
                }
                return true;
            },

            submitNewWeeklyCategory(event) {
                if (!this.newCategory || !this.validateWeeklyCategory(this.newCategory)) {
                    event.preventDefault();
                }
            },

            submitWeeklyCategoryEdit(event) {
                if (!this.weeklyEditRow || !this.validateWeeklyCategory(this.weeklyEditRow)) {
                    event.preventDefault();
                }
            },

            openNewCollection() {
                const methods = Object.keys(this.paymentMethods);
                this.newCollection = {
                    collection_date: new Date().toISOString().slice(0, 10),
                    payment_method: methods[0] || 'paybill',
                    amount: '',
                    fund_type: '',
                    reference: '',
                    notes: '',
                    budget_year: this.year,
                };
                this.$nextTick(() => window.lucide?.createIcons());
            },

            get newArrearComputedPaid() {
                return Number(this.newArrear?.amount_paid) || 0;
            },

            get newArrearComputedBalance() {
                const due = Number(this.newArrear?.amount_due) || 0;
                return Math.max(0, Math.round((due - this.newArrearComputedPaid) * 100) / 100);
            },

            get newArrearComputedStatus() {
                return this.computePaymentStatus(
                    Number(this.newArrear?.amount_due) || 0,
                    this.newArrearComputedPaid
                );
            },

            openEdit(id) {
                const row = this.findArrear(id);
                if (!row) return;
                this.editRow = {
                    ...row,
                    expense_group: row.expense_group || this.groupForDepartment(row.department_id),
                    department_id: row.department_id || '',
                    category_id: row.category_id || '',
                    new_category_label: '',
                    expense_item: row.expense_item || row.category_label || '',
                    amount_paid: Number(row.amount_paid) || 0,
                    amount_due: Number(row.amount_due) || 0,
                };
                this.paymentRow = null;
                this.openMenu = null;
                this.viewRow = null;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            openRecordPayment(id) {
                const row = this.findArrear(id);
                if (!row) return;
                this.paymentRow = {
                    ...row,
                    original_amount_paid: Number(row.amount_paid) || 0,
                    record_payment: '',
                    date_paid: row.date_paid || '',
                    paid_by_ref: row.paid_by_ref || '',
                };
                this.editRow = null;
                this.openMenu = null;
                this.viewRow = null;
                this.$nextTick(() => {
                    window.lucide?.createIcons();
                    this.$refs.paymentAmountInput?.focus();
                });
            },

            get paymentComputedPaid() {
                if (!this.paymentRow) return 0;
                const base = Number(this.paymentRow.original_amount_paid) || 0;
                const extra = Number(this.paymentRow.record_payment) || 0;
                return Math.round((base + extra) * 100) / 100;
            },

            get paymentComputedBalance() {
                if (!this.paymentRow) return 0;
                const due = Number(this.paymentRow.amount_due) || 0;
                return Math.max(0, Math.round((due - this.paymentComputedPaid) * 100) / 100);
            },

            get paymentComputedStatus() {
                return this.computePaymentStatus(
                    Number(this.paymentRow?.amount_due) || 0,
                    this.paymentComputedPaid
                );
            },

            get editComputedPaid() {
                if (!this.editRow) return 0;
                return Number(this.editRow.amount_paid) || 0;
            },

            get editComputedBalance() {
                if (!this.editRow) return 0;
                const due = Number(this.editRow.amount_due) || 0;
                return Math.max(0, Math.round((due - this.editComputedPaid) * 100) / 100);
            },

            get editComputedStatus() {
                return this.computePaymentStatus(
                    Number(this.editRow?.amount_due) || 0,
                    this.editComputedPaid
                );
            },

            computePaymentStatus(due, paid) {
                const balance = Math.max(0, Number(due) - Number(paid));
                if (balance <= 0 && paid > 0) return 'PAID';
                if (paid > 0 && balance > 0) return 'PARTIAL';
                return 'UNPAID';
            },

            syncArrearInTable(data) {
                const id = Number(data.id);
                const due = Number(data.amount_due) || 0;
                const paid = Number(data.amount_paid) || 0;
                const balance = Math.max(0, Math.round((due - paid) * 100) / 100);
                const status = this.computePaymentStatus(due, paid);
                const patch = {
                    ...data,
                    amount_paid: paid,
                    amount_due: due,
                    balance_owing: balance,
                    payment_status: status,
                };
                const idx = this.arrears.findIndex((r) => Number(r.id) === id);
                if (idx >= 0) {
                    this.arrears[idx] = { ...this.arrears[idx], ...patch };
                }
            },

            submitArrearEdit(event) {
                const form = event.target;
                const payload = {
                    ...this.editRow,
                    amount_paid: Number(this.editRow.amount_paid) || 0,
                };
                if (!this.validateArrearCatalog(payload)) {
                    return;
                }
                if (!String(payload.expense_item || '').trim()) {
                    window.alert('Enter an expense title.');
                    return;
                }
                this.syncArrearInTable(payload);
                this.editRow = null;
                form.submit();
            },

            submitArrearPayment(event) {
                const form = event.target;
                const payment = Number(this.paymentRow.record_payment) || 0;
                if (payment <= 0) {
                    window.alert('Enter a payment amount greater than zero.');
                    return;
                }
                if (!this.paymentRow.date_paid) {
                    this.paymentRow.date_paid = new Date().toISOString().slice(0, 10);
                }
                const payload = {
                    ...this.paymentRow,
                    amount_paid: this.paymentComputedPaid,
                };
                if (this.paymentComputedPaid > Number(this.paymentRow.amount_due)) {
                    window.alert('Total amount paid cannot exceed amount due.');
                    return;
                }
                this.syncArrearInTable(payload);
                this.paymentRow = null;
                form.submit();
            },

            formatMoney(value) {
                if (value === null || value === undefined || value === '') return '—';
                const n = Number(value);
                if (Number.isNaN(n)) return '—';
                const formatted = Math.abs(n).toLocaleString('en-KE', { maximumFractionDigits: 0 });
                return (n < 0 ? '-KES ' : 'KES ') + formatted;
            },

            formatMoneyPlain(value) {
                if (value === null || value === undefined || value === '') return '—';
                const n = Number(value);
                if (Number.isNaN(n)) return '—';
                const formatted = Math.abs(n).toLocaleString('en-KE', { maximumFractionDigits: 0 });
                return (n < 0 ? '-' : '') + formatted;
            },

            formatDate(value) {
                if (!value) return '—';
                const d = new Date(value + 'T00:00:00');
                if (Number.isNaN(d.getTime())) return value;
                return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            },

            dateMain(value) {
                if (!value) return null;
                const d = new Date(value + 'T00:00:00');
                if (Number.isNaN(d.getTime())) return null;
                const day = String(d.getDate()).padStart(2, '0');
                const month = d.toLocaleDateString('en-GB', { month: 'short' });
                return day + ' ' + month;
            },

            dateYear(value) {
                if (!value) return null;
                const d = new Date(value + 'T00:00:00');
                if (Number.isNaN(d.getTime())) return null;
                return String(d.getFullYear());
            },

            statusLabel(status) {
                if (status === 'PAID') return 'Paid';
                if (status === 'PARTIAL') return 'Partially paid';
                return 'Not paid';
            },

            statusClass(status) {
                if (status === 'PAID') return 'arrears-status--paid';
                if (status === 'PARTIAL') return 'arrears-status--partial';
                return 'arrears-status--unpaid';
            },

            printStatement() {
                window.print();
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('financeHub', financeHubFactory);

        Alpine.data('weeklyEntryForm', (config) => ({
            weekDate: config.weekDate || '',
            amountsByDate: config.amountsByDate || {},
            categories: config.categories || [],
            fields: {},
            weekTotal: 0,

            init() {
                this.loadFieldsForDate(this.weekDate);
                this.$nextTick(() => window.lucide?.createIcons());
            },

            loadFieldsForDate(date) {
                const saved = this.amountsByDate[date] || {};
                const next = {};
                this.categories.forEach((slug) => {
                    next[slug] = Number(saved[slug]) || 0;
                });
                this.fields = next;
                this.recalc();
            },

            onDateChange() {
                this.loadFieldsForDate(this.weekDate);
            },

            recalc() {
                this.weekTotal = Object.values(this.fields).reduce((sum, v) => sum + (Number(v) || 0), 0);
            },
        }));

        Alpine.data('weeklyCollectionsEntryForm', (config) => ({
            weekDate: config.weekDate || '',
            amountsByDate: config.amountsByDate || {},
            methods: config.methods || [],
            fields: {},
            weekTotal: 0,

            init() {
                this.loadFieldsForDate(this.weekDate);
                this.$nextTick(() => window.lucide?.createIcons());
            },

            loadFieldsForDate(date) {
                const saved = this.amountsByDate[date] || {};
                const next = {};
                this.methods.forEach((method) => {
                    next[method] = Number(saved[method]) || 0;
                });
                this.fields = next;
                this.recalc();
            },

            onDateChange() {
                this.loadFieldsForDate(this.weekDate);
            },

            recalc() {
                this.weekTotal = Object.values(this.fields).reduce((sum, v) => sum + (Number(v) || 0), 0);
            },
        }));

        Alpine.data('sundayEntryForm', (config) => ({
            weekDate: config.weekDate || '',
            sessionsByDate: config.sessionsByDate || {},
            methods: config.methods || [],
            categories: config.categories || [],
            presets: config.presets || {},
            presetTotals: config.presetTotals || { standard: 12200, full: 17200 },
            collectionFields: {},
            expenseFields: {},
            notes: '',
            collectionsTotal: 0,
            expensesTotal: 0,
            weekBalance: 0,
            activePreset: '',

            init() {
                this.loadSession(this.weekDate);
                this.$nextTick(() => window.lucide?.createIcons());
                this.$watch('weekDate', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                });
            },

            get hasSavedData() {
                const session = this.sessionsByDate[this.weekDate];
                if (!session) return false;
                const colSum = Object.values(session.collections || {}).reduce((s, v) => s + (Number(v) || 0), 0);
                const expSum = Object.values(session.expenses || {}).reduce((s, v) => s + (Number(v) || 0), 0);
                return colSum > 0 || expSum > 0 || Boolean(session.notes);
            },

            get balanceLabel() {
                if (this.weekBalance > 0) return 'Surplus';
                if (this.weekBalance < 0) return 'Shortfall';
                return 'Balanced';
            },

            loadSession(date) {
                const session = this.sessionsByDate[date] || { collections: {}, expenses: {}, notes: '' };
                const col = {};
                this.methods.forEach((m) => {
                    col[m] = Number(session.collections?.[m]) || 0;
                });
                const exp = {};
                this.categories.forEach((slug) => {
                    exp[slug] = Number(session.expenses?.[slug]) || 0;
                });
                this.collectionFields = col;
                this.expenseFields = exp;
                this.notes = session.notes || '';
                this.activePreset = '';
                this.recalc();
            },

            onDateChange() {
                this.loadSession(this.weekDate);
            },

            sumFields(obj) {
                return Object.values(obj).reduce((sum, v) => sum + (Number(v) || 0), 0);
            },

            roundMoney(n) {
                return Math.round((Number(n) || 0) * 100) / 100;
            },

            recalc() {
                this.collectionsTotal = this.roundMoney(this.sumFields(this.collectionFields));
                this.expensesTotal = this.roundMoney(this.sumFields(this.expenseFields));
                this.weekBalance = this.roundMoney(this.collectionsTotal - this.expensesTotal);
            },

            applyPreset(name) {
                const preset = this.presets[name];
                if (!preset) return;
                this.clearExpenses();
                Object.keys(preset).forEach((slug) => {
                    if (slug in this.expenseFields) {
                        this.expenseFields[slug] = Number(preset[slug]) || 0;
                    }
                });
                this.activePreset = name;
                this.recalc();
            },

            clearExpenses() {
                this.categories.forEach((slug) => {
                    this.expenseFields[slug] = 0;
                });
                this.activePreset = '';
                this.recalc();
            },

            clearCollections() {
                this.methods.forEach((m) => {
                    this.collectionFields[m] = 0;
                });
                this.recalc();
            },

            formatMoney(value) {
                const n = Number(value) || 0;
                const formatted = Math.abs(n).toLocaleString('en-KE', { maximumFractionDigits: 0 });
                return (n < 0 ? '−KES ' : 'KES ') + formatted;
            },

            validateBeforeSubmit() {
                this.recalc();
            },
        }));
    });
})();
