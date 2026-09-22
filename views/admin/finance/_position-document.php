<?php

/** Consolidated department totals (print/PDF target). */
/** @var array<string, mixed> $position */
/** @var int $year */
/** @var string $churchName */
/** @var string $statementLogoUrl */
/** @var string $statementDisclaimer */

$fmtAmt = static function (float $n): string {
    return \App\Services\FinanceReconciliationService::formatPositionAmount($n);
};

$year = (int) ($position['year'] ?? $year ?? date('Y'));
$priorYear = (int) ($position['prior_year'] ?? ($year - 1));
$generatedAt = $generatedAt ?? date('j F Y, g:i a');
$refId = $refId ?? ('POS-' . $year . '-' . date('YmdHis'));
$departments = $position['departments'] ?? [];
$deptTotals = $position['department_totals'] ?? [];
$note = 1;
?>
<div id="finance-position-document"
     class="finance-statement finance-statement--watermarked finance-position">
    <img src="<?= htmlspecialchars($statementLogoUrl ?? '/images/kc-logo.png') ?>"
         alt=""
         class="finance-statement__watermark"
         aria-hidden="true">

    <header class="finance-position__header">
        <img src="<?= htmlspecialchars($statementLogoUrl ?? '/images/kc-logo.png') ?>"
             alt="<?= htmlspecialchars($churchName) ?>"
             class="finance-statement__logo">
        <p class="finance-position__org"><?= htmlspecialchars(strtoupper($churchName)) ?></p>
        <p class="finance-position__title">
            <span class="finance-position__title-line">Consolidated Statement of Financial Position</span>
            <span class="finance-position__title-asat">as at <?= htmlspecialchars($position['as_at_label'] ?? ('31st December ' . $year)) ?></span>
        </p>
        <div class="finance-statement__meta finance-position__meta">
            <p><span class="finance-statement__meta-label">Reporting year</span><br><?= (int) $year ?></p>
            <p><span class="finance-statement__meta-label">Generated</span><br><?= htmlspecialchars($generatedAt) ?></p>
            <p><span class="finance-statement__meta-label">Reference</span><br><?= htmlspecialchars($refId) ?></p>
        </div>
    </header>

    <p class="finance-statement__subtitle"><?= htmlspecialchars($position['period_subtitle'] ?? '') ?></p>

    <div class="arrears-table-scroll">
        <table class="finance-position-table" aria-label="Department totals">
            <thead>
                <tr>
                    <th scope="col">Department</th>
                    <th scope="col" class="finance-position-table__note">Notes</th>
                    <th scope="col" class="finance-position-table__amt finance-position-table__col--current">
                        CONSOLIDATED<br><?= (int) $year ?><br><span class="finance-position-table__currency">(KShs)</span>
                    </th>
                    <th scope="col" class="finance-position-table__amt finance-position-table__col--prior">
                        CONSOLIDATED<br><?= (int) $priorYear ?><br><span class="finance-position-table__currency">(KShs)</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($dept['label'] ?? '')) ?></td>
                    <td class="finance-position-table__note"><?= (int) $note ?></td>
                    <td class="finance-position-table__amt finance-position-table__col--current"><?= $fmtAmt((float) ($dept['application_current'] ?? 0)) ?></td>
                    <td class="finance-position-table__amt finance-position-table__col--prior"><?= $fmtAmt((float) ($dept['application_prior'] ?? 0)) ?></td>
                </tr>
                <?php $note++; endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="finance-position-table__total">
                    <td>TOTAL</td>
                    <td class="finance-position-table__note"></td>
                    <td class="finance-position-table__amt finance-position-table__col--current"><?= $fmtAmt((float) ($deptTotals['application_current'] ?? 0)) ?></td>
                    <td class="finance-position-table__amt finance-position-table__col--prior"><?= $fmtAmt((float) ($deptTotals['application_prior'] ?? 0)) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <footer class="finance-statement__footer">
        <p class="finance-statement__disclaimer"><?= htmlspecialchars($statementDisclaimer ?? '') ?></p>
        <p class="finance-statement__signoff"><?= htmlspecialchars($churchName) ?> · Finance Office</p>
    </footer>
</div>
