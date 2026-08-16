<?php
$staffJson = json_encode(array_values($staff ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-pagination.css">

<div class="admin-hub-page" x-data="staffTable(<?= htmlspecialchars($staffJson, ENT_QUOTES) ?>)">
    <h2 class="arrears-title">Staff</h2>
    <p class="finance-tab-hint">Manage church staff, roles, and contact details.</p>

    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error mb-4"><?= htmlspecialchars((string) $error) ?></div>
    <?php endif; ?>

    <div class="arrears-toolbar-row">
        <div class="arrears-toolbar-left">
            <input type="search"
                   x-model="search"
                   class="arrears-search"
                   placeholder="Search name, role, department…"
                   aria-label="Search staff">
            <span class="arrears-count" x-text="filteredRows.length + ' staff'"></span>
            <select x-model="departmentFilter" class="arrears-year-select" aria-label="Filter by department" x-show="departments.length">
                <option value="">All departments</option>
                <template x-for="dept in departments" :key="dept">
                    <option :value="dept" x-text="dept"></option>
                </template>
            </select>
            <select x-model="statusFilter" class="arrears-year-select" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="on_leave">On leave</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <button type="button" @click="openAddForm()" class="arrears-btn-new">+ Add staff</button>
    </div>

    <div class="arrears-card finance-table-card">
        <div class="finance-table-caption">
            <span class="finance-table-caption-label">Staff directory</span>
            <span class="finance-table-caption-badge" x-text="rows.length + ' total'"></span>
            <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
        </div>
        <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Staff — scroll horizontally on small screens">
            <table class="arrears-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="hidden sm:table-cell">Role</th>
                        <th class="hidden md:table-cell">Department</th>
                        <th>Phone</th>
                        <th class="hidden lg:table-cell">Email</th>
                        <th class="ft-th-accent">Status</th>
                        <th class="ft-th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="filteredRows.length === 0">
                        <td colspan="7" class="arrears-empty">
                            <span x-show="search.trim() || departmentFilter || statusFilter">No staff match your filters.</span>
                            <span x-show="!search.trim() && !departmentFilter && !statusFilter">No staff yet. Click <strong>+ Add staff</strong> to get started.</span>
                        </td>
                    </tr>
                    <template x-for="person in paginatedRows" :key="person.id">
                        <tr class="arrears-row">
                            <td>
                                <span class="arrears-accent font-medium" x-text="person.name"></span>
                            </td>
                            <td class="arrears-muted hidden sm:table-cell" x-text="person.role_title || '—'"></td>
                            <td class="arrears-muted hidden md:table-cell" x-text="person.department || '—'"></td>
                            <td class="arrears-muted" x-text="person.phone || '—'"></td>
                            <td class="arrears-muted hidden lg:table-cell" x-text="person.email || '—'"></td>
                            <td class="ft-td-accent">
                                <span :class="statusClass(person.status)" x-text="statusLabel(person.status)"></span>
                            </td>
                            <td class="arrears-actions ft-td-actions"
                                :class="openMenu == person.id && 'weekly-actions--open'">
                                <button type="button"
                                        class="arrears-view-btn"
                                        @click.stop="toggleMenu(person.id, $event)"
                                        :aria-expanded="openMenu == person.id"
                                        :aria-label="'Actions for ' + person.name">
                                    Actions
                                    <i data-lucide="chevron-down"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <?php
        $pageKey = 'page';
        $listKey = 'filteredRows';
        $itemLabel = 'staff';
        $navLabel = 'Staff pages';
        require __DIR__ . '/../partials/table-pagination.php';
        ?>
    </div>

    <template x-teleport="body">
        <div x-show="openMenu"
             x-cloak
             @click.outside="openMenu = null; activePerson = null"
             @keydown.escape.window="openMenu = null; activePerson = null"
             class="arrears-dropdown arrears-dropdown--fixed arrears-dropdown--teleport"
             :style="'top:' + menuPos.top + 'px;left:' + menuPos.left + 'px'">
            <template x-if="activePerson">
                <div>
                    <button type="button"
                            @click="openEditForm(activePerson)"
                            class="arrears-dropdown-item">
                        Edit staff
                    </button>
                    <button type="button"
                            @click="confirmDelete(activePerson)"
                            class="arrears-dropdown-item arrears-dropdown-item--danger">
                        Delete staff
                    </button>
                </div>
            </template>
        </div>
    </template>

    <div x-show="showForm" x-cloak class="finance-modal-overlay" @keydown.escape.window="closeAddForm()">
        <div class="finance-modal-backdrop" @click="closeAddForm()"></div>
        <div class="finance-modal finance-modal--wide" role="dialog" aria-modal="true" aria-labelledby="staff-form-title">
            <div class="finance-modal-header">
                <div>
                    <p class="finance-modal-eyebrow">Staff</p>
                    <h2 id="staff-form-title" class="finance-modal-title" x-text="editingPerson ? 'Edit staff' : 'Add staff'"></h2>
                </div>
                <button type="button" class="finance-modal-close" @click="closeAddForm()" aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="post" :action="editingPerson ? '/admin/staff/' + editingPerson.id : '/admin/staff'">
                <div class="finance-modal-body finance-modal-body--grid">
                    <div class="finance-field" style="grid-column: 1 / -1;">
                        <label class="finance-label" for="staff-name">Full name</label>
                        <input type="text" id="staff-name" name="name" required class="finance-input" placeholder="e.g. Pastor Jane Wanjiku" x-model="form.name">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-role">Role / title</label>
                        <input type="text" id="staff-role" name="role_title" class="finance-input" placeholder="e.g. Worship leader" x-model="form.role_title">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-department">Department</label>
                        <input type="text" id="staff-department" name="department" class="finance-input" placeholder="e.g. Media, Administration" x-model="form.department">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-phone">Phone</label>
                        <input type="tel" id="staff-phone" name="phone" class="finance-input" placeholder="+254…" x-model="form.phone">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-email">Email</label>
                        <input type="email" id="staff-email" name="email" class="finance-input" placeholder="name@example.com" x-model="form.email">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-status">Status</label>
                        <select id="staff-status" name="status" class="finance-input" x-model="form.status">
                            <option value="active">Active</option>
                            <option value="on_leave">On leave</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="finance-field" style="grid-column: 1 / -1;">
                        <label class="finance-label" for="staff-notes">Notes</label>
                        <textarea id="staff-notes" name="notes" rows="2" class="finance-input finance-textarea" placeholder="Optional notes…" x-model="form.notes"></textarea>
                    </div>
                </div>
                <div class="finance-modal-footer">
                    <div class="finance-modal-actions">
                        <button type="button" class="finance-btn-secondary" @click="closeAddForm()">Cancel</button>
                        <button type="submit" class="finance-btn-primary" x-text="editingPerson ? 'Save changes' : 'Save staff'"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/js/admin-pagination.js"></script>
