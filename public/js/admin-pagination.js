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
                    el.style.right = '';
                });
            },

            positionActionDropdown(button) {
                if (!button) return;
                const dropdown = button.nextElementSibling;
                if (!dropdown?.classList.contains('arrears-dropdown')) return;

                this.clearFixedDropdowns();
                dropdown.classList.add('arrears-dropdown--fixed');
                const rect = button.getBoundingClientRect();
                const width = 188;
                const margin = 8;
                let right = Math.max(margin, window.innerWidth - rect.right);
                if (rect.right - width < margin) {
                    right = Math.max(margin, window.innerWidth - width - margin);
                }
                dropdown.style.top = `${rect.bottom + 6}px`;
                dropdown.style.right = `${right}px`;
                dropdown.style.left = 'auto';
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

    function registerPaginationAlpine() {
        if (!window.Alpine || window.__kcPaginationAlpineRegistered) return;
        window.__kcPaginationAlpineRegistered = true;

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
                formType: 'join',
                hasSpouse: '',
                hasChildren: '',
                hasDependents: '',
                otherChurch: '',

                isManualMember(m) {
                    return !!(m && (m.is_manual === true || m.is_manual === 1 || m.is_manual === '1' || (m.form_type || '') === 'manual'));
                },

                matchesFormTab(m, key) {
                    if (!key) return true;
                    const type = m.form_type || '';
                    // Legacy rows stored as form_type=manual count toward Members (join).
                    if (key === 'join') {
                        return type === 'join' || type === 'manual';
                    }
                    return type === key;
                },

                formTabCount(key) {
                    return this.rows.filter((m) => this.matchesFormTab(m, key)).length;
                },

                memberFormLabel(m) {
                    return this.formTypeLabel(m?.form_type);
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
                            || this.memberFormLabel(m).toLowerCase().includes(q)
                        );
                    }
                    if (this.statusFilter) {
                        list = list.filter((m) => (m.status || 'new') === this.statusFilter);
                    }
                    if (this.formTypeFilter) {
                        list = list.filter((m) => this.matchesFormTab(m, this.formTypeFilter));
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
                    this.$watch('formType', () => {
                        this.hasSpouse = '';
                        this.hasChildren = '';
                        this.hasDependents = '';
                        this.otherChurch = '';
                    });
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

                openMember(member) {
                    if (!member?.id) return;
                    this.openMenu = null;
                    this.activeMember = null;
                    window.location.href = '/admin/members/' + member.id;
                },

                async confirmDelete(member) {
                    if (!member?.id) return;
                    const ok = await window.AdminDialog?.confirm({
                        title: 'Delete member?',
                        message: 'Delete this member registration? This cannot be undone.',
                        confirmLabel: 'Delete member',
                        tone: 'danger',
                    });
                    if (!ok) return;
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
                    this.formType = 'join';
                    this.hasSpouse = '';
                    this.hasChildren = '';
                    this.hasDependents = '';
                    this.otherChurch = '';
                    this.showForm = true;
                    this.$nextTick(() => {
                        const form = this.$refs.addMemberForm;
                        if (form) {
                            form.reset();
                            const typeSelect = form.querySelector('[name="form_type"]');
                            if (typeSelect) typeSelect.value = 'join';
                            this.formType = 'join';
                        }
                        window.lucide?.createIcons();
                    });
                },

                closeAddForm() {
                    this.showForm = false;
                },

                onAddSubmit() {
                    // Ensure Alpine formType is what gets posted if select was synced.
                    const form = this.$refs.addMemberForm;
                    const typeSelect = form?.querySelector('[name="form_type"]');
                    if (typeSelect && this.formType) typeSelect.value = this.formType;
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
                statusFilter: '',
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
                            || (item.sku || '').toLowerCase().includes(q)
                            || (item.brand || '').toLowerCase().includes(q)
                            || (item.serial_number || '').toLowerCase().includes(q)
                        );
                    }
                    if (this.categoryFilter) {
                        list = list.filter((item) => (item.category || '') === this.categoryFilter);
                    }
                    if (this.statusFilter) {
                        list = list.filter((item) => (item.status || 'available') === this.statusFilter);
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

                statusLabel(status) {
                    const map = {
                        available: 'Available',
                        in_use: 'In use',
                        reserved: 'Reserved',
                        maintenance: 'Maintenance',
                        retired: 'Retired',
                        missing: 'Missing',
                    };
                    const key = String(status || 'available');
                    return map[key] || key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
                },

                openItem(item) {
                    if (!item?.id) return;
                    this.openMenu = null;
                    this.activeItem = null;
                    window.location.href = '/admin/inventory/' + item.id;
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

                async confirmDelete(item) {
                    if (!item?.id) return;
                    const ok = await window.AdminDialog?.confirm({
                        title: 'Remove inventory item?',
                        message: 'Remove this item from inventory? Photos will also be deleted.',
                        confirmLabel: 'Remove item',
                        tone: 'danger',
                    });
                    if (!ok) return;
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
                    this.$watch('categoryFilter', () => { this.page = 1; });
                    this.$watch('statusFilter', () => { this.page = 1; });
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

                initials(name) {
                    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
                    return parts.slice(0, 2).map((p) => p.charAt(0).toUpperCase()).join('') || '?';
                },

                openPerson(person) {
                    if (!person?.id) return;
                    this.openMenu = null;
                    this.activePerson = null;
                    window.location.href = '/admin/staff/' + person.id;
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

                async confirmDelete(person) {
                    if (!person?.id) return;
                    const ok = await window.AdminDialog?.confirm({
                        title: 'Remove staff member?',
                        message: 'Remove this staff member? Their profile photos will also be deleted.',
                        confirmLabel: 'Remove staff',
                        tone: 'danger',
                    });
                    if (!ok) return;
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

    }

    document.addEventListener('alpine:init', registerPaginationAlpine);
    if (window.Alpine) {
        registerPaginationAlpine();
    }

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
