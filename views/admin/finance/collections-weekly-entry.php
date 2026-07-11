<?php
$monthLabel = date('F Y', strtotime($month . '-01'));
?>
<div class="weekly-entry-page" x-cloak x-data="weeklyCollectionsEntryForm(<?= htmlspecialchars(json_encode([
    'amountsByDate' => $amountsByDate ?? [],
    'weekDate' => $weekDate ?? '',
    'methods' => array_keys($paymentMethods ?? []),
]), ENT_QUOTES) ?>)">
    <div class="weekly-entry-header">
        <a href="/admin/finance?tab=ledger&sub=collections&year=<?= (int) substr($month, 0, 4) ?>&month=<?= htmlspecialchars($month) ?>" class="weekly-entry-back">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to weekly collections
        </a>
        <div class="weekly-entry-header-text">
            <p class="weekly-entry-eyebrow">Weekly collections · <?= htmlspecialchars($monthLabel) ?></p>
            <h2 class="weekly-entry-title">Enter weekly collections</h2>
            <p class="weekly-entry-sub">Record Paybill, cheque, and cash totals for a single Sunday service.</p>
        </div>
    </div>

    <form method="post" action="/admin/finance/collections/weekly" class="weekly-entry-card">
        <div class="weekly-entry-toolbar">
            <div class="weekly-entry-date-wrap">
                <label for="week_date" class="weekly-entry-label">Sunday date</label>
                <select id="week_date"
                        name="week_date"
                        required
                        class="weekly-entry-select"
                        x-model="weekDate"
                        @change="onDateChange()">
                    <?php foreach ($sundays as $sun): ?>
                    <option value="<?= htmlspecialchars($sun) ?>" <?= ($weekDate ?? '') === $sun ? 'selected' : '' ?>>
                        <?= date('l, M j Y', strtotime($sun)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="weekly-entry-total-pill">
                <span class="weekly-entry-total-label">Week total</span>
                <span class="weekly-entry-total-value" x-text="'KES ' + weekTotal.toLocaleString('en-KE', { maximumFractionDigits: 0 })"></span>
            </div>
        </div>

        <div class="weekly-entry-grid weekly-entry-grid--collections">
            <?php foreach ($paymentMethods as $method => $meta):
                $val = $amounts[$method] ?? 0;
            ?>
            <div class="weekly-entry-field">
                <label class="weekly-entry-field-label" for="amt_<?= htmlspecialchars($method) ?>">
                    <?= htmlspecialchars($meta['label']) ?>
                </label>
                <?php if (!empty($meta['desc'])): ?>
                <p class="weekly-entry-field-hint"><?= htmlspecialchars($meta['desc']) ?></p>
                <?php endif; ?>
                <div class="weekly-entry-input-wrap">
                    <span class="weekly-entry-currency">KES</span>
                    <input type="number"
                           id="amt_<?= htmlspecialchars($method) ?>"
                           name="amounts[<?= htmlspecialchars($method) ?>]"
                           min="0"
                           step="0.01"
                           value="<?= $val > 0 ? htmlspecialchars((string) $val) : '' ?>"
                           placeholder="0"
                           class="weekly-entry-input"
                           x-model.number="fields['<?= htmlspecialchars($method) ?>']"
                           @input="recalc()">
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="weekly-entry-actions">
            <button type="submit" class="weekly-entry-btn-primary">
                <i data-lucide="check" class="w-4 h-4"></i>
                Save weekly collections
            </button>
            <a href="/admin/finance?tab=ledger&sub=collections&year=<?= (int) substr($month, 0, 4) ?>&month=<?= htmlspecialchars($month) ?>" class="weekly-entry-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
