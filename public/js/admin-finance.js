(function () {
    'use strict';

    function financeHubFactory(config) {
        return {
            newArrear: null,
            newCategory: null,
            weeklyMenu: null,
            weeklyEditRow: null,
            weeklyViewRow: null,
            collectionMenu: null,
            collectionViewRow: null,
            collectionEditRow: null,
            reconciliationMenu: null,
            reconciliationViewRow: null,
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
            weeklyMonth: config.weeklyMonth || config.month || '',
            weeklyCollectionRows: config.weeklyCollectionRows || [],
            weeklyCollectionSundays: config.weeklyCollectionSundays || [],
            sundaySessionsByDate: config.sundaySessionsByDate || {},
            sundayFormBase: config.sundayFormBase || {
                weekDate: '',
                methods: Object.keys(config.paymentMethods || {}),
                categories: [],
                presets: {},
                presetTotals: {},
            },
            ledgerSub: config.ledgerSub === 'collections' ? 'collections' : 'expenses',
            dashboard: config.dashboard || {},
            budget: config.budget || {},
            budgetYear: config.budgetYear || config.year || new Date().getFullYear(),
            budgetEditLines: config.budgetEditLines || [],
            showBudgetEditor: false,
            budgetNewLine: null,
            reconciliation: config.reconciliation || { weeks: [], month_expenses: 0, month_collections: 0, month_balance: 0 },
            statementView: config.statementView || 'monthly',
            statementWeekDate: config.statementWeekDate || '',
            statementSundays: config.statementSundays || [],
            statementBusy: false,
            yearReconciliation: config.yearReconciliation || { months: [], year_expenses: 0, year_collections: 0, year_balance: 0 },
            expenseGroups: config.expenseGroups || [],
            arrearsTotals: config.arrearsTotals || { due: 0, paid: 0, balance: 0 },
            tablePerPage: 10,
            arrearsPage: 1,
            collectionsPage: 1,
            weeklyPage: 1,
            _syncingEditCatalog: false,
            editFormKey: 0,
            weeklyDropdownPos: { top: 0, left: 0 },
            weeklyMenuIgnoreOutside: false,
            collectionDropdownPos: { top: 0, left: 0 },
            collectionMenuIgnoreOutside: false,
            reconciliationDropdownPos: { top: 0, left: 0 },
            reconciliationMenuIgnoreOutside: false,
            showSundayModal: false,
            toast: null,
            toastTimer: null,
            ajaxBusy: false,

            init() {
                this.$nextTick(() => window.lucide?.createIcons());
                if (config.openSundayModal) {
                    this.showSundayModal = true;
                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('record');
                        url.searchParams.delete('record_date');
                        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
                    } catch (_) { /* ignore */ }
                }
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
                this.$watch('collectionViewRow', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('collectionEditRow', () => {
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
                this.$watch('showBudgetEditor', (open) => {
                    document.body.style.overflow = open ? 'hidden' : '';
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('showSundayModal', (open) => {
                    document.body.style.overflow = open ? 'hidden' : '';
                    this.$nextTick(() => window.lucide?.createIcons());
                });
            },

            openSundayModal(weekDate = null) {
                if ((!this.weeklySundays || !this.weeklySundays.length)
                    && Array.isArray(this.reconciliation?.weeks)
                    && this.reconciliation.weeks.length) {
                    this.weeklySundays = this.reconciliation.weeks.map((w) => w.week_date);
                }
                if (weekDate) {
                    this.sundayFormBase = {
                        ...(this.sundayFormBase || {}),
                        weekDate,
                    };
                }
                this.reconciliationMenu = null;
                this.weeklyMenu = null;
                this.collectionMenu = null;
                this.showSundayModal = true;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            closeSundayModal() {
                this.showSundayModal = false;
            },

            setLedgerSub(sub) {
                if (sub !== 'expenses' && sub !== 'collections') return;
                if (this.ledgerSub === sub) return;
                this.ledgerSub = sub;
                this.weeklyMenu = null;
                this.collectionMenu = null;
                this.reconciliationMenu = null;
                this.syncFinanceUrl({ tab: 'ledger', sub });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            buildSundayFormConfig() {
                const base = this.sundayFormBase || {};
                return {
                    weekDate: base.weekDate || '',
                    sessionsByDate: this.sundaySessionsByDate || {},
                    methods: base.methods || Object.keys(this.paymentMethods || {}),
                    categories: base.categories || [],
                    presets: base.presets || {},
                    presetTotals: base.presetTotals || {},
                };
            },

            showToast(message, type = 'success') {
                if (this.toastTimer) {
                    clearTimeout(this.toastTimer);
                }
                this.toast = { message: String(message || ''), type };
                this.toastTimer = setTimeout(() => {
                    this.toast = null;
                }, 3200);
                this.$nextTick(() => window.lucide?.createIcons());
            },

            applyLedgerData(data) {
                this.applyFinanceData(data);
            },

            applyFinanceData(data) {
                if (!data || typeof data !== 'object') return;
                if (data.year) this.year = Number(data.year) || this.year;
                if (Array.isArray(data.weeklyRows)) this.weeklyRows = data.weeklyRows;
                if (Array.isArray(data.weeklySundays)) this.weeklySundays = data.weeklySundays;
                if (Array.isArray(data.weeklyCollectionRows)) this.weeklyCollectionRows = data.weeklyCollectionRows;
                if (Array.isArray(data.weeklyCollectionSundays)) this.weeklyCollectionSundays = data.weeklyCollectionSundays;
                if (Array.isArray(data.expenseGroups)) this.expenseGroups = data.expenseGroups;
                if (Array.isArray(data.arrears)) this.arrears = data.arrears;
                if (data.arrearsTotals) this.arrearsTotals = data.arrearsTotals;
                if (data.month) this.weeklyMonth = data.month;
                if (data.weeklyMonth) this.weeklyMonth = data.weeklyMonth;
                if (data.sundaySessionsByDate) this.sundaySessionsByDate = data.sundaySessionsByDate;
                if (data.sundayFormBase) {
                    this.sundayFormBase = {
                        ...this.sundayFormBase,
                        ...data.sundayFormBase,
                    };
                }
                if (data.dashboard) this.dashboard = data.dashboard;
                if (data.reconciliation) this.reconciliation = data.reconciliation;
                if (data.budget) this.budget = data.budget;
                if (Array.isArray(data.budgetEditLines)) this.budgetEditLines = data.budgetEditLines;
                if (data.budgetYear) this.budgetYear = Number(data.budgetYear) || this.budgetYear;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            get monthLabel() {
                if (!this.weeklyMonth) return '';
                const d = new Date(String(this.weeklyMonth) + '-01T12:00:00');
                if (Number.isNaN(d.getTime())) return String(this.weeklyMonth);
                return d.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
            },

            syncFinanceUrl(extra = {}) {
                try {
                    const url = new URL(window.location.href);
                    if (extra.tab) url.searchParams.set('tab', extra.tab);
                    if (Object.prototype.hasOwnProperty.call(extra, 'sub')) {
                        if (extra.sub) url.searchParams.set('sub', String(extra.sub));
                        else url.searchParams.delete('sub');
                    }
                    if (this.weeklyMonth) url.searchParams.set('month', this.weeklyMonth);
                    if (this.year) url.searchParams.set('year', String(this.year));
                    Object.keys(extra).forEach((key) => {
                        if (key === 'tab' || key === 'sub') return;
                        if (extra[key] === null || extra[key] === undefined || extra[key] === '') {
                            url.searchParams.delete(key);
                        } else {
                            url.searchParams.set(key, String(extra[key]));
                        }
                    });
                    window.history.replaceState({}, '', url.pathname + url.search + url.hash);
                } catch (_) { /* ignore */ }
            },

            async loadFinanceData(options = {}) {
                if (this.ajaxBusy) return null;
                const month = options.month || this.weeklyMonth || '';
                const year = options.year || this.year || (month ? Number(String(month).slice(0, 4)) : new Date().getFullYear());
                const params = new URLSearchParams({
                    month: month || `${year}-01`,
                    year: String(year),
                });
                this.ajaxBusy = true;
                try {
                    const response = await fetch('/admin/finance/data?' + params.toString(), {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await response.json();
                    if (!response.ok || !data?.ok) {
                        throw new Error(data?.message || 'Could not load finance data.');
                    }
                    this.applyFinanceData(data);
                    this.syncFinanceUrl(options.url || {});
                    if (options.toast) {
                        this.showToast(options.toast);
                    }
                    if (typeof options.onSuccess === 'function') {
                        options.onSuccess(data);
                    }
                    return data;
                } catch (err) {
                    this.showToast(err?.message || 'Could not load data.', 'error');
                    return null;
                } finally {
                    this.ajaxBusy = false;
                }
            },

            async changeLedgerMonth(month) {
                if (!month || month === this.weeklyMonth) return;
                const year = Number(String(month).slice(0, 4)) || this.year;
                await this.loadFinanceData({
                    month,
                    year,
                    url: { month, year },
                });
            },

            async changeFinanceYear(year) {
                const nextYear = Number(year) || this.year;
                if (nextYear === this.year) return;
                const monthPart = String(this.weeklyMonth || '').slice(5, 7) || '01';
                const month = `${nextYear}-${monthPart}`;
                this.year = nextYear;
                await this.loadFinanceData({
                    month,
                    year: nextYear,
                    url: { month, year: nextYear },
                });
            },

            async shiftSundayMonth(delta) {
                const parts = String(this.weeklyMonth || '').split('-').map(Number);
                let y = parts[0] || new Date().getFullYear();
                let m = parts[1] || (new Date().getMonth() + 1);
                m += Number(delta) || 0;
                while (m < 1) { m += 12; y -= 1; }
                while (m > 12) { m -= 12; y += 1; }
                const month = `${y}-${String(m).padStart(2, '0')}`;
                const wasOpen = this.showSundayModal;
                const data = await this.loadFinanceData({
                    month,
                    year: y,
                    url: { month, year: y },
                });
                if (!data) return;
                if (wasOpen) {
                    this.showSundayModal = false;
                    this.$nextTick(() => {
                        this.showSundayModal = true;
                        this.$nextTick(() => window.lucide?.createIcons());
                    });
                }
            },

            openBudgetEditor() {
                const lines = (this.budgetEditLines || []).map((line) => ({
                    ...line,
                    amount: Number(line.amount) || 0,
                }));
                this.budgetEditLines = lines;
                this.budgetNewLine = null;
                this.showBudgetEditor = true;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            closeBudgetEditor() {
                this.showBudgetEditor = false;
                this.budgetNewLine = null;
            },

            get budgetEditIncomeLines() {
                return (this.budgetEditLines || []).filter((l) => l.line_type === 'income');
            },

            get budgetEditExpenseLines() {
                return (this.budgetEditLines || []).filter((l) => l.line_type !== 'income');
            },

            get budgetEditIncomeTotal() {
                return this.budgetEditIncomeLines.reduce((sum, l) => sum + (Number(l.amount) || 0), 0);
            },

            get budgetEditExpenseTotal() {
                return this.budgetEditExpenseLines.reduce((sum, l) => sum + (Number(l.amount) || 0), 0);
            },

            startBudgetNewLine(type) {
                this.budgetNewLine = {
                    line_type: type === 'income' ? 'income' : 'expense',
                    section: type === 'income' ? 'Incomes' : 'Other expenses',
                    label: '',
                    amount: 0,
                };
                this.$nextTick(() => {
                    window.lucide?.createIcons();
                    const ref = type === 'income' ? this.$refs.budgetNewIncome : this.$refs.budgetNewExpense;
                    if (ref && typeof ref.scrollIntoView === 'function') {
                        ref.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    const input = ref?.querySelector?.('input[type="text"]');
                    if (input) input.focus();
                });
            },

            async saveBudgetMonth(event) {
                event.preventDefault();
                const form = event.target;
                await this.postAjax(form, {
                    onSuccess: () => {
                        this.closeBudgetEditor();
                        window.location.reload();
                    },
                });
            },

            async saveBudgetNewLine() {
                if (!this.budgetNewLine || !String(this.budgetNewLine.label || '').trim()) {
                    window.alert('Enter a line name.');
                    return;
                }
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/admin/finance/budget/lines';
                const fields = {
                    budget_year: String(this.budgetYear || this.year || new Date().getFullYear()),
                    month: this.weeklyMonth || '',
                    line_type: this.budgetNewLine.line_type || 'expense',
                    section: this.budgetNewLine.section || '',
                    label: String(this.budgetNewLine.label || '').trim(),
                    amount: String(Number(this.budgetNewLine.amount) || 0),
                };
                Object.keys(fields).forEach((name) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = fields[name];
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                await this.postAjax(form, {
                    onSuccess: () => {
                        this.budgetNewLine = null;
                        this.$nextTick(() => window.lucide?.createIcons());
                    },
                });
                form.remove();
            },

            async deleteBudgetLine(line) {
                if (!line || !line.id) return;
                const kind = line.line_type === 'income' ? 'income' : 'expense';
                const name = String(line.label || 'this line').trim() || 'this line';
                if (!window.confirm('Delete ' + kind + ' line “' + name + '”? This removes it from the budget year.')) {
                    return;
                }
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/admin/finance/budget/lines/' + encodeURIComponent(line.id) + '/delete';
                const yearInput = document.createElement('input');
                yearInput.type = 'hidden';
                yearInput.name = 'budget_year';
                yearInput.value = String(this.budgetYear || this.year || new Date().getFullYear());
                form.appendChild(yearInput);
                const monthInput = document.createElement('input');
                monthInput.type = 'hidden';
                monthInput.name = 'month';
                monthInput.value = this.weeklyMonth || '';
                form.appendChild(monthInput);
                document.body.appendChild(form);
                await this.postAjax(form, {
                    onSuccess: () => {
                        this.$nextTick(() => window.lucide?.createIcons());
                    },
                });
                form.remove();
            },

            async postAjax(form, options = {}) {
                if (!form || this.ajaxBusy) return null;
                const url = form.getAttribute('action');
                if (!url) return null;

                this.ajaxBusy = true;
                const submitBtn = options.submitBtn || form.querySelector('[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                try {
                    const response = await fetch(url, {
                        method: (form.getAttribute('method') || 'POST').toUpperCase(),
                        body: new FormData(form),
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    let data = null;
                    try {
                        data = await response.json();
                    } catch (_) {
                        throw new Error('Unexpected server response.');
                    }

                    if (!response.ok || !data?.ok) {
                        throw new Error(data?.message || 'Request failed.');
                    }

                    this.applyLedgerData(data);
                    if (!options.silent) {
                        this.showToast(data.message || 'Saved.');
                    }
                    if (typeof options.onSuccess === 'function') {
                        options.onSuccess(data);
                    }
                    return data;
                } catch (err) {
                    this.showToast(err?.message || 'Something went wrong.', 'error');
                    if (typeof options.onError === 'function') {
                        options.onError(err);
                    }
                    return null;
                } finally {
                    this.ajaxBusy = false;
                    if (submitBtn) submitBtn.disabled = false;
                }
            },

            async submitSundayAjax(form) {
                await this.postAjax(form, {
                    onSuccess: () => this.closeSundayModal(),
                });
            },

            async submitCollectionEditAjax(event) {
                event.preventDefault();
                const form = event.target;
                await this.postAjax(form, {
                    onSuccess: () => {
                        this.collectionEditRow = null;
                    },
                });
            },

            async clearCollectionMethodAjax(method) {
                if (!method) return;
                if (!window.confirm('Clear all amounts for this method in the selected month?')) return;
                this.collectionMenu = null;
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/admin/finance/collections/weekly/methods/' + encodeURIComponent(method) + '/clear';
                const monthInput = document.createElement('input');
                monthInput.type = 'hidden';
                monthInput.name = 'month';
                monthInput.value = this.weeklyMonth || '';
                form.appendChild(monthInput);
                document.body.appendChild(form);
                await this.postAjax(form);
                form.remove();
            },

            async submitNewWeeklyCategory(event) {
                if (!this.newCategory || !this.validateWeeklyCategory(this.newCategory)) {
                    event.preventDefault();
                    return;
                }
                event.preventDefault();
                await this.postAjax(event.target, {
                    onSuccess: () => {
                        this.newCategory = null;
                    },
                });
            },

            async submitWeeklyCategoryEdit(event) {
                if (!this.weeklyEditRow || !this.validateWeeklyCategory(this.weeklyEditRow)) {
                    event.preventDefault();
                    return;
                }
                event.preventDefault();
                await this.postAjax(event.target, {
                    onSuccess: () => {
                        this.weeklyEditRow = null;
                    },
                });
            },

            async deleteWeeklyCategoryAjax(slug) {
                if (!slug) return;
                if (!window.confirm('Delete this category and all its expense entries?')) return;
                this.weeklyMenu = null;
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/admin/finance/weekly/categories/' + encodeURIComponent(slug) + '/delete';
                const monthInput = document.createElement('input');
                monthInput.type = 'hidden';
                monthInput.name = 'month';
                monthInput.value = this.weeklyMonth || '';
                form.appendChild(monthInput);
                document.body.appendChild(form);
                const result = await this.postAjax(form);
                form.remove();
                if (result) {
                    this.weeklyEditRow = null;
                }
            },

            async deleteArrearAjax(id) {
                if (!id) return;
                if (!window.confirm('Delete this arrear entry?')) return;
                this.openMenu = null;
                this.viewRow = null;
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/admin/finance/arrears/' + encodeURIComponent(id) + '/delete';
                const yearInput = document.createElement('input');
                yearInput.type = 'hidden';
                yearInput.name = 'budget_year';
                yearInput.value = String(this.year || new Date().getFullYear());
                form.appendChild(yearInput);
                document.body.appendChild(form);
                await this.postAjax(form);
                form.remove();
            },

            get weeklyMenuRow() {
                if (!this.weeklyMenu) return null;
                return this.weeklyRows.find((r) => r.slug === this.weeklyMenu) || null;
            },

            get collectionMenuRow() {
                if (!this.collectionMenu) return null;
                return this.weeklyCollectionRows.find((r) => r.method === this.collectionMenu) || null;
            },

            get reconciliationMenuRow() {
                if (!this.reconciliationMenu) return null;
                return (this.reconciliation.weeks || []).find((w) => w.week_date === this.reconciliationMenu) || null;
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

            openWeeklyView(slug) {
                const row = this.weeklyRows.find((r) => r.slug === slug);
                if (!row) return;
                this.weeklyViewRow = {
                    slug: row.slug,
                    label: row.label,
                    hint: row.hint || '',
                    amounts: { ...(row.amounts || {}) },
                    total: Number(row.total) || 0,
                };
                this.weeklyMenu = null;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            openCollectionView(method) {
                const row = this.weeklyCollectionRows.find((r) => r.method === method);
                if (!row) return;
                this.collectionViewRow = {
                    method: row.method,
                    label: row.label,
                    desc: row.desc || '',
                    amounts: { ...(row.amounts || {}) },
                    total: Number(row.total) || 0,
                };
                this.collectionMenu = null;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            openCollectionEdit(method) {
                const row = this.weeklyCollectionRows.find((r) => r.method === method);
                if (!row) return;
                const amounts = {};
                (this.weeklyCollectionSundays || []).forEach((sun) => {
                    amounts[sun] = Number(row.amounts?.[sun]) || 0;
                });
                this.collectionEditRow = {
                    method: row.method,
                    label: row.label,
                    desc: row.desc || '',
                    amounts,
                };
                this.collectionMenu = null;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            get collectionEditTotal() {
                if (!this.collectionEditRow) return 0;
                return Object.values(this.collectionEditRow.amounts || {}).reduce(
                    (sum, v) => sum + (Number(v) || 0),
                    0
                );
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

            expenseItemsForDepartment(deptId, keepCategoryId = null) {
                const dept = this.departmentById(deptId);
                if (!dept) return [];
                const items = dept.categories || [];
                // Always expose every catalog line on edit/create selects.
                // (Previously hid lines whose label matched the department name, which
                // removed real items like "K.Kids" and broke edit prefill + save.)
                if (keepCategoryId != null && keepCategoryId !== '') {
                    const keepId = Number(keepCategoryId);
                    const hasKeep = items.some((cat) => Number(cat.id) === keepId);
                    if (!hasKeep && Number.isFinite(keepId) && keepId > 0) {
                        // Selected id missing from catalog — still show a stub so the select can bind.
                        return [
                            {
                                id: keepId,
                                label: dept.label,
                                slug: '',
                                account_code: '',
                            },
                            ...items,
                        ];
                    }
                }
                return items;
            },

            weeklyLineLabel(row) {
                if (!row) return '';
                if (row.expense_category_id === '__new__') {
                    return String(row.new_category_item_label || '').trim();
                }
                const items = this.categoriesForDepartment(row.department_id);
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
                if (!this.editRow || this._syncingEditCatalog) return;
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
                if (!this.editRow || this._syncingEditCatalog) return;
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

            async submitNewArrear(event) {
                if (!this.newArrear || !this.validateArrearCatalog(this.newArrear)) {
                    event.preventDefault();
                    return;
                }
                event.preventDefault();
                await this.postAjax(event.target, {
                    onSuccess: () => {
                        this.newArrear = null;
                    },
                });
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
                this.collectionMenu = null;
                this.reconciliationMenu = null;
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
                    this.weeklyDropdownPos = this.positionFixedDropdown(rect, 220, 176);
                }
                this.weeklyMenu = slug;
                this.openMenu = null;
                this.collectionMenu = null;
                this.reconciliationMenu = null;
                this.weeklyMenuIgnoreOutside = true;
                requestAnimationFrame(() => {
                    this.weeklyMenuIgnoreOutside = false;
                });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            toggleCollectionMenu(method, event) {
                if (this.collectionMenu === method) {
                    this.collectionMenu = null;
                    return;
                }
                const btn = event?.currentTarget;
                if (btn) {
                    const rect = btn.getBoundingClientRect();
                    this.collectionDropdownPos = this.positionFixedDropdown(rect, 220, 176);
                }
                this.collectionMenu = method;
                this.openMenu = null;
                this.weeklyMenu = null;
                this.reconciliationMenu = null;
                this.collectionMenuIgnoreOutside = true;
                requestAnimationFrame(() => {
                    this.collectionMenuIgnoreOutside = false;
                });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            toggleReconciliationMenu(weekDate, event) {
                if (this.reconciliationMenu === weekDate) {
                    this.reconciliationMenu = null;
                    return;
                }
                const btn = event?.currentTarget;
                if (btn) {
                    const rect = btn.getBoundingClientRect();
                    this.reconciliationDropdownPos = this.positionFixedDropdown(rect, 240, 220);
                }
                this.reconciliationMenu = weekDate;
                this.openMenu = null;
                this.weeklyMenu = null;
                this.collectionMenu = null;
                this.reconciliationMenuIgnoreOutside = true;
                requestAnimationFrame(() => {
                    this.reconciliationMenuIgnoreOutside = false;
                });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            openReconciliationView(weekDate) {
                const week = (this.reconciliation.weeks || []).find((w) => w.week_date === weekDate);
                if (!week) return;
                this.reconciliationViewRow = {
                    week_date: week.week_date,
                    collections: Number(week.collections) || 0,
                    expenses: Number(week.expenses) || 0,
                    balance: Number(week.balance) || 0,
                };
                this.reconciliationMenu = null;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            openWeeklyStatement(weekDate) {
                this.reconciliationMenu = null;
                if (!weekDate) return;
                const params = new URLSearchParams({
                    tab: 'reports',
                    view: 'weekly',
                    week_date: weekDate,
                    month: this.weeklyMonth || String(weekDate).slice(0, 7),
                    year: String(this.year || String(weekDate).slice(0, 4)),
                });
                window.location.href = '/admin/finance?' + params.toString();
            },

            goToLedger(sub) {
                this.reconciliationMenu = null;
                const params = new URLSearchParams({
                    tab: 'ledger',
                    sub: sub === 'collections' ? 'collections' : 'expenses',
                    month: this.weeklyMonth || '',
                    year: String(this.year || new Date().getFullYear()),
                });
                window.location.href = '/admin/finance?' + params.toString();
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

                const departmentId = row.department_id != null && Number(row.department_id) > 0
                    ? String(row.department_id)
                    : '';
                const categoryId = row.category_id != null && Number(row.category_id) > 0
                    ? String(row.category_id)
                    : '';
                let expenseGroup = String(row.expense_group || '').trim();
                if (!expenseGroup && departmentId) {
                    expenseGroup = this.groupForDepartment(departmentId);
                }

                this._syncingEditCatalog = true;
                this.editRow = {
                    ...row,
                    expense_group: expenseGroup,
                    department_id: departmentId,
                    category_id: categoryId,
                    new_category_label: '',
                    expense_item: row.expense_item || row.category_label || '',
                    amount_paid: Number(row.amount_paid) || 0,
                    amount_due: Number(row.amount_due) || 0,
                    month_incurred: row.month_incurred || '',
                    date_paid: row.date_paid || '',
                    paid_by_ref: row.paid_by_ref || '',
                    notes: row.notes || '',
                    budget_year: row.budget_year || this.year,
                };
                this.editFormKey = `${row.id}-${Date.now()}`;
                this.paymentRow = null;
                this.openMenu = null;
                this.viewRow = null;

                this.$nextTick(() => {
                    this.syncEditCatalogSelects(expenseGroup, departmentId, categoryId);
                });
            },

            syncEditCatalogSelects(expenseGroup, departmentId, categoryId) {
                if (!this.editRow) {
                    this._syncingEditCatalog = false;
                    return;
                }

                const setSelectValue = (el, value) => {
                    if (!el || value === '' || value == null) return;
                    const str = String(value);
                    el.value = str;
                    // If option was missing when we set value, try again after options paint.
                    if (el.value !== str) {
                        const opt = Array.from(el.options || []).find((o) => o.value === str);
                        if (opt) {
                            opt.selected = true;
                            el.value = str;
                        }
                    }
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                };

                const groupEl = document.getElementById('edit-expense-group');
                setSelectValue(groupEl, expenseGroup);
                if (this.editRow) this.editRow.expense_group = expenseGroup;

                this.$nextTick(() => {
                    if (!this.editRow) {
                        this._syncingEditCatalog = false;
                        return;
                    }
                    if (this.editRow) this.editRow.department_id = departmentId;
                    const deptEl = document.getElementById('edit-department')
                        || document.getElementById('edit-admin-department');
                    setSelectValue(deptEl, departmentId);

                    this.$nextTick(() => {
                        if (!this.editRow) {
                            this._syncingEditCatalog = false;
                            return;
                        }
                        if (this.editRow) this.editRow.category_id = categoryId;
                        const itemEl = document.getElementById('edit-ministry-item')
                            || document.getElementById('edit-admin-item');
                        setSelectValue(itemEl, categoryId);
                        this._syncingEditCatalog = false;
                        window.lucide?.createIcons();
                    });
                });
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

            async submitArrearEdit(event) {
                event.preventDefault();
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
                await this.postAjax(form, {
                    onSuccess: () => {
                        this.editRow = null;
                    },
                });
            },

            async submitArrearPayment(event) {
                event.preventDefault();
                const form = event.target;
                const payment = Number(this.paymentRow.record_payment) || 0;
                if (payment <= 0) {
                    window.alert('Enter a payment amount greater than zero.');
                    return;
                }
                if (!this.paymentRow.date_paid) {
                    this.paymentRow.date_paid = new Date().toISOString().slice(0, 10);
                }
                if (this.paymentComputedPaid > Number(this.paymentRow.amount_due)) {
                    window.alert('Total amount paid cannot exceed amount due.');
                    return;
                }
                await this.postAjax(form, {
                    onSuccess: () => {
                        this.paymentRow = null;
                    },
                });
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

            formatSundayShort(date) {
                if (!date) return '';
                const d = new Date(String(date) + 'T12:00:00');
                if (Number.isNaN(d.getTime())) return String(date);
                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            },

            formatSundayLong(date) {
                if (!date) return '';
                const d = new Date(String(date) + 'T12:00:00');
                if (Number.isNaN(d.getTime())) return String(date);
                return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
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

            statementExportUrl(format) {
                const params = new URLSearchParams({
                    view: this.statementView || 'monthly',
                    year: String(this.year || new Date().getFullYear()),
                });
                if (this.statementView !== 'annual' && this.weeklyMonth) {
                    params.set('month', this.weeklyMonth);
                }
                if (this.statementView === 'weekly' && this.statementWeekDate) {
                    params.set('week_date', this.statementWeekDate);
                }
                return '/admin/finance/statement/' + format + '?' + params.toString();
            },

            async setStatementView(view) {
                if (!['weekly', 'monthly', 'annual'].includes(view)) return;
                if (view === this.statementView && !this.statementBusy) return;
                await this.loadStatement({ view });
            },

            async changeStatementMonth(month) {
                if (!month || month === this.weeklyMonth) return;
                const year = Number(String(month).slice(0, 4)) || this.year;
                await this.loadStatement({ month, year, week_date: '' });
            },

            async changeStatementYear(year) {
                const nextYear = Number(year) || this.year;
                if (nextYear === this.year && this.statementView === 'annual') {
                    await this.loadStatement({ year: nextYear });
                    return;
                }
                const monthPart = String(this.weeklyMonth || '').slice(5, 7) || '01';
                const month = `${nextYear}-${monthPart}`;
                await this.loadStatement({
                    year: nextYear,
                    month: this.statementView === 'annual' ? this.weeklyMonth : month,
                    week_date: this.statementView === 'weekly' ? '' : this.statementWeekDate,
                });
            },

            async changeStatementWeek(weekDate) {
                if (!weekDate || weekDate === this.statementWeekDate) return;
                await this.loadStatement({ view: 'weekly', week_date: weekDate });
            },

            async loadStatement(options = {}) {
                if (this.statementBusy) return null;
                const view = options.view || this.statementView || 'monthly';
                const month = options.month || this.weeklyMonth || '';
                const year = options.year || this.year || (month ? Number(String(month).slice(0, 4)) : new Date().getFullYear());
                const weekDate = Object.prototype.hasOwnProperty.call(options, 'week_date')
                    ? (options.week_date || '')
                    : (this.statementWeekDate || '');

                const params = new URLSearchParams({
                    view,
                    year: String(year),
                });
                if (view !== 'annual' && month) {
                    params.set('month', month);
                }
                if (view === 'weekly' && weekDate) {
                    params.set('week_date', weekDate);
                }

                this.statementBusy = true;
                try {
                    const response = await fetch('/admin/finance/statement/data?' + params.toString(), {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await response.json();
                    if (!response.ok || !data?.ok) {
                        throw new Error(data?.message || 'Could not load statement.');
                    }

                    this.statementView = data.view || view;
                    this.year = Number(data.year) || year;
                    if (data.month) this.weeklyMonth = data.month;
                    this.statementWeekDate = data.week_date || '';
                    this.statementSundays = Array.isArray(data.sundays) ? data.sundays : [];

                    const wrap = this.$refs?.statementDocumentWrap;
                    if (wrap && data.html) {
                        wrap.innerHTML = data.html;
                    }

                    this.syncFinanceUrl({
                        tab: 'reports',
                        view: this.statementView,
                        year: this.year,
                        month: this.statementView === 'annual' ? null : this.weeklyMonth,
                        week_date: this.statementView === 'weekly' ? this.statementWeekDate : null,
                        sub: null,
                    });

                    this.$nextTick(() => window.lucide?.createIcons());
                    return data;
                } catch (err) {
                    this.showToast(err?.message || 'Could not load statement.', 'error');
                    return null;
                } finally {
                    this.statementBusy = false;
                }
            },
        };
    }

    function registerFinanceAlpine() {
        if (!window.Alpine || window.__kcFinanceAlpineRegistered) return;
        window.__kcFinanceAlpineRegistered = true;

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
    }

    document.addEventListener('alpine:init', registerFinanceAlpine);
    if (window.Alpine) {
        registerFinanceAlpine();
    }
})();
