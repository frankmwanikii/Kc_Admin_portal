<?php
/** @var array<string, mixed> $position */
/** @var int $year */
/** @var string $churchName */
/** @var string $statementLogoUrl */
/** @var string $statementDisclaimer */
$generatedAt = date('j F Y, g:i a');
$refId = 'POS-' . (int) ($position['year'] ?? $year) . '-' . date('YmdHis');
?>
<div class="arrears-page statement-page position-page">
    <div class="statement-toolbar no-print">
        <div class="statement-toolbar-row">
            <h2 class="arrears-title statement-toolbar-title">Consolidated Income &amp; Expenditure</h2>
            <div class="statement-toolbar-actions">
                <button type="button" @click="printStatement()" class="arrears-btn-outline" :disabled="positionBusy">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    Print
                </button>
                <a :href="positionExportUrl('pdf')" class="arrears-btn-new no-underline">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    Download PDF
                </a>
                <a :href="positionExportUrl('csv')" class="arrears-btn-outline no-underline">
                    <i data-lucide="table-2" class="w-4 h-4"></i>
                    Download CSV
                </a>
            </div>
        </div>

        <div class="statement-controls">
            <div class="statement-period-form">
                <select class="arrears-year-select"
                        aria-label="Year"
                        :value="year"
                        :disabled="positionBusy"
                        @change="changePositionYear(Number($event.target.value))">
                    <?php for ($y = (int) date('Y') + 1; $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= (int) $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <p class="finance-field-hint" x-show="positionBusy" x-cloak>Updating consolidated position…</p>
    </div>

    <div x-ref="positionDocumentWrap">
        <?php require __DIR__ . '/_position-document.php'; ?>
    </div>
</div>
