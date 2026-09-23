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
if ($tabKey === 'reconciliation') {
    $tabKey = 'dashboard';
}
if ($tabKey === 'budget') {
    $tabKey = 'reports';
}
if ($tabKey === 'statement') {
    $tabKey = 'reports';
}

$reportSubKey = strtolower((string) ($reportSub ?? ($_GET['sub'] ?? '')));
if ($tabKey === 'reports' && !in_array($reportSubKey, ['statement', 'position', 'budget'], true)) {
    $reportSubKey = 'statement';
}

$sectionMeta = match ($tabKey) {
    'bills' => [
        'title' => 'Bills',
        'sub' => 'Track what the church owes — paid amounts and balances still due.',
    ],
    'ledger' => [
        'title' => 'Sundays',
        'sub' => 'Collections and expenses for each Sunday service.',
    ],
    'reports' => [
        'title' => 'Reports',
        'sub' => match ($reportSubKey) {
            'position' => 'Year-over-year consolidated income and expenditure.',
            'budget' => 'Planned amounts versus actual collections and spending.',
            default => 'Operating statements, annual position, and budget vs actual.',
        },
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
    <?php if ($tabKey === 'dashboard' || $tabKey === 'ledger'): ?>
    <div class="fin-hero__actions">
        <a href="/admin/finance/sunday?month=<?= htmlspecialchars(urlencode($month ?? date('Y-m'))) ?>&amp;return_tab=<?= $tabKey === 'ledger' ? 'ledger' : 'dashboard' ?>"
           class="fin-btn fin-btn--primary">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Record
        </a>
    </div>
    <?php endif; ?>
</header>
