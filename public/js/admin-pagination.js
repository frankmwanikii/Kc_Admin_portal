(function () {
    'use strict';

    function paginationMethods(perPageKey) {
        return {
            paginationTotalPages(list) {
                const perPage = this[perPageKey];
                return Math.max(1, Math.ceil(list.length / perPage));
            },

            paginate(list, page) {
                const perPage = this[perPageKey];
                const totalPages = this.paginationTotalPages(list);
                const p = Math.min(page, totalPages);
                const start = (p - 1) * perPage;
                return list.slice(start, start + perPage);
            },

            paginationFrom(list, page) {
                if (!list.length) return 0;
                const p = Math.min(page, this.paginationTotalPages(list));
                const perPage = this[perPageKey];
                return (p - 1) * perPage + 1;
            },

            paginationTo(list, page) {
                if (!list.length) return 0;
                const p = Math.min(page, this.paginationTotalPages(list));
                const perPage = this[perPageKey];
                return Math.min(p * perPage, list.length);
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
                if (this[pageKey] > 1) this[pageKey] -= 1;
            },

            nextPage(pageKey, list) {
                if (this[pageKey] < this.paginationTotalPages(list)) this[pageKey] += 1;
            },

            goPage(pageKey, num, list) {
                const p = Number(num);
                const max = this.paginationTotalPages(list);
                if (p >= 1 && p <= max) this[pageKey] = p;
            },

            toggleMenu(id, event) {
                if (this.openMenu === id) {
                    this.openMenu = null;
                    this.clearFixedDropdowns();
                    return;
                }
                this.openMenu = id;
                this.$nextTick(() => {
                    this.positionActionDropdown(event?.currentTarget);
                    window.lucide?.createIcons();
                });
            },

            clearFixedDropdowns() {
                document.querySelectorAll('.arrears-dropdown--fixed').forEach((el) => {
                    el.classList.remove('arrears-dropdown--fixed');
                    el.style.top = '';
                    el.style.left = '';
                });
            },

            positionActionDropdown(button) {
                if (!button) return;
                const dropdown = button.nextElementSibling;
                if (!dropdown?.classList.contains('arrears-dropdown')) return;

                this.clearFixedDropdowns();
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
        };
    }

    function tableInit(pageKey, listKey) {
        return {
            init() {
                this.$watch(listKey, () => this.clampPage(pageKey, this[listKey]));
                this.$watch('search', () => {
                    this[pageKey] = 1;
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch(pageKey, () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$nextTick(() => window.lucide?.createIcons());
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('paginatedTable', (rows) => createPaginatedTable(rows));

        Alpine.data('memberTable', (rows, formTypeLabels) => {
            const pagination = paginationMethods('tablePerPage');
            return {
                rows: rows || [],
                formTypeLabels: formTypeLabels || {},
                page: 1,
                tablePerPage: 10,
                search: '',
                statusFilter: '',
                formTypeFilter: '',
                openMenu: null,
                menuPos: { top: 0, left: 0 },
                activeMember: null,
                showForm: false,
                form: {
                    name: '',
                    phone: '',
                    email: '',
                    campus: 'nanyuki',
                    form_type: 'manual',
                    notes: '',
                },

                get filteredRows() {
                    let list = this.rows;
                    const q = this.search.trim().toLowerCase();
                    if (q) {
                        list = list.filter((m) =>
                            (m.submitter_name || '').toLowerCase().includes(q)
                            || (m.submitter_phone || '').toLowerCase().includes(q)
                            || (m.submitter_email || '').toLowerCase().includes(q)
                            || (m.campus_id || '').toLowerCase().includes(q)
                            || this.formTypeLabel(m.form_type).toLowerCase().includes(q)
                        );
                    }
                    if (this.statusFilter) {
                        list = list.filter((m) => (m.status || 'new') === this.statusFilter);
                    }
                    if (this.formTypeFilter) {
                        list = list.filter((m) => (m.form_type || '') === this.formTypeFilter);
                    }
                    return list;
                },

                get paginatedRows() {
                    return this.paginate(this.filteredRows, this.page);
                },

                ...pagination,
                ...tableInit('page', 'filteredRows'),

                init() {
                    this.$watch('filteredRows', () => this.clampPage('page', this.filteredRows));
                    this.$watch('search', () => {
                        this.page = 1;
                        this.$nextTick(() => window.lucide?.createIcons());
                    });
                    this.$watch('statusFilter', () => { this.page = 1; });
                    this.$watch('formTypeFilter', () => { this.page = 1; });
                    this.$watch('page', () => this.$nextTick(() => window.lucide?.createIcons()));
                    this.$nextTick(() => window.lucide?.createIcons());

                    this._closeMenuOnScroll = () => {
                        if (this.openMenu) {
                            this.openMenu = null;
                            this.activeMember = null;
                        }
                    };
                    window.addEventListener('scroll', this._closeMenuOnScroll, true);
                },

                toggleMenu(id, event) {
                    if (this.openMenu === id) {
                        this.openMenu = null;
                        this.activeMember = null;
                        return;
                    }
                    this.activeMember = this.rows.find((m) => m.id == id) || null;
                    const btn = event?.currentTarget;
                    if (btn) {
                        const rect = btn.getBoundingClientRect();
                        const width = 168;
                        this.menuPos = {
                            top: rect.bottom + 6,
                            left: Math.min(
                                Math.max(8, rect.right - width),
                                window.innerWidth - width - 8
                            ),
                        };
                    }
                    this.openMenu = id;
                    this.$nextTick(() => window.lucide?.createIcons());
                },

                confirmDelete(member) {
                    if (!member?.id) return;
                    if (!confirm('Delete this member registration? This cannot be undone.')) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/admin/members/' + member.id + '/delete';
                    form.style.display = 'none';
                    document.body.appendChild(form);
                    form.submit();
                },

                openAddForm() {
                    this.openMenu = null;
                    this.activeMember = null;
                    this.form = {
                        name: '',
                        phone: '',
                        email: '',
                        campus: 'nanyuki',
                        form_type: 'manual',
                        notes: '',
                    };
                    this.showForm = true;
                    this.$nextTick(() => window.lucide?.createIcons());
                },

                closeAddForm() {
                    this.showForm = false;
                },

                formatMemberDate(value) {
                    if (!value) return '—';
                    const d = new Date(String(value).replace(' ', 'T'));
                    if (Number.isNaN(d.getTime())) return value;
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                },

                formTypeLabel(formType) {
                    if (!formType) return '—';
                    return this.formTypeLabels[formType] || String(formType).replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
                },

                memberStatusLabel(status) {
                    if (status === 'reviewed') return 'Reviewed';
                    if (status === 'new') return 'New';
                    if (status === 'archived') return 'Archived';
                    return status ? String(status).charAt(0).toUpperCase() + String(status).slice(1) : '—';
                },

                memberStatusClass(status) {
                    if (status === 'new') return 'admin-status-pill admin-status-pill--new';
                    if (status === 'reviewed') return 'admin-status-pill admin-status-pill--reviewed';
                    if (status === 'archived') return 'admin-status-pill admin-status-pill--archived';
                    return 'admin-status-pill admin-status-pill--default';
                },

                memberInitial(name) {
                    const n = String(name || '?').trim();
                    return n ? n.charAt(0).toUpperCase() : '?';
                },
            };
        });

        Alpine.data('inventoryTable', (rows) => {
            const pagination = paginationMethods('tablePerPage');
            return {
                rows: rows || [],
                page: 1,
                tablePerPage: 10,
                search: '',
                categoryFilter: '',
                openMenu: null,
                menuPos: { top: 0, left: 0 },
                activeItem: null,
                showForm: false,
                editingItem: null,
                form: {
                    name: '',
                    category: '',
                    quantity: 1,
                    unit: 'pcs',
                    location: '',
                    notes: '',
                },

                get filteredRows() {
                    let list = this.rows;
                    const q = this.search.trim().toLowerCase();
                    if (q) {
                        list = list.filter((item) =>
                            (item.name || '').toLowerCase().includes(q)
                            || (item.category || '').toLowerCase().includes(q)
                            || (item.location || '').toLowerCase().includes(q)
                            || (item.notes || '').toLowerCase().includes(q)
                        );
                    }
                    if (this.categoryFilter) {
                        list = list.filter((item) => (item.category || '') === this.categoryFilter);
                    }
                    return list;
                },

                get categories() {
                    const set = new Set();
                    this.rows.forEach((item) => {
                        if (item.category) set.add(item.category);
                    });
                    return Array.from(set).sort();
                },

                get paginatedRows() {
                    return this.paginate(this.filteredRows, this.page);
                },

                resetForm() {
                    this.form = {
                        name: '',
                        category: '',
                        quantity: 1,
                        unit: 'pcs',
                        location: '',
                        notes: '',
                    };
                },

                openAddForm() {
                    this.editingItem = null;
                    this.resetForm();
                    this.showForm = true;
                    this.$nextTick(() => window.lucide?.createIcons());
                },

                openEditForm(item) {
                    this.editingItem = item;
                    this.form = {
                        name: item.name || '',
                        category: item.category || '',
                        quantity: item.quantity ?? 0,
                        unit: item.unit || 'pcs',
                        location: item.location || '',
                        notes: item.notes || '',
                    };
                    this.openMenu = null;
                    this.activeItem = null;
                    this.showForm = true;
                    this.$nextTick(() => window.lucide?.createIcons());
                },

                confirmDelete(item) {
                    if (!item?.id) return;
                    if (!confirm('Remove this item from inventory?')) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/admin/inventory/' + item.id + '/delete';
                    form.style.display = 'none';
                    document.body.appendChild(form);
                    form.submit();
                },

                closeAddForm() {
                    this.showForm = false;
                    this.editingItem = null;
                    this.resetForm();
                },

                ...pagination,
                ...tableInit('page', 'filteredRows'),

                init() {
                    this.$watch('filteredRows', () => this.clampPage('page', this.filteredRows));
                    this.$watch('search', () => {
                        this.page = 1;
                        this.$nextTick(() => window.lucide?.createIcons());
                    });
                    this.$watch('page', () => this.$nextTick(() => window.lucide?.createIcons()));
                    this.$nextTick(() => window.lucide?.createIcons());

                    this._closeMenuOnScroll = () => {
                        if (this.openMenu) {
                            this.openMenu = null;
                            this.activeItem = null;
                        }
                    };
                    window.addEventListener('scroll', this._closeMenuOnScroll, true);
                },

                toggleMenu(id, event) {
                    if (this.openMenu === id) {
                        this.openMenu = null;
                        this.activeItem = null;
                        return;
                    }
                    this.activeItem = this.rows.find((item) => item.id == id) || null;
                    const btn = event?.currentTarget;
                    if (btn) {
                        const rect = btn.getBoundingClientRect();
                        const width = 168;
                        this.menuPos = {
                            top: rect.bottom + 6,
                            left: Math.min(
                                Math.max(8, rect.right - width),
                                window.innerWidth - width - 8
                            ),
                        };
                    }
                    this.openMenu = id;
                    this.$nextTick(() => window.lucide?.createIcons());
                },
            };
        });

        Alpine.data('staffTable', (rows) => {
            const pagination = paginationMethods('tablePerPage');
            return {
                rows: rows || [],
                page: 1,
                tablePerPage: 10,
                search: '',
                departmentFilter: '',
                statusFilter: '',
                openMenu: null,
                menuPos: { top: 0, left: 0 },
                activePerson: null,
                showForm: false,
                editingPerson: null,
                form: {
                    name: '',
                    role_title: '',
                    department: '',
                    phone: '',
                    email: '',
                    status: 'active',
                    notes: '',
                },

                get filteredRows() {
                    let list = this.rows;
                    const q = this.search.trim().toLowerCase();
                    if (q) {
                        list = list.filter((person) =>
                            (person.name || '').toLowerCase().includes(q)
                            || (person.role_title || '').toLowerCase().includes(q)
                            || (person.department || '').toLowerCase().includes(q)
                            || (person.phone || '').toLowerCase().includes(q)
                            || (person.email || '').toLowerCase().includes(q)
                            || (person.notes || '').toLowerCase().includes(q)
                        );
                    }
                    if (this.departmentFilter) {
                        list = list.filter((person) => (person.department || '') === this.departmentFilter);
                    }
                    if (this.statusFilter) {
                        list = list.filter((person) => (person.status || 'active') === this.statusFilter);
                    }
                    return list;
                },

                get departments() {
                    const set = new Set();
                    this.rows.forEach((person) => {
                        if (person.department) set.add(person.department);
                    });
                    return Array.from(set).sort();
                },

                get paginatedRows() {
                    return this.paginate(this.filteredRows, this.page);
                },

                resetForm() {
                    this.form = {
                        name: '',
                        role_title: '',
                        department: '',
                        phone: '',
                        email: '',
                        status: 'active',
                        notes: '',
                    };
                },

                openAddForm() {
                    this.editingPerson = null;
                    this.resetForm();
                    this.showForm = true;
                    this.$nextTick(() => window.lucide?.createIcons());
                },

                openEditForm(person) {
                    this.editingPerson = person;
                    this.form = {
                        name: person.name || '',
                        role_title: person.role_title || '',
                        department: person.department || '',
                        phone: person.phone || '',
                        email: person.email || '',
                        status: person.status || 'active',
                        notes: person.notes || '',
                    };
                    this.openMenu = null;
                    this.activePerson = null;
                    this.showForm = true;
                    this.$nextTick(() => window.lucide?.createIcons());
                },

                confirmDelete(person) {
                    if (!person?.id) return;
                    if (!confirm('Remove this staff member?')) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/admin/staff/' + person.id + '/delete';
                    form.style.display = 'none';
                    document.body.appendChild(form);
                    form.submit();
                },

                closeAddForm() {
                    this.showForm = false;
                    this.editingPerson = null;
                    this.resetForm();
                },

                statusLabel(status) {
                    if (status === 'on_leave') return 'On leave';
                    if (status === 'inactive') return 'Inactive';
                    return 'Active';
                },

                statusClass(status) {
                    if (status === 'on_leave') return 'admin-status-pill admin-status-pill--reviewed';
                    if (status === 'inactive') return 'admin-status-pill admin-status-pill--archived';
                    return 'admin-status-pill admin-status-pill--new';
                },

                ...pagination,
                ...tableInit('page', 'filteredRows'),

                init() {
                    this.$watch('filteredRows', () => this.clampPage('page', this.filteredRows));
                    this.$watch('search', () => {
                        this.page = 1;
                        this.$nextTick(() => window.lucide?.createIcons());
                    });
                    this.$watch('departmentFilter', () => { this.page = 1; });
                    this.$watch('statusFilter', () => { this.page = 1; });
                    this.$watch('page', () => this.$nextTick(() => window.lucide?.createIcons()));
                    this.$nextTick(() => window.lucide?.createIcons());

                    this._closeMenuOnScroll = () => {
                        if (this.openMenu) {
                            this.openMenu = null;
                            this.activePerson = null;
                        }
                    };
                    window.addEventListener('scroll', this._closeMenuOnScroll, true);
                },

                toggleMenu(id, event) {
                    if (this.openMenu === id) {
                        this.openMenu = null;
                        this.activePerson = null;
                        return;
                    }
                    this.activePerson = this.rows.find((person) => person.id == id) || null;
                    const btn = event?.currentTarget;
                    if (btn) {
                        const rect = btn.getBoundingClientRect();
                        const width = 168;
                        this.menuPos = {
                            top: rect.bottom + 6,
                            left: Math.min(
                                Math.max(8, rect.right - width),
                                window.innerWidth - width - 8
                            ),
                        };
                    }
                    this.openMenu = id;
                    this.$nextTick(() => window.lucide?.createIcons());
                },
            };
        });
    });

    window.addEventListener('scroll', () => {
        document.querySelectorAll('.arrears-dropdown--fixed:not(.arrears-dropdown--teleport)').forEach((el) => {
            el.classList.remove('arrears-dropdown--fixed');
            el.style.top = '';
            el.style.left = '';
        });
    }, true);

    function createPaginatedTable(rows, extra) {
        return {
            rows: rows || [],
            page: 1,
            tablePerPage: 10,

            init() {
                this.$watch('rows', () => this.clampPage('page', this.rows));
                this.$watch('page', () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$nextTick(() => window.lucide?.createIcons());
            },

            get paginatedRows() {
                return this.paginate(this.rows, this.page);
            },

            ...paginationMethods('tablePerPage'),
            ...(extra || {}),
        };
    }
})();
