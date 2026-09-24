<?php

use App\Services\InventoryService;

$item = $item ?? [];
$images = $images ?? [];
$statuses = $statuses ?? InventoryService::STATUSES;
$conditions = $conditions ?? InventoryService::CONDITIONS;

$id = (int) ($item['id'] ?? 0);
$name = (string) ($item['name'] ?? 'Untitled item');
$category = trim((string) ($item['category'] ?? ''));
$quantity = (int) ($item['quantity'] ?? 0);
$unit = trim((string) ($item['unit'] ?? 'pcs')) ?: 'pcs';
$minQty = (int) ($item['min_quantity'] ?? 0);
$status = (string) ($item['status'] ?? 'available');
$condition = (string) ($item['condition_status'] ?? 'good');
$primaryUrl = InventoryService::imageUrl($item['primary_image_path'] ?? null);
if (!$primaryUrl && $images !== []) {
    $primaryUrl = InventoryService::imageUrl($images[0]['path'] ?? null);
}

$lowStock = $minQty > 0 && $quantity <= $minQty;
$statusClass = match ($status) {
    'available' => 'inv-status--available',
    'in_use' => 'inv-status--in-use',
    'reserved' => 'inv-status--reserved',
    'maintenance' => 'inv-status--maintenance',
    'retired' => 'inv-status--retired',
    'missing' => 'inv-status--missing',
    default => 'inv-status--default',
};

$fmtMoney = static function ($n): string {
    if ($n === null || $n === '') {
        return '—';
    }

    return 'KES ' . number_format((float) $n, 0);
};
$fmtDate = static function (?string $d): string {
    if (!$d) {
        return '—';
    }
    $ts = strtotime($d);

    return $ts ? date('M j, Y', $ts) : '—';
};

$warrantyExpired = !empty($item['warranty_expires']) && strtotime((string) $item['warranty_expires']) < time();
?>
<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-inventory.css">

