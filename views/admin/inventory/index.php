<?php
$itemsJson = json_encode(array_values($items ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-pagination.css">
<link rel="stylesheet" href="/css/admin-inventory.css">

<div class="admin-hub-page" x-data="inventoryTable(<?= htmlspecialchars($itemsJson, ENT_QUOTES) ?>)">
    <h2 class="arrears-title">Inventory</h2>
    <p class="finance-tab-hint">Track church equipment, supplies, and assets — open any item for photos and full details.</p>

    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error mb-4"><?= htmlspecialchars(is_string($error) ? $error : 'Something went wrong.') ?></div>
    <?php endif; ?>

    <div class="arrears-toolbar-row">
        <div class="arrears-toolbar-left">
            <input type="search"
                   x-model="search"
                   class="arrears-search"
                   placeholder="Search name, SKU, category, location…"
                   aria-label="Search inventory">
            <span class="arrears-count" x-text="filteredRows.length + ' item' + (filteredRows.length === 1 ? '' : 's')"></span>
            <select x-model="categoryFilter" class="arrears-year-select" aria-label="Filter by category" x-show="categories.length">
                <option value="">All categories</option>
                <template x-for="cat in categories" :key="cat">
                    <option :value="cat" x-text="cat"></option>
                </template>
            </select>
            <select x-model="statusFilter" class="arrears-year-select" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="available">Available</option>
                <option value="in_use">In use</option>
                <option value="reserved">Reserved</option>
                <option value="maintenance">Maintenance</option>
                <option value="retired">Retired</option>
                <option value="missing">Missing</option>
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
                        <th class="hidden md:table-cell">Status</th>
                        <th class="hidden lg:table-cell">Location</th>
                        <th class="ft-th-actions"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="filteredRows.length === 0">
                        <td colspan="6" class="arrears-empty">
                            <span x-show="search.trim() || categoryFilter || statusFilter">No items match your filters.</span>
                            <span x-show="!search.trim() && !categoryFilter && !statusFilter">No inventory items yet. Click <strong>+ Add item</strong> to get started.</span>
                        </td>
                    </tr>
                    <template x-for="item in paginatedRows" :key="item.id">
                        <tr class="arrears-row arrears-row--clickable"
                            role="link"
                            tabindex="0"
                            :aria-label="'Open ' + (item.name || 'item')"
                            @click="openItem(item)"
                            @keydown.enter.prevent="openItem(item)">
                            <td>
                                <div class="inv-list-name">
                                    <template x-if="item.image_url">
                                        <img :src="item.image_url" alt="" class="inv-list-thumb">
                                    </template>
                                    <template x-if="!item.image_url">
                                        <span class="inv-list-thumb inv-list-thumb--empty" aria-hidden="true">
                                            <i data-lucide="package" class="w-4 h-4"></i>
                                        </span>
                                    </template>
                                    <div class="inv-list-name__text">
                                        <span class="arrears-accent font-medium" x-text="item.name"></span>
                                        <span class="block text-xs arrears-muted" x-show="item.sku" x-text="'SKU ' + item.sku"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="arrears-muted hidden sm:table-cell" x-text="item.category || '—'"></td>
                            <td class="ft-td-accent">
                                <span class="arrears-amount" x-text="(item.quantity ?? 0) + ' ' + (item.unit || 'pcs')"></span>
                                <span class="block"
                                      x-show="Number(item.min_quantity) > 0 && Number(item.quantity) <= Number(item.min_quantity)">
                                    <span class="inv-list-pill inv-list-pill--warn">Low stock</span>
                                </span>
                            </td>
                            <td class="hidden md:table-cell">
                                <span class="inv-list-pill" x-text="statusLabel(item.status)"></span>
                            </td>
                            <td class="arrears-muted hidden lg:table-cell" x-text="item.location || '—'"></td>
                            <td class="arrears-actions ft-td-actions"
                                @click.stop
                                :class="openMenu == item.id && 'weekly-actions--open'">
                                <button type="button"
                                        class="arrears-view-btn arrears-view-btn--icon"
                                        @click.stop="toggleMenu(item.id, $event)"
                                        :aria-expanded="openMenu == item.id"
                                        :aria-label="'Actions for ' + item.name"
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
                    <a :href="'/admin/inventory/' + activeItem.id" class="arrears-dropdown-item">Open profile</a>
                    <button type="button"
                            @click="openEditForm(activeItem)"
                            class="arrears-dropdown-item">
                        Quick edit
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

    <div x-show="showForm" x-cloak class="finance-modal-overlay" @keydown.escape.window="closeAddForm()">
        <div class="finance-modal-backdrop" @click="closeAddForm()"></div>
        <div class="finance-modal finance-modal--wide" role="dialog" aria-modal="true" aria-labelledby="inventory-form-title">
            <div class="finance-modal-header">
                <div>
                    <p class="finance-modal-eyebrow">Inventory</p>
                    <h2 id="inventory-form-title" class="finance-modal-title" x-text="editingItem ? 'Quick edit' : 'Add new item'"></h2>
                    <p class="finance-modal-subtitle" x-show="!editingItem">Create the item, then open its profile to add photos and full details.</p>
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
                        <button type="submit" class="finance-btn-primary" x-text="editingItem ? 'Save changes' : 'Create & open profile'"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/js/admin-pagination.js"></script>
