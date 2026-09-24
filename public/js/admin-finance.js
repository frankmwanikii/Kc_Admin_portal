(function () {
    'use strict';

    function financeHubFactory(config) {
        return {
            newArrear: null,
            newCategory: null,
            newCollectionCategory: null,
            collectionCategoryEditRow: null,
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
            arrearDropdownPos: { top: 0, right: 8 },
            arrearMenuIgnoreOutside: false,
            inlineEdit: null,
            _inlineSaving: false,
            viewRow: null,
            editRow: null,
            paymentRow: null,
            search: '',
            billsMonthFilter: '',
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
            financeTab: config.financeTab || config.tab || 'dashboard',
            sundayPanelLock: null,
            dashboard: config.dashboard || {},
            financeYears: Array.isArray(config.financeYears) && config.financeYears.length
                ? config.financeYears.map(Number)
                : (() => {
                    const list = [];
                    const top = new Date().getFullYear() + 1;
                    for (let y = top; y >= 2024; y -= 1) list.push(y);
                    return list;
                })(),
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
            reportSub: ['statement','position'].includes(config.reportSub) ? config.reportSub : 'statement',
            positionBusy: false,
            yearReconciliation: config.yearReconciliation || { months: [], year_expenses: 0, year_collections: 0, year_balance: 0 },
            expenseGroups: config.expenseGroups || [],
            arrearsTotals: config.arrearsTotals || { due: 0, paid: 0, balance: 0 },
            tablePerPage: 10,
            arrearsPage: 1,
            collectionsPage: 1,
            weeklyPage: 1,
            _syncingEditCatalog: false,
            editFormKey: 0,
            weeklyDropdownPos: { top: 0, right: 8 },
            weeklyMenuIgnoreOutside: false,
            collectionDropdownPos: { top: 0, right: 8 },
            collectionMenuIgnoreOutside: false,
            reconciliationDropdownPos: { top: 0, right: 8 },
            reconciliationMenuIgnoreOutside: false,
            showSundayModal: false,
            monthPickerOpen: false,
            monthPickerTarget: 'ledger',
            monthPickerYear: Number(String(config.weeklyMonth || config.month || '').slice(0, 4)) || Number(config.year) || new Date().getFullYear(),
            monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            toast: null,
            toastTimer: null,
            ajaxBusy: false,

            init() {
                this.$nextTick(() => window.lucide?.createIcons());
                // Legacy ?record=1 bookmarks → dedicated Record Sunday page
                if (config.openSundayModal) {
                    const params = new URLSearchParams();
                    if (config.weeklyMonth || config.month) {
                        params.set('month', config.weeklyMonth || config.month);
                    }
                    if (config.sundayFormBase?.weekDate) {
                        params.set('week_date', config.sundayFormBase.weekDate);
                    }
                    if (config.financeTab === 'ledger') {
                        params.set('panel', config.ledgerSub === 'collections' ? 'collections' : 'expenses');
                        params.set('return_tab', 'ledger');
                        params.set('return_sub', config.ledgerSub === 'collections' ? 'collections' : 'expenses');
                    } else {
                        params.set('return_tab', config.financeTab || 'dashboard');
                    }
                    window.location.replace('/admin/finance/sunday?' + params.toString());
                    return;
                }
                this.$watch('search', () => { this.arrearsPage = 1; });
                this.$watch('billsMonthFilter', () => { this.arrearsPage = 1; });
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
                this.$nextTick(() => {
                    if (typeof window.initFinanceOverviewCharts === 'function') {
                        window.initFinanceOverviewCharts();
                    }
                });
            },

            openSundayModal(weekDate = null, panel = null) {
                this.goToSundayRecord(weekDate, panel);
            },

            goToSundayRecord(weekDate = null, panel = null) {
                const params = new URLSearchParams();
                const month = this.weeklyMonth || String(weekDate || '').slice(0, 7) || '';
                if (month) params.set('month', month);
                if (weekDate) params.set('week_date', weekDate);

                let lock = null;
                if (panel === 'collections' || panel === 'expenses') {
                    lock = panel;
                } else if (this.financeTab === 'ledger') {
                    lock = this.ledgerSub === 'collections' ? 'collections' : 'expenses';
                }
                if (lock) params.set('panel', lock);

                const tab = this.financeTab || 'ledger';
                params.set('return_tab', tab);
                if (tab === 'ledger') {
                    params.set('return_sub', this.ledgerSub === 'collections' ? 'collections' : 'expenses');
                }

                window.location.href = '/admin/finance/sunday?' + params.toString();
            },

            ensureWeeklySundays() {
                if (Array.isArray(this.weeklySundays) && this.weeklySundays.length) {
                    return;
                }
                if (Array.isArray(this.reconciliation?.weeks) && this.reconciliation.weeks.length) {
                    this.weeklySundays = this.reconciliation.weeks.map((w) => w.week_date).filter(Boolean);
                    return;
                }
                const fromSessions = Object.keys(this.sundaySessionsByDate || {});
                if (fromSessions.length) {
                    this.weeklySundays = fromSessions.sort();
                }
            },

            closeSundayModal() {
                this.showSundayModal = false;
                this.sundayPanelLock = null;
            },

            setLedgerSub(sub) {
                if (sub !== 'expenses' && sub !== 'collections') return;
                if (this.ledgerSub === sub) return;
                this.ledgerSub = sub;
                this.financeTab = 'ledger';
                this.weeklyMenu = null;
                this.collectionMenu = null;
                this.reconciliationMenu = null;
                this.syncFinanceUrl({ tab: 'ledger', sub });
                this.$nextTick(() => window.lucide?.createIcons());
            },

            buildSundayFormConfig() {
                const base = this.sundayFormBase || {};
                const lock = this.sundayPanelLock;
                let panel = 'expenses';
                if (lock === 'collections' || lock === 'expenses') {
                    panel = lock;
                } else if (this.ledgerSub === 'collections') {
                    panel = 'collections';
                }
                this.ensureWeeklySundays();
                return {
                    weekDate: base.weekDate || '',
                    sessionsByDate: this.sundaySessionsByDate || {},
                    weeklySundays: Array.isArray(this.weeklySundays) ? this.weeklySundays.slice() : [],
                    weeklyMonth: this.weeklyMonth || '',
                    methods: base.methods || Object.keys(this.paymentMethods || {}),
                    categories: base.categories || [],
                    presets: base.presets || {},
                    presetTotals: base.presetTotals || {},
                    activePanel: panel,
                    panelLock: lock,
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
                if (data.paymentMethods && typeof data.paymentMethods === 'object') {
                    this.paymentMethods = data.paymentMethods;
                    if (this.sundayFormBase) {
                        this.sundayFormBase.methods = Object.keys(data.paymentMethods);
                    }
                }
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
                return this.monthPickerLabel('ledger');
            },

            monthPickerValue(target = 'ledger') {
                if (target === 'bill-new') {
                    return String(this.newArrear?.month_incurred || '');
                }
                if (target === 'bill-edit') {
                    return String(this.editRow?.month_incurred || '');
                }
                if (target === 'bills-filter') {
                    return String(this.billsMonthFilter || '');
                }
                return String(this.weeklyMonth || '');
            },

            monthPickerLabel(target = 'ledger') {
                const value = this.monthPickerValue(target);
                if (!value) {
                    if (target === 'bills-filter') return 'All months';
                    return target === 'ledger' ? '' : 'Pick month';
                }
                const ym = this.toMonthInputValue(value);
                if (!/^\d{4}-\d{2}$/.test(ym)) return value;
                const d = new Date(String(ym) + '-01T12:00:00');
                if (Number.isNaN(d.getTime())) return value;
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

            toggleMonthPicker(target = 'ledger') {
                if (this.monthPickerOpen && this.monthPickerTarget === target) {
                    this.closeMonthPicker();
                    return;
                }
                this.monthPickerTarget = target || 'ledger';
                const parts = String(this.monthPickerValue(this.monthPickerTarget) || '').split('-');
                let year = Number(parts[0]);
                if (!year && this.monthPickerTarget === 'bills-filter') {
                    year = Number(this.year) || new Date().getFullYear();
                }
                this.monthPickerYear = year || Number(this.year) || new Date().getFullYear();
                this.monthPickerOpen = true;
                this.$nextTick(() => window.lucide?.createIcons());
            },

            closeMonthPicker() {
                this.monthPickerOpen = false;
            },

            shiftMonthPickerYear(delta) {
                this.monthPickerYear = Number(this.monthPickerYear || new Date().getFullYear()) + (Number(delta) || 0);
            },

            isMonthPickerSelected(monthNum) {
                const parts = String(this.monthPickerValue(this.monthPickerTarget) || '').split('-');
                const y = Number(parts[0]);
                const m = Number(parts[1]);
                return y === Number(this.monthPickerYear) && m === Number(monthNum);
            },

            async pickMonthPickerMonth(monthNum) {
                const y = Number(this.monthPickerYear) || new Date().getFullYear();
                const month = `${y}-${String(monthNum).padStart(2, '0')}`;
                const target = this.monthPickerTarget || 'ledger';
                this.closeMonthPicker();
                if (target === 'bill-new') {
                    if (this.newArrear) this.newArrear.month_incurred = month;
                    return;
                }
                if (target === 'bill-edit') {
                    if (this.editRow) this.editRow.month_incurred = month;
                    return;
                }
                if (target === 'bills-filter') {
                    this.billsMonthFilter = month;
                    return;
                }
                if (target === 'budget') {
                    this.navigateBudgetMonth(month);
                    return;
                }
                await this.changeLedgerMonth(month);
            },

            async pickMonthPickerToday() {
                const now = new Date();
                const month = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
                this.monthPickerYear = now.getFullYear();
                const target = this.monthPickerTarget || 'ledger';
                this.closeMonthPicker();
                if (target === 'bill-new') {
                    if (this.newArrear) this.newArrear.month_incurred = month;
                    return;
                }
                if (target === 'bill-edit') {
                    if (this.editRow) this.editRow.month_incurred = month;
                    return;
                }
                if (target === 'bills-filter') {
                    this.billsMonthFilter = month;
                    return;
                }
                if (target === 'budget') {
                    this.navigateBudgetMonth(month);
                    return;
                }
                await this.changeLedgerMonth(month);
            },

            navigateBudgetMonth(month) {
                const params = new URLSearchParams({
                    tab: 'budget',
                    month: month || this.weeklyMonth || '',
                    budget_year: String(this.budgetYear || this.year || new Date().getFullYear()),
                });
                window.location.href = '/admin/finance?' + params.toString();
            },

            clearBillsMonthFilter() {
                this.billsMonthFilter = '';
                this.closeMonthPicker();
            },

            async changeFinanceYear(year) {
                const nextYear = Number(year) || this.year;
                if (nextYear === this.year) return;
                const monthPart = String(this.weeklyMonth || '').slice(5, 7) || '01';
                const month = `${nextYear}-${monthPart}`;
                this.year = nextYear;
                this.billsMonthFilter = '';
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
                const params = new URLSearchParams({
                    tab: 'budget',
                    edit: '1',
                    month: this.weeklyMonth || '',
                    budget_year: String(this.budgetYear || this.year || new Date().getFullYear()),
                });
                window.location.href = '/admin/finance?' + params.toString();
            },

            closeBudgetEditor() {
                const params = new URLSearchParams({
                    tab: 'budget',
                    month: this.weeklyMonth || '',
                    budget_year: String(this.budgetYear || this.year || new Date().getFullYear()),
                });
                window.location.href = '/admin/finance?' + params.toString();
            },

            get budgetEditIncomeLines() {
                return (this.budgetEditLines || []).filter((l) => l.line_type === 'income');
            },

            get budgetEditExpenseLines() {
                return (this.budgetEditLines || []).filter((l) => l.line_type !== 'income');
            },

            get budgetExpenseSections() {
                return ['Administration', 'Ministry & Departments', 'Finance Costs', 'Other expenses'];
            },

            get budgetEditExpenseGroups() {
                const order = this.budgetExpenseSections;
                const groups = {};
                order.forEach((name) => {
                    groups[name] = { section: name, lines: [], total: 0 };
                });
                (this.budgetEditExpenseLines || []).forEach((line) => {
                    const section = String(line.section || '').trim() || 'Other expenses';
                    if (!groups[section]) {
                        groups[section] = { section, lines: [], total: 0 };
                    }
                    groups[section].lines.push(line);
                    groups[section].total += Number(line.amount) || 0;
                });
                return Object.values(groups).filter((g) => g.lines.length > 0);
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
                    section: type === 'income' ? 'Incomes' : 'Administration',
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
                        const params = new URLSearchParams({
                            tab: 'budget',
                            month: this.weeklyMonth || '',
                            budget_year: String(this.budgetYear || this.year || new Date().getFullYear()),
                        });
                        window.location.href = '/admin/finance?' + params.toString();
                    },
                });
            },

            async saveBudgetNewLine() {
                if (!this.budgetNewLine || !String(this.budgetNewLine.label || '').trim()) {
                    await window.AdminDialog?.alert({
                        title: 'Line name required',
                        message: 'Enter a line name.',
                        tone: 'warning',
                    });
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
                const ok = await window.AdminDialog?.confirm({
                    title: 'Delete budget line?',
                    message: 'Delete ' + kind + ' line “' + name + '”? This removes it from the budget year.',
                    confirmLabel: 'Delete line',
                    tone: 'danger',
                });
                if (!ok) return;
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
                const ok = await window.AdminDialog?.confirm({
                    title: 'Clear collection amounts?',
                    message: 'Clear all amounts for this method in the selected month?',
                    confirmLabel: 'Clear amounts',
                    tone: 'warning',
                });
                if (!ok) return;
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
                if (!this.newCategory || !(await this.validateWeeklyCategory(this.newCategory))) {
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
                if (!this.weeklyEditRow || !(await this.validateWeeklyCategory(this.weeklyEditRow))) {
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
                const ok = await window.AdminDialog?.confirm({
                    title: 'Delete expense category?',
                    message: 'Delete this category and all its expense entries?',
                    confirmLabel: 'Delete category',
                    tone: 'danger',
                });
                if (!ok) return;
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
                const ok = await window.AdminDialog?.confirm({
                    title: 'Delete bill?',
                    message: 'Delete this bill? This cannot be undone.',
                    confirmLabel: 'Delete bill',
                    tone: 'danger',
                });
                if (!ok) return;
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
                const monthFilter = String(this.billsMonthFilter || '').trim();
                return this.arrears.filter((r) => {
                    if (monthFilter) {
                        const incurred = this.toMonthInputValue(r.month_incurred, this.year);
                        if (incurred !== monthFilter) return false;
                    }
                    if (!q) return true;
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

            isFinanceCosts(groupSlug) {
                return groupSlug === 'finance_costs';
            },

            administrationDepartmentId() {
                const grp = this.expenseGroups.find((g) => g.slug === 'admin_expenses');
                const admin = grp?.departments?.find((d) => d.slug === 'administration');
                return admin?.id || '';
            },

            financeCostsDepartmentId() {
                const grp = this.expenseGroups.find((g) => g.slug === 'finance_costs');
                const fin = grp?.departments?.find((d) => d.slug === 'finance_costs');
                return fin?.id || grp?.departments?.[0]?.id || '';
            },

            primaryDepartmentIdForGroup(groupSlug) {
                if (this.isAdminExpenses(groupSlug)) {
                    return this.administrationDepartmentId();
                }
                if (this.isFinanceCosts(groupSlug)) {
                    return this.financeCostsDepartmentId();
                }
                const depts = this.departmentsForGroup(groupSlug);
                return depts[0]?.id || '';
            },

            /** Flat expense items for a top-level category (group). */
            expenseItemsForGroup(groupSlug, keepCategoryId = null) {
                const depts = this.departmentsForGroup(groupSlug);
                const items = [];
                for (const dept of depts) {
                    for (const cat of (dept.categories || [])) {
                        items.push({
                            ...cat,
                            department_id: dept.id,
                            department_label: dept.label,
                        });
                    }
                }
                if (keepCategoryId != null && keepCategoryId !== '' && keepCategoryId !== '__new__') {
                    const keepId = Number(keepCategoryId);
                    const hasKeep = items.some((cat) => Number(cat.id) === keepId);
                    if (!hasKeep && Number.isFinite(keepId) && keepId > 0) {
                        items.unshift({
                            id: keepId,
                            label: 'Selected item',
                            slug: '',
                            account_code: '',
                            department_id: this.primaryDepartmentIdForGroup(groupSlug),
                            department_label: '',
                        });
                    }
                }
                return items;
            },

            syncDepartmentFromExpenseItem(row, categoryField = 'category_id') {
                if (!row) return;
                const catId = row[categoryField];
                if (!catId || catId === '__new__') {
                    if (!row.department_id) {
                        row.department_id = this.primaryDepartmentIdForGroup(row.expense_group);
                    }
                    return;
                }
                const items = this.expenseItemsForGroup(row.expense_group, catId);
                const match = items.find((c) => String(c.id) === String(catId));
                if (match?.department_id) {
                    row.department_id = String(match.department_id);
                }
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
                this.newArrear.category_id = '';
                this.newArrear.new_category_label = '';
                this.newArrear.department_id = this.primaryDepartmentIdForGroup(this.newArrear.expense_group);
            },

            onEditGroupChange() {
                if (!this.editRow || this._syncingEditCatalog) return;
                this.editRow.category_id = '';
                this.editRow.new_category_label = '';
                this.editRow.department_id = this.primaryDepartmentIdForGroup(this.editRow.expense_group);
            },

            onNewExpenseItemChange() {
                if (!this.newArrear) return;
                this.syncDepartmentFromExpenseItem(this.newArrear, 'category_id');
                if (this.newArrear.category_id !== '__new__') {
                    this.newArrear.new_category_label = '';
                }
            },

            onEditExpenseItemChange() {
                if (!this.editRow || this._syncingEditCatalog) return;
                this.syncDepartmentFromExpenseItem(this.editRow, 'category_id');
                if (this.editRow.category_id !== '__new__') {
                    this.editRow.new_category_label = '';
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
                this.newCategory.expense_category_id = '';
                this.newCategory.new_category_item_label = '';
                this.newCategory.department_id = this.primaryDepartmentIdForGroup(this.newCategory.expense_group);
            },

            onWeeklyEditGroupChange() {
                if (!this.weeklyEditRow) return;
                this.weeklyEditRow.expense_category_id = '';
                this.weeklyEditRow.new_category_item_label = '';
                this.weeklyEditRow.department_id = this.primaryDepartmentIdForGroup(this.weeklyEditRow.expense_group);
            },

            onNewWeeklyExpenseItemChange() {
                if (!this.newCategory) return;
                this.syncDepartmentFromExpenseItem(this.newCategory, 'expense_category_id');
                if (this.newCategory.expense_category_id !== '__new__') {
                    this.newCategory.new_category_item_label = '';
                }
            },

            onWeeklyEditExpenseItemChange() {
                if (!this.weeklyEditRow) return;
                this.syncDepartmentFromExpenseItem(this.weeklyEditRow, 'expense_category_id');
                if (this.weeklyEditRow.expense_category_id !== '__new__') {
                    this.weeklyEditRow.new_category_item_label = '';
                }
            },

            async validateArrearCatalog(row) {
                if (!row.expense_group) {
                    await window.AdminDialog?.alert({
                        title: 'Category required',
                        message: 'Select a category (Administration, Ministry & Departments, or Finance Costs).',
                        tone: 'warning',
                    });
                    return false;
                }
                if (!row.department_id) {
                    row.department_id = this.primaryDepartmentIdForGroup(row.expense_group);
                }
                this.syncDepartmentFromExpenseItem(row, 'category_id');
                if (!Number(row.department_id)) {
                    await window.AdminDialog?.alert({
                        title: 'Category required',
                        message: 'Select a category for this expense.',
                        tone: 'warning',
                    });
                    return false;
                }
                if (row.category_id === '__new__') {
                    if (!String(row.new_category_label || '').trim()) {
                        await window.AdminDialog?.alert({
                            title: 'Item name required',
                            message: 'Enter a custom expense item name.',
                            tone: 'warning',
                        });
                        return false;
                    }
                } else if (!Number(row.category_id)) {
                    await window.AdminDialog?.alert({
                        title: 'Expense item required',
                        message: 'Select an expense item.',
                        tone: 'warning',
                    });
                    return false;
                }
                const due = Number(row.amount_due) || 0;
                const paid = Number(row.amount_paid) || 0;
                if (paid > due) {
                    await window.AdminDialog?.alert({
                        title: 'Invalid amounts',
                        message: 'Amount paid cannot exceed amount due.',
                        tone: 'warning',
                    });
                    return false;
                }
                return true;
            },

            async submitNewArrear(event) {
                if (!this.newArrear || !(await this.validateArrearCatalog(this.newArrear))) {
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

            paginationPages(list, pageKey) {
                const n = this.paginationTotalPages(list);
                const current = Math.min(Math.max(1, Number(this[pageKey]) || 1), n);
                if (n <= 7) {
                    return Array.from({ length: n }, (_, i) => i + 1);
                }

                const pages = [];
                const push = (v) => {
                    if (pages[pages.length - 1] !== v) pages.push(v);
                };

                push(1);
                const windowStart = Math.max(2, current - 1);
                const windowEnd = Math.min(n - 1, current + 1);
                if (windowStart > 2) push('…');
                for (let i = windowStart; i <= windowEnd; i += 1) push(i);
                if (windowEnd < n - 1) push('…');
                push(n);
                return pages;
            },

            clampPage(pageKey, list) {
                const max = this.paginationTotalPages(list);
                if (this[pageKey] > max) this[pageKey] = max;
                if (this[pageKey] < 1) this[pageKey] = 1;
            },

            firstPage(pageKey) {
                this[pageKey] = 1;
                this.openMenu = null;
                this.weeklyMenu = null;
            },

            lastPage(pageKey, list) {
                this[pageKey] = this.paginationTotalPages(list);
                this.openMenu = null;
                this.weeklyMenu = null;
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
                const p = parseInt(String(num).replace(/\D/g, ''), 10);
                const max = this.paginationTotalPages(list);
                if (p >= 1 && p <= max) {
                    this[pageKey] = p;
                    this.openMenu = null;
                    this.weeklyMenu = null;
                }
            },

            setTablePerPage(n) {
                const next = Number(n);
                if (![10, 15, 25, 50].includes(next)) return;
                this.tablePerPage = next;
                this.arrearsPage = 1;
                this.weeklyPage = 1;
                this.collectionsPage = 1;
                this.openMenu = null;
                this.weeklyMenu = null;
                this.$nextTick(() => window.lucide?.createIcons());
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

            positionFixedDropdown(rect, menuWidth = 188, menuHeight = 168) {
                const margin = 8;
                const gap = 6;
                const spaceBelow = window.innerHeight - rect.bottom - margin;
                const spaceAbove = rect.top - margin;
                const placeAbove = spaceBelow < menuHeight && spaceAbove > spaceBelow;
                const top = placeAbove
                    ? Math.max(margin, rect.top - menuHeight - gap)
                    : rect.bottom + gap;
                // Anchor to the trigger's right edge so the menu never stretches left.
                let right = Math.max(margin, window.innerWidth - rect.right);
                if (rect.right - menuWidth < margin) {
                    right = Math.max(margin, window.innerWidth - menuWidth - margin);
                }
                return { top, right };
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
                    sub: 'statement',
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
                        el.style.right = '';
                    }
                });

                dropdown.classList.add('arrears-dropdown--fixed');
                const rect = button.getBoundingClientRect();
                const pos = this.positionFixedDropdown(rect);
                dropdown.style.top = `${pos.top}px`;
                dropdown.style.right = `${pos.right}px`;
                dropdown.style.left = 'auto';
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
                const now = new Date();
                const monthIncurred = String(now.getFullYear()) + '-' + String(now.getMonth() + 1).padStart(2, '0');
                this.newArrear = {
                    expense_group: '',
                    department_id: '',
                    category_id: '',
                    new_category_label: '',
                    month_incurred: monthIncurred,
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

            async validateWeeklyCategory(row) {
                if (!row.expense_group) {
                    await window.AdminDialog?.alert({
                        title: 'Category required',
                        message: 'Select a category (Administration, Ministry & Departments, or Finance Costs).',
                        tone: 'warning',
                    });
                    return false;
                }
                if (!row.department_id) {
                    row.department_id = this.primaryDepartmentIdForGroup(row.expense_group);
                }
                this.syncDepartmentFromExpenseItem(row, 'expense_category_id');
                if (!Number(row.department_id)) {
                    await window.AdminDialog?.alert({
                        title: 'Category required',
                        message: 'Select a category for this expense.',
                        tone: 'warning',
                    });
                    return false;
                }
                if (row.expense_category_id === '__new__') {
                    if (!String(row.new_category_item_label || '').trim()) {
                        await window.AdminDialog?.alert({
                            title: 'Item name required',
                            message: 'Enter a custom expense item name.',
                            tone: 'warning',
                        });
                        return false;
                    }
                } else if (!Number(row.expense_category_id)) {
                    await window.AdminDialog?.alert({
                        title: 'Expense item required',
                        message: 'Select an expense item.',
                        tone: 'warning',
                    });
                    return false;
                }
                row.label = this.weeklyLineLabel(row);
                if (!String(row.label || '').trim()) {
                    await window.AdminDialog?.alert({
                        title: 'Line name required',
                        message: 'Expense line name is required.',
                        tone: 'warning',
                    });
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
                    month_incurred: this.toMonthInputValue(row.month_incurred, row.budget_year || this.year),
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

            isInlineEditing(scope, key, field) {
                return !!this.inlineEdit
                    && this.inlineEdit.scope === scope
                    && String(this.inlineEdit.key) === String(key)
                    && this.inlineEdit.field === field;
            },

            startInlineEdit(row, field) {
                if (!row || !['amount_due', 'amount_paid'].includes(field)) return;
                this.openMenu = null;
                this.weeklyMenu = null;
                this.collectionMenu = null;
                this.inlineEdit = {
                    scope: 'arrear',
                    key: Number(row.id),
                    field,
                    value: Number(row[field]) || 0,
                };
            },

            startWeeklyInlineEdit(scope, key, sun, currentValue) {
                if (!key || !sun || !['weeklyExpense', 'weeklyCollection'].includes(scope)) return;
                this.openMenu = null;
                this.weeklyMenu = null;
                this.collectionMenu = null;
                this.inlineEdit = {
                    scope,
                    key: String(key),
                    field: String(sun),
                    value: Number(currentValue) || 0,
                };
            },

            cancelInlineEdit() {
                this.inlineEdit = null;
                this._inlineSaving = false;
            },

            async commitInlineEdit(row) {
                if (!this.inlineEdit || this.inlineEdit.scope !== 'arrear' || !row) return;
                if (Number(this.inlineEdit.key) !== Number(row.id)) return;
                if (this._inlineSaving) return;

                const field = this.inlineEdit.field;
                let value = Number(this.inlineEdit.value);
                if (!Number.isFinite(value) || value < 0) value = 0;
                value = Math.round(value * 100) / 100;

                const due = field === 'amount_due' ? value : (Number(row.amount_due) || 0);
                const paid = field === 'amount_paid' ? value : (Number(row.amount_paid) || 0);
                if (paid > due) {
                    this.showToast('Amount paid cannot exceed amount due.', 'error');
                    return;
                }

                const unchanged = field === 'amount_due'
                    ? value === (Number(row.amount_due) || 0)
                    : value === (Number(row.amount_paid) || 0);
                if (unchanged) {
                    this.inlineEdit = null;
                    return;
                }

                const previous = { ...row };
                this.syncArrearInTable({ ...row, amount_due: due, amount_paid: paid });
                this.recomputeArrearsTotals();
                this.inlineEdit = null;
                this._inlineSaving = true;

                const formData = new FormData();
                formData.append('budget_year', String(row.budget_year || this.year || ''));
                formData.append('department_id', String(row.department_id || ''));
                formData.append('category_id', String(row.category_id || ''));
                formData.append('expense_item', String(row.expense_item || ''));
                formData.append('month_incurred', String(row.month_incurred || ''));
                formData.append('amount_due', String(due));
                formData.append('amount_paid', String(paid));
                formData.append('date_paid', String(row.date_paid || ''));
                formData.append('paid_by_ref', String(row.paid_by_ref || ''));
                formData.append('notes', String(row.notes || ''));

                try {
                    const response = await fetch('/admin/finance/arrears/' + row.id, {
                        method: 'POST',
                        body: formData,
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
                        throw new Error(data?.message || 'Could not save amount.');
                    }
                    this.applyLedgerData(data);
                    this.showToast(data.message || 'Amount updated.');
                } catch (err) {
                    this.syncArrearInTable(previous);
                    this.recomputeArrearsTotals();
                    this.showToast(err?.message || 'Could not save amount.', 'error');
                } finally {
                    this._inlineSaving = false;
                }
            },

            patchWeeklyRowAmount(listKey, idKey, idValue, sun, amount) {
                const rows = this[listKey];
                if (!Array.isArray(rows)) return null;
                const idx = rows.findIndex((r) => String(r[idKey]) === String(idValue));
                if (idx < 0) return null;
                const previous = {
                    ...rows[idx],
                    amounts: { ...(rows[idx].amounts || {}) },
                };
                const next = {
                    ...rows[idx],
                    amounts: { ...(rows[idx].amounts || {}) },
                };
                next.amounts[sun] = amount;
                next.total = Object.values(next.amounts).reduce((sum, v) => sum + (Number(v) || 0), 0);
                rows.splice(idx, 1, next);
                return previous;
            },

            async commitWeeklyInlineEdit(scope, key) {
                if (!this.inlineEdit || this.inlineEdit.scope !== scope) return;
                if (String(this.inlineEdit.key) !== String(key)) return;
                if (this._inlineSaving) return;

                const sun = this.inlineEdit.field;
                let value = Number(this.inlineEdit.value);
                if (!Number.isFinite(value) || value < 0) value = 0;
                value = Math.round(value * 100) / 100;

                const isExpense = scope === 'weeklyExpense';
                const listKey = isExpense ? 'weeklyRows' : 'weeklyCollectionRows';
                const idKey = isExpense ? 'slug' : 'method';
                const rows = this[listKey] || [];
                const row = rows.find((r) => String(r[idKey]) === String(key));
                const current = Number(row?.amounts?.[sun]) || 0;
                if (value === current) {
                    this.inlineEdit = null;
                    return;
                }

                const previous = this.patchWeeklyRowAmount(listKey, idKey, key, sun, value);
                this.inlineEdit = null;
                this._inlineSaving = true;

                const formData = new FormData();
                formData.append('week_date', String(sun));
                formData.append('amount', String(value));
                formData.append('month', String(this.weeklyMonth || sun.slice(0, 7)));
                if (isExpense) {
                    formData.append('category_slug', String(key));
                } else {
                    formData.append('method', String(key));
                }

                const url = isExpense
                    ? '/admin/finance/weekly/cell'
                    : '/admin/finance/collections/weekly/cell';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
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
                        throw new Error(data?.message || 'Could not save amount.');
                    }
                    this.applyLedgerData(data);
                    this.showToast(data.message || 'Amount updated.');
                } catch (err) {
                    if (previous) {
                        const idx = (this[listKey] || []).findIndex((r) => String(r[idKey]) === String(key));
                        if (idx >= 0) this[listKey].splice(idx, 1, previous);
                    }
                    this.showToast(err?.message || 'Could not save amount.', 'error');
                } finally {
                    this._inlineSaving = false;
                }
            },

            recomputeArrearsTotals() {
                let due = 0;
                let paid = 0;
                let balance = 0;
                for (const row of this.arrears) {
                    due += Number(row.amount_due) || 0;
                    paid += Number(row.amount_paid) || 0;
                    balance += Number(row.balance_owing) || 0;
                }
                this.arrearsTotals = {
                    due: Math.round(due * 100) / 100,
                    paid: Math.round(paid * 100) / 100,
                    balance: Math.round(balance * 100) / 100,
                };
            },

            async submitArrearEdit(event) {
                event.preventDefault();
                const form = event.target;
                const payload = {
                    ...this.editRow,
                    amount_paid: Number(this.editRow.amount_paid) || 0,
                };
                if (!(await this.validateArrearCatalog(payload))) {
                    return;
                }
                if (!String(payload.expense_item || '').trim()) {
                    await window.AdminDialog?.alert({
                        title: 'Title required',
                        message: 'Enter an expense title.',
                        tone: 'warning',
                    });
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
                    await window.AdminDialog?.alert({
                        title: 'Payment required',
                        message: 'Enter a payment amount greater than zero.',
                        tone: 'warning',
                    });
                    return;
                }
                if (!this.paymentRow.date_paid) {
                    this.paymentRow.date_paid = new Date().toISOString().slice(0, 10);
                }
                if (this.paymentComputedPaid > Number(this.paymentRow.amount_due)) {
                    await window.AdminDialog?.alert({
                        title: 'Invalid payment',
                        message: 'Total amount paid cannot exceed amount due.',
                        tone: 'warning',
                    });
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

            sundayDayNum(date) {
                if (!date) return '';
                const d = new Date(String(date) + 'T12:00:00');
                if (Number.isNaN(d.getTime())) return '';
                return String(d.getDate());
            },

            formatDate(value) {
                if (!value) return '—';
                const d = new Date(value + 'T00:00:00');
                if (Number.isNaN(d.getTime())) return value;
                return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            },

            /** Display YYYY-MM (or legacy free text) as "MMM YYYY". */
            formatMonthIncurred(value) {
                const raw = String(value || '').trim();
                if (!raw) return '—';
                const ym = this.toMonthInputValue(raw);
                if (/^\d{4}-\d{2}$/.test(ym)) {
                    const [y, m] = ym.split('-').map(Number);
                    const d = new Date(y, m - 1, 1);
                    if (!Number.isNaN(d.getTime())) {
                        return d.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
                    }
                }
                return raw;
            },

            /** Normalize stored period text into YYYY-MM for <input type="month">. */
            toMonthInputValue(value, fallbackYear) {
                const raw = String(value || '').trim();
                if (/^\d{4}-\d{2}$/.test(raw)) {
                    return raw;
                }
                if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                    return raw.slice(0, 7);
                }
                const months = {
                    jan: 1, january: 1, feb: 2, february: 2, mar: 3, march: 3,
                    apr: 4, april: 4, may: 5, jun: 6, june: 6, jul: 7, july: 7,
                    aug: 8, august: 8, sep: 9, sept: 9, september: 9,
                    oct: 10, october: 10, nov: 11, november: 11, dec: 12, december: 12,
                };
                const lower = raw.toLowerCase();
                const yearMatch = lower.match(/\b(20\d{2})\b/);
                const year = yearMatch ? Number(yearMatch[1]) : (Number(fallbackYear) || new Date().getFullYear());
                let monthNum = 0;
                Object.keys(months).forEach((key) => {
                    if (monthNum) return;
                    if (new RegExp('\\b' + key + '\\b').test(lower)) {
                        monthNum = months[key];
                    }
                });
                if (!monthNum) {
                    const mMatch = lower.match(/\b(0?[1-9]|1[0-2])\b/);
                    if (mMatch && !yearMatch) {
                        monthNum = Number(mMatch[1]);
                    } else if (mMatch && lower.indexOf(mMatch[0]) < lower.indexOf(String(year))) {
                        monthNum = Number(mMatch[1]);
                    }
                }
                if (monthNum >= 1 && monthNum <= 12) {
                    return String(year) + '-' + String(monthNum).padStart(2, '0');
                }
                if (/^\d{4}$/.test(raw)) {
                    return raw + '-01';
                }
                // Unparseable legacy text — land on selected finance year / current month so the picker is usable
                const y = Number(fallbackYear) || new Date().getFullYear();
                const m = new Date().getMonth() + 1;
                return String(y) + '-' + String(m).padStart(2, '0');
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

            setReportSub(sub) {
                if (!['statement', 'position', 'budget'].includes(sub) || sub === this.reportSub) return;
                const params = new URLSearchParams({
                    tab: 'reports',
                    sub,
                    year: String(this.year || new Date().getFullYear()),
                });
                if (sub === 'statement') {
                    params.set('view', this.statementView || 'monthly');
                    if (this.weeklyMonth) params.set('month', this.weeklyMonth);
                    if (this.statementView === 'weekly' && this.statementWeekDate) {
                        params.set('week_date', this.statementWeekDate);
                    }
                } else if (sub === 'budget') {
                    if (this.weeklyMonth) params.set('month', this.weeklyMonth);
                    params.set('budget_year', String(this.budgetYear || this.year || new Date().getFullYear()));
                }
                window.location.href = '/admin/finance?' + params.toString();
            },

            positionExportUrl(format) {
                const params = new URLSearchParams({
                    year: String(this.year || new Date().getFullYear()),
                });
                return '/admin/finance/position/' + format + '?' + params.toString();
            },

            async changePositionYear(year) {
                const nextYear = Number(year) || this.year;
                if (nextYear === this.year && !this.positionBusy) {
                    await this.loadPosition({ year: nextYear });
                    return;
                }
                await this.loadPosition({ year: nextYear });
            },

            async loadPosition(options = {}) {
                if (this.positionBusy) return null;
                const year = options.year || this.year || new Date().getFullYear();
                this.positionBusy = true;
                try {
                    const params = new URLSearchParams({ year: String(year) });
                    const res = await fetch('/admin/finance/position/data?' + params.toString(), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await res.json();
                    if (!data || !data.ok) {
                        this.showToast((data && data.message) || 'Could not load consolidated position.', 'error');
                        return null;
                    }
                    this.year = data.year || year;
                    if (Array.isArray(data.financeYears) && data.financeYears.length) {
                        this.financeYears = data.financeYears.map(Number);
                    }
                    this.reportSub = 'position';
                    if (this.$refs.positionDocumentWrap && data.html) {
                        this.$refs.positionDocumentWrap.innerHTML = data.html;
                        if (window.lucide && typeof window.lucide.createIcons === 'function') {
                            window.lucide.createIcons();
                        }
                    }
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', 'reports');
                    url.searchParams.set('sub', 'position');
                    url.searchParams.set('year', String(this.year));
                    window.history.replaceState({}, '', url.toString());
                    return data;
                } catch (err) {
                    this.showToast('Could not load consolidated position.', 'error');
                    return null;
                } finally {
                    this.positionBusy = false;
                }
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
            weeklySundays: Array.isArray(config.weeklySundays) ? config.weeklySundays.slice() : [],
            weeklyMonth: config.weeklyMonth || '',
            methods: config.methods || [],
            categories: config.categories || [],
            presets: config.presets || {},
            presetTotals: config.presetTotals || { standard: 12200, full: 17200 },
            activePanel: config.activePanel === 'collections' ? 'collections' : 'expenses',
            panelLock: config.panelLock === 'collections' || config.panelLock === 'expenses'
                ? config.panelLock
                : null,
            collectionFields: {},
            expenseFields: {},
            notes: '',
            collectionsTotal: 0,
            expensesTotal: 0,
            weekBalance: 0,
            activePreset: '',

            init() {
                if (this.panelLock) {
                    this.activePanel = this.panelLock;
                }
                this.pullFromHub();
                this.refreshSundayOptions();
                this.loadSession(this.weekDate);
                this.$nextTick(() => {
                    this.pullFromHub();
                    this.refreshSundayOptions();
                    window.lucide?.createIcons();
                });
                this.$watch('weekDate', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('activePanel', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                });
            },

            hub() {
                let node = this.$el ? this.$el.parentElement : null;
                while (node) {
                    try {
                        const data = window.Alpine && window.Alpine.$data(node);
                        if (data && typeof data.shiftSundayMonth === 'function') {
                            return data;
                        }
                    } catch (_) { /* keep walking */ }
                    node = node.parentElement;
                }
                return null;
            },

            pullFromHub() {
                const hub = this.hub();
                if (!hub) return;
                hub.ensureWeeklySundays?.();
                if (Array.isArray(hub.weeklySundays) && hub.weeklySundays.length) {
                    this.weeklySundays = hub.weeklySundays.slice();
                }
                if (hub.weeklyMonth) {
                    this.weeklyMonth = hub.weeklyMonth;
                }
                if (hub.sundaySessionsByDate) {
                    this.sessionsByDate = hub.sundaySessionsByDate;
                }
            },

            setActivePanel(panel) {
                if (this.panelLock) return;
                this.activePanel = panel === 'collections' ? 'collections' : 'expenses';
            },

            get showPanelSwitch() {
                return !this.panelLock;
            },

            get monthLabel() {
                const month = this.weeklyMonth || '';
                if (!month) return 'Select month';
                const d = new Date(String(month) + '-01T12:00:00');
                if (Number.isNaN(d.getTime())) return String(month);
                return d.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
            },

            get sundayDates() {
                let sundays = Array.isArray(this.weeklySundays) ? this.weeklySundays.slice() : [];
                if (!sundays.length) {
                    sundays = Object.keys(this.sessionsByDate || {}).sort();
                }
                return sundays;
            },

            dayNum(date) {
                if (!date) return '';
                const parts = String(date).split('-');
                if (parts.length === 3) {
                    const day = Number(parts[2]);
                    if (day > 0) return String(day);
                }
                const d = new Date(String(date) + 'T12:00:00');
                if (Number.isNaN(d.getTime())) return '';
                return String(d.getDate());
            },

            formatLong(date) {
                if (!date) return '';
                const d = new Date(String(date) + 'T12:00:00');
                if (Number.isNaN(d.getTime())) return String(date);
                return d.toLocaleDateString('en-GB', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                });
            },

            async shiftMonth(delta) {
                const hub = this.hub();
                if (!hub || typeof hub.shiftSundayMonth !== 'function') return;
                await hub.shiftSundayMonth(delta);
            },

            syncSelectedSunday() {
                const sundays = this.sundayDates;
                if (!sundays.length) {
                    if (this.weekDate) {
                        this.weekDate = '';
                    }
                    return;
                }
                if (!sundays.includes(this.weekDate)) {
                    this.weekDate = sundays[0];
                    this.loadSession(this.weekDate);
                }
            },

            selectSunday(sun) {
                if (!sun || sun === this.weekDate) return;
                this.weekDate = sun;
                this.onDateChange();
                this.$nextTick(() => window.lucide?.createIcons());
            },

            sundayHasData(date) {
                const session = this.sessionsByDate[date];
                if (!session) return false;
                const colSum = Object.values(session.collections || {}).reduce((s, v) => s + (Number(v) || 0), 0);
                const expSum = Object.values(session.expenses || {}).reduce((s, v) => s + (Number(v) || 0), 0);
                return colSum > 0 || expSum > 0 || Boolean(session.notes);
            },

            refreshSundayOptions() {
                this.syncSelectedSunday();
            },

            get hasSavedData() {
                return this.sundayHasData(this.weekDate);
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

    window.initFinanceOverviewCharts = function initFinanceOverviewCharts() {
        if (typeof Chart === 'undefined') {
            return false;
        }
        const dataEl = document.getElementById('fin-dashboard-charts-data');
        if (!dataEl) {
            return false;
        }

        let charts;
        try {
            charts = JSON.parse(dataEl.textContent || '{}');
        } catch (_) {
            return false;
        }

        const fmtKes = (v) => 'KES ' + Number(v || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 });
        const tooltipTheme = {
            backgroundColor: '#0b486d',
            padding: 10,
            cornerRadius: 8,
            titleFont: { size: 12, weight: '600' },
            bodyFont: { size: 12 },
            callbacks: {
                label(ctx) {
                    const label = ctx.dataset.label || ctx.label || '';
                    const val = ctx.parsed.y !== undefined ? ctx.parsed.y : ctx.parsed;
                    return ' ' + label + ': ' + fmtKes(val);
                },
            },
        };

        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
        Chart.defaults.color = '#64748b';

        const trend = charts.trend || {};
        const trendCanvas = document.getElementById('finChartTrend');
        if (trendCanvas && Array.isArray(trend.labels) && trend.labels.length) {
            const existing = Chart.getChart(trendCanvas);
            if (existing) existing.destroy();
            new Chart(trendCanvas, {
                type: 'bar',
                data: {
                    labels: trend.labels,
                    datasets: [
                        {
                            label: 'Collections',
                            data: trend.collections || [],
                            backgroundColor: 'rgba(45, 160, 217, 0.85)',
                            borderRadius: 6,
                            maxBarThickness: 28,
                            order: 2,
                        },
                        {
                            label: 'Expenses',
                            data: trend.expenses || [],
                            backgroundColor: 'rgba(232, 119, 34, 0.88)',
                            borderRadius: 6,
                            maxBarThickness: 28,
                            order: 2,
                        },
                        {
                            label: 'Expense budget',
                            data: trend.budget_expenses || [],
                            type: 'line',
                            borderColor: '#0f766e',
                            backgroundColor: 'rgba(15, 118, 110, 0.08)',
                            borderWidth: 2.5,
                            borderDash: [6, 4],
                            pointRadius: 3,
                            pointBackgroundColor: '#0f766e',
                            tension: 0.25,
                            order: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: tooltipTheme,
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: '600' } },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.18)' },
                            ticks: {
                                callback(value) {
                                    const n = Number(value);
                                    if (Math.abs(n) >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
                                    if (Math.abs(n) >= 1_000) return Math.round(n / 1_000) + 'k';
                                    return n;
                                },
                            },
                        },
                    },
                },
            });
        }

        const pieTooltip = {
            ...tooltipTheme,
            callbacks: {
                label(ctx) {
                    const total = (ctx.dataset.data || []).reduce((s, n) => s + Number(n || 0), 0);
                    const val = Number(ctx.parsed || 0);
                    const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                    return ' ' + (ctx.label || '') + ': ' + fmtKes(val) + ' (' + pct + '%)';
                },
            },
        };

        const pieOpts = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 14,
                        font: { size: 11, weight: '600' },
                    },
                },
                tooltip: pieTooltip,
            },
        };

        const expensePie = charts.expense_groups || {};
        const expenseCanvas = document.getElementById('finChartExpensePie');
        if (expenseCanvas && Array.isArray(expensePie.amounts) && expensePie.amounts.some((n) => Number(n) > 0)) {
            const existing = Chart.getChart(expenseCanvas);
            if (existing) existing.destroy();
            new Chart(expenseCanvas, {
                type: 'doughnut',
                data: {
                    labels: expensePie.labels || [],
                    datasets: [{
                        data: expensePie.amounts || [],
                        backgroundColor: ['#0b486d', '#e87722', '#2da0d9'],
                        borderWidth: 0,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    ...pieOpts,
                    cutout: '58%',
                },
            });
        }

        const methodPie = charts.collection_methods || {};
        const methodCanvas = document.getElementById('finChartCollectionPie');
        if (methodCanvas && Array.isArray(methodPie.amounts) && methodPie.amounts.some((n) => Number(n) > 0)) {
            const existing = Chart.getChart(methodCanvas);
            if (existing) existing.destroy();
            new Chart(methodCanvas, {
                type: 'doughnut',
                data: {
                    labels: methodPie.labels || [],
                    datasets: [{
                        data: methodPie.amounts || [],
                        backgroundColor: ['#2da0d9', '#0b486d', '#e87722'],
                        borderWidth: 0,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    ...pieOpts,
                    cutout: '58%',
                },
            });
        }

        // Nudge Chart.js to measure containers after AJAX layout.
        requestAnimationFrame(() => {
            ['finChartTrend', 'finChartExpensePie', 'finChartCollectionPie'].forEach((id) => {
                const el = document.getElementById(id);
                const chart = el && Chart.getChart(el);
                chart?.resize();
            });
        });

        return true;
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.initFinanceOverviewCharts());
    } else {
        // Defer so late-injected AJAX HTML (or scripts ordered before the fragment) is ready.
        queueMicrotask(() => window.initFinanceOverviewCharts());
    }
    document.addEventListener('admin:content-loaded', () => {
        window.initFinanceOverviewCharts();
    });
})();
