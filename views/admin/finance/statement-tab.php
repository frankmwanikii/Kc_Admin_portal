<?php
/** @var array<string, mixed> $statement */
/** @var string $statementView */
/** @var string $statementWeekDate */
/** @var list<string> $statementSundays */
/** @var int $year */
/** @var string $month */
/** @var string $churchName */
/** @var string $statementLogoUrl */
/** @var string $statementDisclaimer */
$stmtFmt = static fn (float $n): string => number_format($n, 2);
$generatedAt = date('j F Y, g:i a');
$refId = 'STMT-' . strtoupper($statement['view'] ?? 'M') . '-' . ($statement['year'] ?? $year) . '-' . date('YmdHis');
?>
<div class="arrears-page statement-page">
    <div class="statement-toolbar no-print">
        <div class="statement-toolbar-row">
            <h2 class="arrears-title statement-toolbar-title">Financial Statement</h2>
            <div class="statement-toolbar-actions">
                <button type="button" @click="printStatement()" class="arrears-btn-outline" :disabled="statementBusy">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    Print
                </button>
                <a :href="statementExportUrl('pdf')" class="arrears-btn-new no-underline">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    Download PDF
                </a>
                <a :href="statementExportUrl('csv')" class="arrears-btn-outline no-underline">
                    <i data-lucide="table-2" class="w-4 h-4"></i>
                    Download CSV
                </a>
            </div>
        </div>

        <div class="statement-controls">
            <div class="statement-view-toggle" role="tablist" aria-label="Statement period">
                <button type="button"
                        role="tab"
                        class="statement-view-toggle__btn"
                        :class="statementView === 'weekly' && 'statement-view-toggle__btn--active'"
                        :aria-selected="statementView === 'weekly'"
                        :disabled="statementBusy"
                        @click="setStatementView('weekly')">Weekly</button>
                <button type="button"
                        role="tab"
                        class="statement-view-toggle__btn"
                        :class="statementView === 'monthly' && 'statement-view-toggle__btn--active'"
                        :aria-selected="statementView === 'monthly'"
                        :disabled="statementBusy"
                        @click="setStatementView('monthly')">Monthly</button>
                <button type="button"
                        role="tab"
                        class="statement-view-toggle__btn"
                        :class="statementView === 'annual' && 'statement-view-toggle__btn--active'"
                        :aria-selected="statementView === 'annual'"
                        :disabled="statementBusy"
                        @click="setStatementView('annual')">Annual</button>
            </div>

            <div class="statement-period-form">
                <select x-show="statementView === 'weekly'"
                        x-cloak
                        class="arrears-year-select"
                        aria-label="Sunday week"
                        :value="statementWeekDate"
                        :disabled="statementBusy"
                        @change="changeStatementWeek($event.target.value)">
                    <template x-for="(sun, index) in statementSundays" :key="sun">
                        <option :value="sun"
                                :selected="sun === statementWeekDate"
                                x-text="'Sun ' + (index + 1) + ' — ' + formatSundayLong(sun)"></option>
                    </template>
                </select>
                <input x-show="statementView !== 'annual'"
                       x-cloak
                       type="month"
                       class="arrears-year-select"
                       aria-label="Month"
                       :value="weeklyMonth"
                       :disabled="statementBusy"
                       @change="changeStatementMonth($event.target.value)">
                <select class="arrears-year-select"
                        aria-label="Year"
                        :value="year"
                        :disabled="statementBusy"
                        @change="changeStatementYear(Number($event.target.value))">
                    <?php for ($y = (int) date('Y') + 1; $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <p class="finance-field-hint" x-show="statementBusy" x-cloak>Updating statement…</p>
    </div>

    <div x-ref="statementDocumentWrap">
        <?php require __DIR__ . '/_statement-document.php'; ?>
    </div>
</div>
