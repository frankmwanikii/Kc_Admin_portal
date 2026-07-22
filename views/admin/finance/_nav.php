<?php
/** @var string $tab */
/** @var int $year */
/** @var string $month */
$tabKey = $tab ?? 'dashboard';
if ($tabKey === 'arrears') {
    $tabKey = 'bills';
}
if (in_array($tabKey, ['weekly', 'collections'], true)) {
    $tabKey = 'ledger';
}
if (in_array($tabKey, ['reconciliation', 'statement'], true)) {
    $tabKey = 'reports';
}

$tabDashboard = $tabKey === 'dashboard';
$tabBills = $tabKey === 'bills';
$tabLedger = $tabKey === 'ledger';
$tabReports = $tabKey === 'reports';
$reportSub = $reportSub ?? (($_GET['sub'] ?? '') === 'statement' ? 'statement' : 'reconciliation');
$ledgerSub = $ledgerSub ?? (($_GET['sub'] ?? '') === 'collections' ? 'collections' : 'expenses');

$sectionMeta = match ($tabKey) {
    'bills' => [
        'title' => 'Bills',
        'sub' => 'Track what the church owes — paid amounts and balances still due.',
    ],
    'ledger' => [
        'title' => 'Ledger',
        'sub' => 'Weekly expenses and collections for each Sunday.',
    ],
    'reports' => [
        'title' => 'Reports',
        'sub' => 'Reconciliation, statements, and budget vs actual.',
    ],
    default => [
        'title' => 'Finance overview',
        'sub' => 'Record Sunday giving and expenses in one place. Everything links automatically.',
    ],
};
?>
<header class="fin-hero no-print">
    <div class="fin-hero__text">
        <p class="fin-hero__eyebrow">Finance</p>
        <h2 class="fin-hero__title"><?= htmlspecialchars($sectionMeta['title']) ?></h2>
        <p class="fin-hero__sub"><?= htmlspecialchars($sectionMeta['sub']) ?></p>
    </div>
    <div class="fin-hero__actions">
        <a href="/admin/finance/sunday?month=<?= htmlspecialchars($month) ?>" class="fin-btn fin-btn--primary fin-btn--lg">
            <i data-lucide="calendar-plus" class="w-5 h-5"></i>
            Record Sunday
        </a>
        <form method="get" class="fin-year-form">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tabKey) ?>">
            <?php if ($tabLedger || ($tabReports && $reportSub === 'statement')): ?>
            <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
            <?php endif; ?>
            <?php if ($tabReports && $reportSub === 'statement'): ?>
            <input type="hidden" name="sub" value="statement">
            <input type="hidden" name="view" value="<?= htmlspecialchars($_GET['view'] ?? 'monthly') ?>">
            <?php elseif ($tabReports): ?>
            <input type="hidden" name="sub" value="<?= htmlspecialchars($reportSub) ?>">
            <?php endif; ?>
            <?php if ($tabLedger): ?>
            <input type="hidden" name="sub" value="<?= htmlspecialchars($ledgerSub) ?>">
            <?php endif; ?>
            <select name="year" onchange="this.form.submit()" class="fin-select fin-select--hero" aria-label="Budget year">
                <?php for ($y = (int) date('Y') + 1; $y >= 2024; $y--): ?>
                <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
</header>