<div class="inv-profile-page" x-data="{ galleryOpen: false, gallerySrc: '', tab: <?= !empty($_GET['photo']) ? "'photos'" : "'overview'" ?> }">
    <a href="/admin/inventory" class="member-profile-back">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to inventory
    </a>

    <?php if (!empty($success)): ?>
    <div class="admin-alert admin-alert--success mb-4">
        <?= !empty($_GET['photo']) ? 'Photos updated.' : (!empty($_GET['added']) ? 'Item created. Fill in the full details below.' : 'Item details saved.') ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error mb-4"><?= htmlspecialchars(is_string($error) ? $error : 'Something went wrong.') ?></div>
    <?php endif; ?>

    <div class="inv-profile-hero">
        <div class="inv-profile-hero__media">
            <?php if ($primaryUrl): ?>
            <button type="button"
                    class="inv-profile-hero__photo"
                    @click="galleryOpen = true; gallerySrc = '<?= htmlspecialchars($primaryUrl, ENT_QUOTES) ?>'"
                    aria-label="View primary photo">
                <img src="<?= htmlspecialchars($primaryUrl) ?>" alt="<?= htmlspecialchars($name) ?>">
            </button>
            <?php else: ?>
            <div class="inv-profile-hero__photo inv-profile-hero__photo--empty" aria-hidden="true">
                <i data-lucide="package" class="w-10 h-10"></i>
            </div>
            <?php endif; ?>
        </div>
        <div class="inv-profile-hero__body">
            <div class="inv-profile-hero__badges">
                <span class="inv-status <?= $statusClass ?>"><?= htmlspecialchars(InventoryService::statusLabel($status)) ?></span>
                <span class="inv-condition"><?= htmlspecialchars(InventoryService::conditionLabel($condition)) ?></span>
                <?php if ($lowStock): ?>
                <span class="inv-stock-warn">Low stock</span>
                <?php endif; ?>
                <?php if ($warrantyExpired): ?>
                <span class="inv-stock-warn inv-stock-warn--muted">Warranty expired</span>
                <?php endif; ?>
            </div>
            <h1 class="inv-profile-hero__title"><?= htmlspecialchars($name) ?></h1>
            <p class="inv-profile-hero__sub">
                <?= $category !== '' ? htmlspecialchars($category) : 'Uncategorized' ?>
                · Item #<?= $id ?>
            </p>
            <div class="member-profile-chips inv-profile-chips">
                <span class="member-profile-chip">
                    <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                    <?= (int) $quantity ?> <?= htmlspecialchars($unit) ?>
                    <?php if ($minQty > 0): ?>
                    <span class="inv-chip-muted">(min <?= $minQty ?>)</span>
                    <?php endif; ?>
                </span>
                <?php if (!empty($item['location'])): ?>
                <span class="member-profile-chip">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                    <?= htmlspecialchars((string) $item['location']) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($item['campus'])): ?>
                <span class="member-profile-chip">
                    <i data-lucide="church" class="w-3.5 h-3.5"></i>
                    <?= htmlspecialchars(ucfirst((string) $item['campus'])) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($item['assigned_to'])): ?>
                <span class="member-profile-chip">
                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                    <?= htmlspecialchars((string) $item['assigned_to']) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($item['sku'])): ?>
                <span class="member-profile-chip">
                    <i data-lucide="hash" class="w-3.5 h-3.5"></i>
                    SKU <?= htmlspecialchars((string) $item['sku']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="inv-profile-body">
        <nav class="admin-profile-tabs" role="tablist" aria-label="Inventory item sections">
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'overview' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'overview'"
                    @click="tab = 'overview'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="layout-dashboard"></i>
                Overview
            </button>
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'photos' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'photos'"
                    @click="tab = 'photos'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="images"></i>
                Photos
            </button>
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'details' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'details'"
                    @click="tab = 'details'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="clipboard-list"></i>
                Details
            </button>
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'manage' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'manage'"
                    @click="tab = 'manage'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="settings"></i>
                Manage
            </button>
        </nav>

        <div class="admin-profile-tabs__panel inv-profile-panel-stack" x-show="tab === 'overview'" role="tabpanel">
            <div class="admin-profile-overview-grid">
<div class="member-profile-card member-profile-card--muted">
                <div class="member-profile-card-header">
                    <h2>At a glance</h2>
                </div>
                <dl class="member-profile-meta-list">
                    <div>
                        <dt>Quantity</dt>
                        <dd><?= $quantity ?> <?= htmlspecialchars($unit) ?></dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd><?= htmlspecialchars(InventoryService::statusLabel($status)) ?></dd>
                    </div>
                    <div>
                        <dt>Condition</dt>
                        <dd><?= htmlspecialchars(InventoryService::conditionLabel($condition)) ?></dd>
                    </div>
                    <div>
                        <dt>Purchase price</dt>
                        <dd><?= $fmtMoney($item['purchase_price'] ?? null) ?></dd>
                    </div>
                    <div>
                        <dt>Current value</dt>
                        <dd><?= $fmtMoney($item['current_value'] ?? null) ?></dd>
                    </div>
                    <div>
                        <dt>Purchased</dt>
                        <dd><?= $fmtDate($item['purchase_date'] ?? null) ?></dd>
                    </div>
                    <div>
                        <dt>Warranty</dt>
                        <dd><?= $fmtDate($item['warranty_expires'] ?? null) ?></dd>
                    </div>
                    <div>
                        <dt>Last checked</dt>
                        <dd><?= $fmtDate($item['last_checked_at'] ?? null) ?></dd>
                    </div>
                    <div>
                        <dt>Photos</dt>
                        <dd><?= count($images) ?></dd>
                    </div>
                </dl>
            </div>
<div class="member-profile-card member-profile-card--muted">
                <div class="member-profile-card-header">
                    <h2>Record info</h2>
                </div>
                <dl class="member-profile-meta-list">
                    <div>
                        <dt>Item ID</dt>
                        <dd>#<?= $id ?></dd>
                    </div>
                    <div>
                        <dt>Created</dt>
                        <dd><?= !empty($item['created_at']) ? date('F j, Y g:i A', strtotime((string) $item['created_at'])) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Last updated</dt>
                        <dd><?= !empty($item['updated_at']) ? date('F j, Y g:i A', strtotime((string) $item['updated_at'])) : '—' ?></dd>
                    </div>
                </dl>
            </div>
            </div>
        </div>

        <div class="admin-profile-tabs__panel" x-show="tab === 'photos'" x-cloak role="tabpanel">
            <section class="member-profile-card inv-card">
                <div class="member-profile-card-header">
                    <h2>Photos</h2>
                    <p>Upload clear photos of the item from multiple angles. JPG, PNG, WebP or GIF · max 5 MB each.</p>
                </div>

                <?php if ($images !== []): ?>
                <div class="inv-gallery">
                    <?php foreach ($images as $image): ?>
                    <?php
                        $imgUrl = InventoryService::imageUrl($image['path'] ?? null);
                        if (!$imgUrl) {
                            continue;
                        }
                        $isPrimary = (int) ($image['is_primary'] ?? 0) === 1;
                    ?>
                    <figure class="inv-gallery__item <?= $isPrimary ? 'inv-gallery__item--primary' : '' ?>">
                        <button type="button"
                                class="inv-gallery__thumb"
                                @click="galleryOpen = true; gallerySrc = '<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>'">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars((string) ($image['caption'] ?? $name)) ?>">
                        </button>
                        <?php if ($isPrimary): ?>
                        <span class="inv-gallery__badge">Primary</span>
                        <?php endif; ?>
                        <?php if (!empty($image['caption'])): ?>
                        <figcaption><?= htmlspecialchars((string) $image['caption']) ?></figcaption>
                        <?php endif; ?>
                        <div class="inv-gallery__actions">
                            <?php if (!$isPrimary): ?>
                            <form method="post" action="/admin/inventory/<?= $id ?>/images/<?= (int) $image['id'] ?>/primary">
                                <button type="submit" class="inv-gallery__btn" title="Set as primary">Set primary</button>
                            </form>
                            <?php endif; ?>
                            <form method="post"
                                  action="/admin/inventory/<?= $id ?>/images/<?= (int) $image['id'] ?>/delete"
                                  data-confirm="Remove this photo from the inventory item?"
                                  data-confirm-title="Remove photo?"
                                  data-confirm-label="Remove photo"
                                  data-confirm-tone="danger">
                                <button type="submit" class="inv-gallery__btn inv-gallery__btn--danger" title="Delete photo">Delete</button>
                            </form>
                        </div>
                    </figure>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php
                $uploadAction = '/admin/inventory/' . $id . '/images';
                $inputId = 'inv-photos';
                $captionId = 'inv-photo-caption';
                $captionPlaceholder = 'e.g. Front view, serial plate, packaging…';
                $solo = $images === [];
                require __DIR__ . '/../partials/photo-dropzone.php';
                ?>
            </section>
        </div>

        <div class="admin-profile-tabs__panel" x-show="tab === 'details'" x-cloak role="tabpanel">
        <!-- Full details form -->
        <form method="post" action="/admin/inventory/<?= $id ?>" class="member-profile-card inv-card admin-profile-form">
            <div class="member-profile-card-header">
                <h2>Item details</h2>
                <p>Keep every field current so the team can find and track this asset.</p>
            </div>

            <div class="inv-form-section">
                <h3 class="inv-form-section__title"><i data-lucide="package"></i> Basics</h3>
                <div class="inv-form-grid">
                    <div class="finance-field inv-field--full">
                        <label class="finance-label" for="inv-name">Item name <span class="finance-req">*</span></label>
                        <input type="text" id="inv-name" name="name" required class="finance-input"
                               value="<?= htmlspecialchars($name) ?>">
                    </div>
                    <div class="finance-field inv-field--full">
                        <label class="finance-label" for="inv-description">Description</label>
                        <textarea id="inv-description" name="description" rows="3" class="finance-input finance-textarea"
                                  placeholder="What it is, how it is used, special handling…"><?= htmlspecialchars((string) ($item['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-category">Category</label>
                        <input type="text" id="inv-category" name="category" class="finance-input"
                               list="inv-category-suggestions"
                               placeholder="e.g. Sound, Furniture, AV"
                               value="<?= htmlspecialchars($category) ?>">
                        <datalist id="inv-category-suggestions">
                            <option value="Sound"></option>
                            <option value="AV / Media"></option>
                            <option value="Furniture"></option>
                            <option value="Instruments"></option>
                            <option value="IT / Computers"></option>
                            <option value="Kitchen"></option>
                            <option value="Children / Kids"></option>
                            <option value="Vehicles"></option>
                            <option value="Office"></option>
                            <option value="Worship"></option>
                            <option value="Maintenance"></option>
                        </datalist>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-department">Department / ministry</label>
                        <input type="text" id="inv-department" name="department" class="finance-input"
                               placeholder="e.g. Production, Hospitality"
                               value="<?= htmlspecialchars((string) ($item['department'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="inv-form-section">
                <h3 class="inv-form-section__title"><i data-lucide="map-pin"></i> Stock & location</h3>
                <div class="inv-form-grid">
                    <div class="finance-field">
                        <label class="finance-label" for="inv-quantity">Quantity on hand</label>
                        <input type="number" id="inv-quantity" name="quantity" min="0" class="finance-input"
                               value="<?= $quantity ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-unit">Unit</label>
                        <input type="text" id="inv-unit" name="unit" class="finance-input"
                               placeholder="pcs, sets, boxes…"
                               value="<?= htmlspecialchars($unit) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-min-quantity">Reorder / minimum qty</label>
                        <input type="number" id="inv-min-quantity" name="min_quantity" min="0" class="finance-input"
                               value="<?= $minQty ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-location">Storage location</label>
                        <input type="text" id="inv-location" name="location" class="finance-input"
                               placeholder="e.g. Stage store, Sacristy shelf B"
                               value="<?= htmlspecialchars((string) ($item['location'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-campus">Campus</label>
                        <select id="inv-campus" name="campus" class="finance-input">
                            <option value="">—</option>
                            <?php foreach (['nanyuki' => 'Nanyuki', 'nairobi' => 'Nairobi'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= (($item['campus'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-assigned">Assigned to</label>
                        <input type="text" id="inv-assigned" name="assigned_to" class="finance-input"
                               placeholder="Person or team responsible"
                               value="<?= htmlspecialchars((string) ($item['assigned_to'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="inv-form-section">
                <h3 class="inv-form-section__title"><i data-lucide="hash"></i> Identity & specs</h3>
                <div class="inv-form-grid">
                    <div class="finance-field">
                        <label class="finance-label" for="inv-sku">SKU / asset tag</label>
                        <input type="text" id="inv-sku" name="sku" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['sku'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-barcode">Barcode / QR code</label>
                        <input type="text" id="inv-barcode" name="barcode" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['barcode'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-brand">Brand / manufacturer</label>
                        <input type="text" id="inv-brand" name="brand" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['brand'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-model">Model number</label>
                        <input type="text" id="inv-model" name="model_number" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['model_number'] ?? '')) ?>">
                    </div>
                    <div class="finance-field inv-field--full">
                        <label class="finance-label" for="inv-serial">Serial number</label>
                        <input type="text" id="inv-serial" name="serial_number" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['serial_number'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="inv-form-section">
                <h3 class="inv-form-section__title"><i data-lucide="shield-check"></i> Condition & status</h3>
                <div class="inv-form-grid">
                    <div class="finance-field">
                        <label class="finance-label" for="inv-status">Status</label>
                        <select id="inv-status" name="status" class="finance-input">
                            <?php foreach ($statuses as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-condition">Physical condition</label>
                        <select id="inv-condition" name="condition_status" class="finance-input">
                            <?php foreach ($conditions as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $condition === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-last-checked">Last checked / audited</label>
                        <input type="date" id="inv-last-checked" name="last_checked_at" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['last_checked_at'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-checked-by">Checked by</label>
                        <input type="text" id="inv-checked-by" name="checked_by" class="finance-input"
                               placeholder="Staff name"
                               value="<?= htmlspecialchars((string) ($item['checked_by'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="inv-form-section">
                <h3 class="inv-form-section__title"><i data-lucide="wallet"></i> Purchase & value</h3>
                <div class="inv-form-grid">
                    <div class="finance-field">
                        <label class="finance-label" for="inv-purchase-date">Purchase date</label>
                        <input type="date" id="inv-purchase-date" name="purchase_date" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['purchase_date'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-purchase-price">Purchase price (KES)</label>
                        <input type="number" id="inv-purchase-price" name="purchase_price" min="0" step="1" class="finance-input"
                               value="<?= $item['purchase_price'] !== null && $item['purchase_price'] !== '' ? htmlspecialchars((string) $item['purchase_price']) : '' ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-current-value">Current estimated value (KES)</label>
                        <input type="number" id="inv-current-value" name="current_value" min="0" step="1" class="finance-input"
                               value="<?= $item['current_value'] !== null && $item['current_value'] !== '' ? htmlspecialchars((string) $item['current_value']) : '' ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-supplier">Supplier / vendor</label>
                        <input type="text" id="inv-supplier" name="supplier" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['supplier'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="inv-warranty">Warranty expires</label>
                        <input type="date" id="inv-warranty" name="warranty_expires" class="finance-input"
                               value="<?= htmlspecialchars((string) ($item['warranty_expires'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="inv-form-section">
                <h3 class="inv-form-section__title"><i data-lucide="sticky-note"></i> Internal notes</h3>
                <div class="finance-field">
                    <label class="finance-label" for="inv-notes">Notes</label>
                    <textarea id="inv-notes" name="notes" rows="4" class="finance-input finance-textarea"
                              placeholder="Maintenance history, accessories included, access codes, reminders…"><?= htmlspecialchars((string) ($item['notes'] ?? '')) ?></textarea>
                </div>
            </div>

            <div class="inv-form-footer">
                <button type="submit" class="member-profile-save-btn"><i data-lucide="save" class="w-4 h-4"></i> Save all details</button>
            </div>
        </form>
        </div>

        <div class="admin-profile-tabs__panel" x-show="tab === 'manage'" x-cloak role="tabpanel">
<form method="post"
                  action="/admin/inventory/<?= $id ?>/delete"
                  class="inv-danger-card"
                  data-confirm="Permanently delete this inventory item? All photos will also be removed."
                  data-confirm-title="Delete inventory item?"
                  data-confirm-label="Delete item"
                  data-confirm-tone="danger">
                <p class="inv-danger-card__title">Danger zone</p>
                <p class="inv-danger-card__text">Permanently remove this item and all of its photos from the inventory.</p>
                <button type="submit" class="inv-danger-card__btn">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Delete item
                </button>
            </form>
        </div>
    </div>

    <div class="inv-lightbox"
         x-show="galleryOpen"
         x-cloak
         @keydown.escape.window="galleryOpen = false"
         @click.self="galleryOpen = false">
        <button type="button" class="inv-lightbox__close" @click="galleryOpen = false" aria-label="Close">
            <i data-lucide="x"></i>
        </button>
        <img :src="gallerySrc" alt="" class="inv-lightbox__img">
    </div>
</div>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
