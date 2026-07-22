<?php
$itemsJson = json_encode(array_values($items ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-pagination.css">

<div class="admin-hub-page" x-data="inventoryTable(<?= htmlspecialchars($itemsJson, ENT_QUOTES) ?>)">
    <h2 class="arrears-title">Inventory</h2>
    <p class="finance-tab-hint">Track church equipment, supplies, and assets.</p>

    <div class="arrears-toolbar-row">
        <div class="arrears-toolbar-left">
            <input type="search"
                   x-model="search"
                   class="arrears-search"
                   placeholder="Search items, category, location…"
                   aria-label="Search inventory">
            <span class="arrears-count" x-text="filteredRows.length + ' item' + (filteredRows.length === 1 ? '' : 's')"></span>
            <select x-model="categoryFilter" class="arrears-year-select" aria-label="Filter by category" x-show="categories.length">
                <option value="">All categories</option>
                <template x-for="cat in categories" :key="cat">
                    <option :value="cat" x-text="cat"></option>
                </template>
            </select>
        </div>
        <button type="button" @click="openAddForm()" class="arrears-btn-new">+ Add item</button>
    </div>

    <div class="arrears-card finance-table-card">
        <div class="finance-table-caption">
            <span class="finance-table-caption-label">Inventory items</span>
            <span class="finance-table-caption-badge" x-text="rows.length + ' total'"></span>
            <span class="finance-table-caption-scroll-hint" aria-hidden="true">Swipe →</span>
        </div>
        <div class="arrears-table-scroll" tabindex="0" role="region" aria-label="Inventory — scroll horizontally on small screens">
            <table class="arrears-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="hidden sm:table-cell">Category</th>
                        <th class="ft-th-accent ft-th--right">Quantity</th>
                        <th class="hidden md:table-cell">Location</th>
                        <th class="hidden lg:table-cell">Notes</th>
                        <th class="ft-th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="filteredRows.length === 0">
                        <td colspan="6" class="arrears-empty">
                            <span x-show="search.trim() || categoryFilter">No items match your filters.</span>
                            <span x-show="!search.trim() && !categoryFilter">No inventory items yet. Click <strong>+ Add item</strong> to get started.</span>
                        </td>
                    </tr>
                    <template x-for="item in paginatedRows" :key="item.id">
                        <tr class="arrears-row">
                            <td>
                                <span class="arrears-accent font-medium" x-text="item.name"></span>
                            </td>
                            <td class="arrears-muted hidden sm:table-cell" x-text="item.category || '—'"></td>
                            <td class="ft-td-accent">
                                <span class="arrears-amount" x-text="(item.quantity ?? 0) + ' ' + (item.unit || 'pcs')"></span>
                            </td>
                            <td class="arrears-muted hidden md:table-cell" x-text="item.location || '—'"></td>
                            <td class="arrears-muted hidden lg:table-cell text-xs" x-text="item.notes || '—'"></td>
                            <td class="arrears-actions ft-td-actions"
                                :class="openMenu == item.id && 'weekly-actions--open'">
                                <button type="button"
                                        class="arrears-view-btn"
                                        @click.stop="toggleMenu(item.id, $event)"
                                        :aria-expanded="openMenu == item.id"
                                        :aria-label="'Actions for ' + item.name">
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
        $itemLabel = 'items';
        $navLabel = 'Inventory pages';
        require __DIR__ . '/../partials/table-pagination.php';
        ?>
    </div>

    <template x-teleport="body">
        <div x-show="openMenu"
             x-cloak
             @click.outside="openMenu = null; activeItem = null"
             @keydown.escape.window="openMenu = null; activeItem = null"
             class="arrears-dropdown arrears-dropdown--fixed arrears-dropdown--teleport"
             :style="'top:' + menuPos.top + 'px;left:' + menuPos.left + 'px'">
            <template x-if="activeItem">
                <div>
                    <button type="button"
                            @click="openEditForm(activeItem)"
                            class="arrears-dropdown-item">
                        Edit item
                    </button>
                    <button type="button"
                            @click="confirmDelete(activeItem)"
                            class="arrears-dropdown-item arrears-dropdown-item--danger">
                        Delete item
                    </button>
                </div>
            </template>
        </div>
    </template>

    <!-- Add / edit item modal -->
    <div x-show="showForm" x-cloak class="finance-modal-overlay" @keydown.escape.window="closeAddForm()">
        <div class="finance-modal-backdrop" @click="closeAddForm()"></div>
        <div class="finance-modal finance-modal--wide" role="dialog" aria-modal="true" aria-labelledby="inventory-form-title">
            <div class="finance-modal-header">
                <div>
                    <p class="finance-modal-eyebrow">Inventory</p>
                    <h2 id="inventory-form-title" class="finance-modal-title" x-text="editingItem ? 'Edit item' : 'Add new item'"></h2>
                </div>
                <button type="button" class="finance-modal-close" @click="closeAddForm()" aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="post" :action="editingItem ? '/admin/inventory/' + editingItem.id : '/admin/inventory'">
                <div class="finance-modal-body finance-modal-body--grid">
                    <div class="finance-field">
                        <label class="finance-label" for="inv-name">Item name</label>
                        <input type="text" id="inv-name" name="name" required class="finance-input" placeholder="e.g. Wireless microphone" x-model="form.name">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-category">Category</label>
                        <input type="text" id="inv-category" name="category" class="finance-input" placeholder="e.g. Sound, Furniture" x-model="form.category">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-quantity">Quantity</label>
                        <input type="number" id="inv-quantity" name="quantity" min="0" class="finance-input" x-model.number="form.quantity">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-unit">Unit</label>
                        <input type="text" id="inv-unit" name="unit" class="finance-input" x-model="form.unit">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-location">Location</label>
                        <input type="text" id="inv-location" name="location" class="finance-input" placeholder="e.g. Main auditorium" x-model="form.location">
                    </div>
                    <div class="finance-field" style="grid-column: 1 / -1;">
                        <label class="finance-label" for="inv-notes">Notes</label>
                        <textarea id="inv-notes" name="notes" rows="2" class="finance-input finance-textarea" placeholder="Optional details…" x-model="form.notes"></textarea>
                    </div>
                </div>
                <div class="finance-modal-footer">
                    <div class="finance-modal-actions">
                        <button type="button" class="finance-btn-secondary" @click="closeAddForm()">Cancel</button>
                        <button type="submit" class="finance-btn-primary" x-text="editingItem ? 'Save changes' : 'Save item'"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/js/admin-pagination.js"></script>
