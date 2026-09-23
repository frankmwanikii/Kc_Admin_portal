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
    <div class="fin-report-bar no-print">
        <div class="fin-report-bar__main">
            <div class="fin-report-bar__identity">
                <h2 class="fin-report-bar__title">Operating Statement</h2>
                <p class="fin-report-bar__hint">Collections and spending for the selected period</p>
            </div>
            <div class="fin-report-bar__tools">
                <div class="fin-export-group" role="group" aria-label="Export statement">
                    <button type="button"
                            class="fin-export-group__btn"
                            @click="printStatement()"
                            :disabled="statementBusy"
                            title="Print">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Print</span>
                    </button>
                    <a :href="statementExportUrl('pdf')"
                       class="fin-export-group__btn fin-export-group__btn--primary no-underline"
                       title="Download PDF">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>PDF</span>
                    </a>
                    <a :href="statementExportUrl('csv')"
                       class="fin-export-group__btn no-underline"
                       title="Download CSV">
                        <i data-lucide="table-2" class="w-4 h-4"></i>
                        <span>CSV</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="fin-report-bar__filters">
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

            <div class="fin-report-bar__period">
                <select x-show="statementView === 'weekly'"
                        x-cloak
                        class="fin-report-bar__select"
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
                       class="fin-report-bar__select"
                       aria-label="Month"
                       :value="weeklyMonth"
                       :disabled="statementBusy"
                       @change="changeStatementMonth($event.target.value)">
                <select class="fin-report-bar__select"
                        aria-label="Year"
                        :value="year"
                        :disabled="statementBusy"
                        @change="changeStatementYear(Number($event.target.value))">
                    <template x-for="y in financeYears" :key="'stmt-y-' + y">
                        <option :value="y" :selected="Number(year) === Number(y)" x-text="y"></option>
                    </template>
                </select>
            </div>
        </div>
        <p class="fin-report-bar__status" x-show="statementBusy" x-cloak>Updating statement…</p>
    </div>

    <div x-ref="statementDocumentWrap">
        <?php require __DIR__ . '/_statement-document.php'; ?>
    </div>
</div>
