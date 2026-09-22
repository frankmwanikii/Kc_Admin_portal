<?php

/** Consolidated Statement of Income and Expenditure. */
/** @var array<string, mixed> $position */
/** @var int $year */
/** @var string $churchName */
/** @var string $statementLogoUrl */
/** @var string $statementDisclaimer */

$fmt = static function (float $n, bool $outflow = false): string {
    return \App\Services\FinanceReconciliationService::formatPositionAmount($n, true, $outflow);
};

$year = (int) ($position['year'] ?? $year ?? date('Y'));
$priorYear = (int) ($position['prior_year'] ?? ($year - 1));
$generatedAt = $generatedAt ?? date('j F Y, g:i a');
$refId = $refId ?? ('POS-' . $year . '-' . date('YmdHis'));
$rows = $position['rows'] ?? [];
$docTitle = (string) ($position['document_title'] ?? 'Consolidated Statement of Income and Expenditure');

$currentAmt = static function (array $amounts, bool $outflow) use ($fmt): string {
    $bucket = $amounts['group'] ?? $amounts['entity'] ?? ['current' => 0, 'prior' => 0];

    return $fmt((float) ($bucket['current'] ?? 0), $outflow);
};
$priorAmt = static function (array $amounts, bool $outflow) use ($fmt): string {
    $bucket = $amounts['group'] ?? $amounts['entity'] ?? ['current' => 0, 'prior' => 0];

    return $fmt((float) ($bucket['prior'] ?? 0), $outflow);
};
?>
<div id="finance-position-document"
     class="finance-statement finance-statement--watermarked finance-position finance-position--ie">
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
            <span class="finance-position__title-line"><?= htmlspecialchars($docTitle) ?></span>
            <span class="finance-position__title-asat">for the year ended <?= htmlspecialchars($position['as_at_label'] ?? ('31st December ' . $year)) ?></span>
        </p>
        <div class="finance-statement__meta finance-position__meta">
            <p><span class="finance-statement__meta-label">Reporting year</span><br><?= (int) $year ?></p>
            <p><span class="finance-statement__meta-label">Generated</span><br><?= htmlspecialchars($generatedAt) ?></p>
            <p><span class="finance-statement__meta-label">Reference</span><br><?= htmlspecialchars($refId) ?></p>
        </div>
    </header>

    <div class="arrears-table-scroll">
        <table class="citam-ie-table" aria-label="Consolidated statement of income and expenditure">
            <thead>
                <tr>
                    <th class="citam-ie-table__label">Items</th>
                    <th class="citam-ie-table__amt citam-ie-table__col--g-cur"><?= (int) $year ?><br><span>KShs</span></th>
                    <th class="citam-ie-table__amt citam-ie-table__col--g-pri"><?= (int) $priorYear ?><br><span>KShs</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $type = (string) ($row['type'] ?? 'line');
                    $outflow = !empty($row['outflow']);
                    $amounts = $row['amounts'] ?? [
                        'group' => ['current' => 0, 'prior' => 0],
                    ];
                    if ($type === 'section'): ?>
                <tr class="citam-ie-table__section">
                    <td colspan="3"><?= htmlspecialchars((string) ($row['label'] ?? '')) ?></td>
                </tr>
                    <?php continue; endif; ?>

                <tr class="citam-ie-table__<?= htmlspecialchars($type) ?>">
                    <td class="citam-ie-table__label"><?= htmlspecialchars((string) ($row['label'] ?? '')) ?></td>
                    <td class="citam-ie-table__amt citam-ie-table__col--g-cur"><?= $currentAmt($amounts, $outflow) ?></td>
                    <td class="citam-ie-table__amt citam-ie-table__col--g-pri"><?= $priorAmt($amounts, $outflow) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer class="finance-statement__footer">
        <p class="finance-statement__disclaimer"><?= htmlspecialchars($statementDisclaimer ?? '') ?></p>
        <p class="finance-statement__signoff"><?= htmlspecialchars($churchName) ?> · Finance Office</p>
    </footer>
</div>
