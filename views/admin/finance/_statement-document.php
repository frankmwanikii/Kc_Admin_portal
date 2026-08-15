<?php

/** Statement document body (print/PDF target). */
/** @var array<string, mixed> $statement */
/** @var int $year */
/** @var string $churchName */
/** @var string $statementLogoUrl */
/** @var string $statementDisclaimer */
$stmtFmt = $stmtFmt ?? static fn (float $n): string => number_format($n, 2);
$summary = $statement['summary'] ?? ['collections' => 0, 'expenses' => 0, 'balance' => 0, 'status' => 'balanced', 'status_label' => 'Balanced position'];
$arrears = $statement['arrears'] ?? ['total_due' => 0, 'total_paid' => 0, 'balance_owing' => 0, 'count' => 0, 'budget_year' => $year];
$truePicture = $statement['true_picture'] ?? ['operating_balance' => 0, 'arrears_owing' => 0, 'net_position' => 0, 'status' => 'balanced', 'status_label' => 'Balanced net position'];
$balanceClass = match ($summary['status'] ?? 'balanced') {
    'surplus' => 'finance-statement-summary__item--surplus',
    'deficit' => 'finance-statement-summary__item--deficit',
    default => 'finance-statement-summary__item--neutral',
};
$truePictureClass = match ($truePicture['status'] ?? 'balanced') {
    'surplus' => 'finance-statement-summary__item--surplus',
    'deficit' => 'finance-statement-summary__item--deficit',
    default => 'finance-statement-summary__item--neutral',
};
$generatedAt = $generatedAt ?? date('j F Y, g:i a');
$refId = $refId ?? ('STMT-' . strtoupper($statement['view'] ?? 'M') . '-' . ($statement['year'] ?? $year) . '-' . date('YmdHis'));
?>
    <div id="finance-statement-document"
         class="finance-statement finance-statement--watermarked">
        <img src="<?= htmlspecialchars($statementLogoUrl ?? '/images/kc-logo.png') ?>"
             alt=""
             class="finance-statement__watermark"
             aria-hidden="true">
        <header class="finance-statement__letterhead">
            <div class="finance-statement__brand-block">
                <img src="<?= htmlspecialchars($statementLogoUrl ?? '/images/kc-logo.png') ?>"
                     alt="<?= htmlspecialchars($churchName) ?>"
                     class="finance-statement__logo">
                <p class="finance-statement__org"><?= htmlspecialchars($churchName) ?></p>
                <p class="finance-statement__doc-title">Financial Statement</p>
                <div class="finance-statement__meta">
                    <p><span class="finance-statement__meta-label">Statement period</span><br><?= htmlspecialchars($statement['period_label'] ?? '') ?></p>
                    <p><span class="finance-statement__meta-label">Generated</span><br><?= htmlspecialchars($generatedAt) ?></p>
                    <p><span class="finance-statement__meta-label">Reference</span><br><?= htmlspecialchars($refId) ?></p>
                </div>
            </div>
        </header>

        <p class="finance-statement__subtitle"><?= htmlspecialchars($statement['period_subtitle'] ?? '') ?></p>

        <section class="finance-statement-summary">
            <div class="finance-statement-summary__item finance-statement-summary__item--collections">
                <p class="finance-statement-summary__label">Total collections</p>
                <p class="finance-statement-summary__value">KES <?= $stmtFmt((float) $summary['collections']) ?></p>
            </div>
            <div class="finance-statement-summary__item finance-statement-summary__item--expenses">
                <p class="finance-statement-summary__label">Weekly expenses</p>
                <p class="finance-statement-summary__value">KES <?= $stmtFmt((float) $summary['expenses']) ?></p>
            </div>
            <div class="finance-statement-summary__item finance-statement-summary__item--balance <?= $balanceClass ?>">
                <p class="finance-statement-summary__label"><?= htmlspecialchars($summary['status_label'] ?? 'Balance') ?></p>
                <p class="finance-statement-summary__value"><?= ((float) $summary['balance'] < 0 ? '-' : '') ?>KES <?= $stmtFmt(abs((float) $summary['balance'])) ?></p>
            </div>
        </section>

        <section class="finance-statement-true-picture">
            <h3 class="finance-statement-section__title">Net Financial Position</h3>
            <p class="finance-statement-true-picture__hint">Operating position for this period, adjusted for outstanding bills (budget year <?= (int) ($arrears['budget_year'] ?? $year) ?>).</p>
            <div class="finance-statement-summary finance-statement-summary--true-picture">
                <div class="finance-statement-summary__item finance-statement-summary__item--balance <?= $balanceClass ?>">
                    <p class="finance-statement-summary__label"><?= htmlspecialchars($summary['status_label'] ?? 'Operating balance') ?></p>
                    <p class="finance-statement-summary__value"><?= ((float) $truePicture['operating_balance'] < 0 ? '-' : '') ?>KES <?= $stmtFmt(abs((float) $truePicture['operating_balance'])) ?></p>
                </div>
                <div class="finance-statement-summary__item finance-statement-summary__item--arrears">
                    <p class="finance-statement-summary__label">Outstanding arrears</p>
                    <p class="finance-statement-summary__value">KES <?= $stmtFmt((float) $arrears['balance_owing']) ?></p>
                </div>
                <div class="finance-statement-summary__item finance-statement-summary__item--balance <?= $truePictureClass ?>">
                    <p class="finance-statement-summary__label"><?= htmlspecialchars($truePicture['status_label'] ?? 'Net position') ?></p>
                    <p class="finance-statement-summary__value"><?= ((float) $truePicture['net_position'] < 0 ? '-' : '') ?>KES <?= $stmtFmt(abs((float) $truePicture['net_position'])) ?></p>
                </div>
            </div>
            <p class="finance-statement__narrative finance-statement__narrative--true-picture"><?= htmlspecialchars($statement['true_picture_narrative'] ?? '') ?></p>
        </section>

        <p class="finance-statement__narrative"><?= htmlspecialchars($statement['narrative'] ?? '') ?></p>

        <div class="finance-statement__columns">
            <section class="finance-statement-section">
                <h3 class="finance-statement-section__title">Collections</h3>
                <div class="arrears-table-scroll">
                <table class="finance-statement-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="finance-statement-table__amount">Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statement['collection_lines'] ?? [] as $line): ?>
                        <tr>
                            <td><?= htmlspecialchars($line['label']) ?></td>
                            <td class="finance-statement-table__amount"><?= $stmtFmt((float) $line['amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total collections</td>
                            <td class="finance-statement-table__amount">KES <?= $stmtFmt((float) $summary['collections']) ?></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </section>

            <section class="finance-statement-section">
                <h3 class="finance-statement-section__title">Weekly expenses</h3>
                <div class="arrears-table-scroll">
                <table class="finance-statement-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="finance-statement-table__amount">Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statement['expense_lines'] ?? [] as $line): ?>
                        <tr>
                            <td><?= htmlspecialchars($line['label']) ?></td>
                            <td class="finance-statement-table__amount"><?= $stmtFmt((float) $line['amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total weekly expenses</td>
                            <td class="finance-statement-table__amount">KES <?= $stmtFmt((float) $summary['expenses']) ?></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </section>
        </div>

        <?php if (!empty($statement['activity_rows'])): ?>
        <section class="finance-statement-section finance-statement-section--full">
            <h3 class="finance-statement-section__title"><?= htmlspecialchars($statement['activity_heading'] ?? 'Activity') ?></h3>
            <div class="arrears-table-scroll">
            <table class="finance-statement-table finance-statement-table--activity">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th class="finance-statement-table__amount">Collections</th>
                        <th class="finance-statement-table__amount">Expenses</th>
                        <th class="finance-statement-table__amount">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statement['activity_rows'] as $row): ?>
                    <?php
                    $rowBal = (float) ($row['balance'] ?? 0);
                    $rowBalClass = $rowBal >= 0 ? 'finance-statement-table__pos' : 'finance-statement-table__neg';
                    ?>
                    <tr>
                        <td>
                            <span class="finance-statement-period-main"><?= htmlspecialchars($row['label']) ?></span>
                            <?php if (!empty($row['sub_label'])): ?>
                            <span class="finance-statement-period-sub"><?= htmlspecialchars($row['sub_label']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="finance-statement-table__amount"><?= $stmtFmt((float) $row['collections']) ?></td>
                        <td class="finance-statement-table__amount"><?= $stmtFmt((float) $row['expenses']) ?></td>
                        <td class="finance-statement-table__amount <?= $rowBal >= 0 ? 'finance-statement-table__pos' : 'finance-statement-table__neg' ?>"><?= ($rowBal < 0 ? '-' : '') . $stmtFmt(abs($rowBal)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Period total</td>
                        <td class="finance-statement-table__amount">KES <?= $stmtFmt((float) $summary['collections']) ?></td>
                        <td class="finance-statement-table__amount">KES <?= $stmtFmt((float) $summary['expenses']) ?></td>
                        <td class="finance-statement-table__amount <?= (float) $summary['balance'] >= 0 ? 'finance-statement-table__pos' : 'finance-statement-table__neg' ?>"><?= ((float) $summary['balance'] < 0 ? '-' : '') ?>KES <?= $stmtFmt(abs((float) $summary['balance'])) ?></td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </section>
        <?php endif; ?>

        <section class="finance-statement-section finance-statement-section--full">
            <h3 class="finance-statement-section__title">Outstanding arrears</h3>
            <p class="finance-statement-arrears__hint">Bills recorded as owed for budget year <?= (int) ($arrears['budget_year'] ?? $year) ?> — separate from weekly cash spending above.</p>
            <?php if (empty($statement['arrears_lines'])): ?>
            <p class="finance-statement-arrears__empty">No outstanding bills recorded for this budget year.</p>
            <?php else: ?>
            <div class="arrears-table-scroll">
            <table class="finance-statement-table finance-statement-table--arrears">
                <thead>
                    <tr>
                        <th>Expense item</th>
                        <th>Period incurred</th>
                        <th class="finance-statement-table__amount">Amount due</th>
                        <th class="finance-statement-table__amount">Amount paid</th>
                        <th class="finance-statement-table__amount">Balance owing</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statement['arrears_lines'] as $line): ?>
                    <?php
                    $owing = (float) ($line['balance_owing'] ?? 0);
                    $statusClass = match ($line['payment_status'] ?? 'UNPAID') {
                        'PAID' => 'finance-statement-arrears-status--paid',
                        'PARTIAL' => 'finance-statement-arrears-status--partial',
                        default => 'finance-statement-arrears-status--unpaid',
                    };
                    ?>
                    <tr>
                        <td><span class="finance-statement-period-main"><?= htmlspecialchars($line['expense_item']) ?></span></td>
                        <td><?= htmlspecialchars($line['month_incurred']) ?></td>
                        <td class="finance-statement-table__amount"><?= $stmtFmt((float) $line['amount_due']) ?></td>
                        <td class="finance-statement-table__amount"><?= $stmtFmt((float) $line['amount_paid']) ?></td>
                        <td class="finance-statement-table__amount <?= $owing > 0 ? 'finance-statement-table__neg' : 'finance-statement-table__pos' ?>"><?= $stmtFmt($owing) ?></td>
                        <td><span class="finance-statement-arrears-status <?= $statusClass ?>"><?= htmlspecialchars($line['status_label']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Year totals</td>
                        <td class="finance-statement-table__amount">KES <?= $stmtFmt((float) $arrears['total_due']) ?></td>
                        <td class="finance-statement-table__amount">KES <?= $stmtFmt((float) $arrears['total_paid']) ?></td>
                        <td class="finance-statement-table__amount <?= (float) $arrears['balance_owing'] > 0 ? 'finance-statement-table__neg' : 'finance-statement-table__pos' ?>">KES <?= $stmtFmt((float) $arrears['balance_owing']) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            </div>
            <?php endif; ?>
        </section>

        <footer class="finance-statement__footer">
            <div class="finance-statement__footer-inner">
                <p class="finance-statement__footer-disclaimer"><?= htmlspecialchars($statementDisclaimer) ?></p>
                <p class="finance-statement__footer-signoff"><?= htmlspecialchars($churchName) ?> · Finance Office</p>
            </div>
        </footer>
    </div>
