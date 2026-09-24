<?php
$membersJson = json_encode(array_values($members ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$formTypeLabelsJson = json_encode($formTypeLabels ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$formsDbStatus = $formsDbStatus ?? [];
?>
<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-pagination.css">

<div class="admin-hub-page" x-data="memberTable(<?= htmlspecialchars($membersJson, ENT_QUOTES) ?>, <?= htmlspecialchars($formTypeLabelsJson, ENT_QUOTES) ?>)">
    <h2 class="arrears-title">Members</h2>
    <p class="finance-tab-hint">Connect submissions!</p>

    <?php if (!empty($success)): ?>
    <div class="admin-alert admin-alert--success mb-4">Member added successfully.</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error mb-4"><?= htmlspecialchars(is_string($error) ? $error : 'Could not add member.') ?></div>
    <?php endif; ?>

    <?php if (!empty($formsDbStatus['warning'])): ?>
    <div class="admin-alert admin-alert--error mb-4"><?= htmlspecialchars($formsDbStatus['warning']) ?></div>
    <?php elseif (!empty($formsDbStatus['error'])): ?>
    <div class="admin-alert admin-alert--error mb-4">Forms database connection failed: <?= htmlspecialchars($formsDbStatus['error']) ?></div>
    <?php endif; ?>

    <div class="members-form-tabs fin-subtabs" role="tablist" aria-label="Filter by form">
        <button type="button"
                class="fin-subtabs__item"
                :class="!formTypeFilter && 'fin-subtabs__item--active'"
                role="tab"
                :aria-selected="!formTypeFilter"
                @click="formTypeFilter = ''">
            All
            <span class="members-form-tabs__count" x-text="formTabCount('')"></span>
        </button>
        <template x-for="(label, key) in formTypeLabels" :key="key">
            <button type="button"
                    class="fin-subtabs__item"
                    :class="formTypeFilter === key && 'fin-subtabs__item--active'"
                    role="tab"
                    :aria-selected="formTypeFilter === key"
                    @click="formTypeFilter = key">
                <span x-text="label"></span>
                <span class="members-form-tabs__count" x-text="formTabCount(key)"></span>
            </button>
        </template>
    </div>

    <div class="arrears-toolbar-row">
        <div class="arrears-toolbar-left">
            <input type="search"
                   x-model="search"
                   class="arrears-search"
                   placeholder="Search name, phone, email…"
                   aria-label="Search members">
            <span class="arrears-count" x-text="filteredRows.length + (filteredRows.length === 1 ? ' member' : ' members')"></span>
            <select x-model="statusFilter" class="arrears-year-select" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="new">New</option>
                <option value="reviewed">Reviewed</option>
                <option value="archived">Archived</option>
            </select>
        </div>
        <button type="button" @click="openAddForm()" class="arrears-btn-new">+ Add member</button>
    </div>

    <div class="arrears-card finance-table-card">
            <div class="finance-table-caption">
                <span class="finance-table-caption-label">Member registrations</span>
                <span class="finance-table-caption-badge">Connect forms</span>
                <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
            </div>
            <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Members — scroll horizontally on small screens">
            <table class="arrears-table">
                <thead>
                    <tr>
                        <th>Full name</th>
                        <th>Phone</th>
                        <th class="hidden md:table-cell">Email</th>
                        <th class="hidden lg:table-cell">Campus</th>
                        <th class="hidden sm:table-cell">Registered</th>
                        <th class="ft-th-actions"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="filteredRows.length === 0">
                        <td colspan="6" class="arrears-empty">
                            <span x-show="search.trim() || statusFilter || formTypeFilter">No members match your filters.</span>
                            <span x-show="!search.trim() && !statusFilter && !formTypeFilter">No members yet. Click <strong>+ Add member</strong> or wait for Connect With Us form submissions.</span>
                        </td>
                    </tr>
                    <template x-for="m in paginatedRows" :key="m.id">
                        <tr class="arrears-row arrears-row--clickable"
                            role="link"
                            tabindex="0"
                            :aria-label="'Open profile for ' + (m.submitter_name || 'member')"
                            @click="openMember(m)"
                            @keydown.enter.prevent="openMember(m)">
                            <td>
                                <span class="arrears-accent font-medium" x-text="m.submitter_name || '—'"></span>
                            </td>
                            <td class="arrears-muted" x-text="m.submitter_phone || '—'"></td>
                            <td class="arrears-muted hidden md:table-cell" x-text="m.submitter_email || '—'"></td>
                            <td class="arrears-muted hidden lg:table-cell capitalize" x-text="m.campus_id || '—'"></td>
                            <td class="arrears-muted hidden sm:table-cell" x-text="formatMemberDate(m.created_at)"></td>
                            <td class="arrears-actions ft-td-actions"
                                @click.stop
                                :class="openMenu === m.id && 'weekly-actions--open'">
                                <button type="button"
                                        class="arrears-view-btn arrears-view-btn--icon"
                                        @click.stop="toggleMenu(m.id, $event)"
                                        :aria-expanded="openMenu === m.id"
                                        :aria-label="'Actions for ' + (m.submitter_name || 'member')"
                                        title="Actions">
                                    <i data-lucide="ellipsis-vertical"></i>
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
        $itemLabel = 'members';
        $navLabel = 'Members pages';
        require __DIR__ . '/../partials/table-pagination.php';
        ?>
    </div>

    <template x-teleport="body">
        <div x-show="openMenu"
             x-cloak
             @click.outside="openMenu = null; activeMember = null"
             @keydown.escape.window="openMenu = null; activeMember = null"
             class="arrears-dropdown arrears-dropdown--fixed arrears-dropdown--teleport"
             :style="'top:' + menuPos.top + 'px;left:' + menuPos.left + 'px'">
            <template x-if="activeMember">
                <div>
                    <a :href="'/admin/members/' + activeMember.id" class="arrears-dropdown-item">View profile</a>
                    <a :href="'/admin/communications?member=' + activeMember.id" class="arrears-dropdown-item">Send message</a>
                    <button type="button"
                            @click="confirmDelete(activeMember)"
                            class="arrears-dropdown-item arrears-dropdown-item--danger">
                        Delete member
                    </button>
                </div>
            </template>
        </div>
    </template>

    <div x-show="showForm" x-cloak class="finance-modal-overlay" @keydown.escape.window="closeAddForm()">
        <div class="finance-modal-backdrop" @click="closeAddForm()"></div>
        <div class="finance-modal finance-modal--member" role="dialog" aria-modal="true" aria-labelledby="member-form-title">
            <div class="finance-modal-header">
                <div>
                    <p class="finance-modal-eyebrow">Members</p>
                    <h2 id="member-form-title" class="finance-modal-title">Add member</h2>
                    <p class="finance-modal-subtitle">Capture a Connect registration — Members (Join us), Visitors, New beginnings, or Kingdom groups.</p>
                </div>
                <button type="button" class="finance-modal-close" @click="closeAddForm()" aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="post" action="/admin/members" x-ref="addMemberForm" @submit="onAddSubmit">
                <div class="finance-modal-body finance-modal-body--grid finance-modal-body--member">
                    <?php require __DIR__ . '/_add-form-fields.php'; ?>
                </div>
                <div class="finance-modal-footer">
                    <div class="finance-modal-actions">
                        <button type="button" class="finance-btn-secondary" @click="closeAddForm()">Cancel</button>
                        <button type="submit" class="finance-btn-primary">Save member</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/js/admin-pagination.js"></script>
