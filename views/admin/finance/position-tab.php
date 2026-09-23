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
    <div class="fin-report-bar no-print">
        <div class="fin-report-bar__main">
            <div class="fin-report-bar__identity">
                <h2 class="fin-report-bar__title">Income &amp; Expenditure</h2>
                <p class="fin-report-bar__hint">Comparative Sunday collections and expenses</p>
            </div>
            <div class="fin-report-bar__tools">
                <label class="fin-report-bar__year">
                    <span class="fin-report-bar__year-label">Year</span>
                    <select class="fin-report-bar__select"
                            aria-label="Reporting year"
                            :value="year"
                            :disabled="positionBusy"
                            @change="changePositionYear(Number($event.target.value))">
                        <template x-for="y in financeYears" :key="y">
                            <option :value="y" :selected="Number(year) === Number(y)" x-text="y"></option>
                        </template>
                    </select>
                </label>
                <div class="fin-export-group" role="group" aria-label="Export statement">
                    <button type="button"
                            class="fin-export-group__btn"
                            @click="printStatement()"
                            :disabled="positionBusy"
                            title="Print">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Print</span>
                    </button>
                    <a :href="positionExportUrl('pdf')"
                       class="fin-export-group__btn fin-export-group__btn--primary no-underline"
                       title="Download PDF">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>PDF</span>
                    </a>
                    <a :href="positionExportUrl('csv')"
                       class="fin-export-group__btn no-underline"
                       title="Download CSV">
                        <i data-lucide="table-2" class="w-4 h-4"></i>
                        <span>CSV</span>
                    </a>
                </div>
            </div>
        </div>
        <p class="fin-report-bar__status" x-show="positionBusy" x-cloak>Updating statement…</p>
    </div>

    <div x-ref="positionDocumentWrap">
        <?php require __DIR__ . '/_position-document.php'; ?>
    </div>
</div>
