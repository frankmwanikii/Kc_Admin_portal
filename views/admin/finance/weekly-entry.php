<?php
$monthLabel = date('F Y', strtotime($month . '-01'));
$fmt = static fn (float $n): string => number_format($n, 0);
?>
<div class="weekly-entry-page" x-cloak x-data="weeklyEntryForm(<?= htmlspecialchars(json_encode([
    'amountsByDate' => $amountsByDate ?? [],
    'weekDate' => $weekDate ?? '',
    'categories' => array_keys($categories ?? []),
]), ENT_QUOTES) ?>)">
    <div class="weekly-entry-header">
        <a href="/admin/finance?tab=ledger&sub=expenses&month=<?= htmlspecialchars($month) ?>" class="weekly-entry-back">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to weekly expenses
        </a>
        <div class="weekly-entry-header-text">
            <p class="weekly-entry-eyebrow">Weekly expenses · <?= htmlspecialchars($monthLabel) ?></p>
            <h2 class="weekly-entry-title">Enter weekly expenses</h2>
            <p class="weekly-entry-sub">Record allowances and costs for a single Sunday service.</p>
        </div>
    </div>

    <form method="post" action="/admin/finance/weekly" class="weekly-entry-card">
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

        <div class="weekly-entry-table-scroll" tabindex="0" role="region" aria-label="Weekly expense items">
            <table class="weekly-entry-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="hidden sm:table-cell">Category</th>
                        <th class="hidden md:table-cell">Sub-category</th>
                        <th class="hidden lg:table-cell">Note</th>
                        <th class="weekly-entry-th-amount">Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $slug => $meta):
                        $val = $amounts[$slug] ?? 0;
                    ?>
                    <tr class="weekly-entry-row">
                        <td class="weekly-entry-td-item">
                            <label class="weekly-entry-table-label" for="amt_<?= htmlspecialchars($slug) ?>">
                                <?= htmlspecialchars($meta['label']) ?>
                            </label>
                            <span class="weekly-entry-table-meta sm:hidden">
                                <?php if (!empty($meta['group_label'])): ?>
                                <?= htmlspecialchars($meta['group_label']) ?>
                                <?php if (!empty($meta['department_label'])): ?> · <?= htmlspecialchars($meta['department_label']) ?><?php endif; ?>
                                <?php elseif (!empty($meta['department_label'])): ?>
                                <?= htmlspecialchars($meta['department_label']) ?>
                                <?php endif; ?>
                                <?php if ($meta['hint']): ?>
                                <span class="weekly-entry-table-meta-note"> · <?= htmlspecialchars($meta['hint']) ?></span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="weekly-entry-td-muted hidden sm:table-cell">
                            <?= htmlspecialchars($meta['group_label'] ?? '—') ?>
                        </td>
                        <td class="weekly-entry-td-muted hidden md:table-cell">
                            <?= htmlspecialchars($meta['department_label'] ?? '—') ?>
                        </td>
                        <td class="weekly-entry-td-muted hidden lg:table-cell">
                            <?= $meta['hint'] !== '' ? htmlspecialchars($meta['hint']) : '—' ?>
                        </td>
                        <td class="weekly-entry-td-amount">
                            <div class="weekly-entry-input-wrap weekly-entry-input-wrap--table">
                                <span class="weekly-entry-currency">KES</span>
                                <input type="number"
                                       id="amt_<?= htmlspecialchars($slug) ?>"
                                       name="amounts[<?= htmlspecialchars($slug) ?>]"
                                       min="0"
                                       step="0.01"
                                       value="<?= $val > 0 ? htmlspecialchars((string) $val) : '' ?>"
                                       placeholder="0"
                                       class="weekly-entry-input"
                                       x-model.number="fields['<?= htmlspecialchars($slug) ?>']"
                                       @input="recalc()"
                                       aria-label="Amount for <?= htmlspecialchars($meta['label']) ?>">
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="weekly-entry-actions">
            <button type="submit" class="weekly-entry-btn-primary">
                <i data-lucide="check" class="w-4 h-4"></i>
                Save weekly expenses
            </button>
            <a href="/admin/finance?tab=ledger&sub=expenses&month=<?= htmlspecialchars($month) ?>" class="weekly-entry-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
