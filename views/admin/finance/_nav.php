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
if ($tabKey === 'statement') {
    $tabKey = 'reports';
}
// Legacy: reports&sub=budget → Budget tab (controller remaps; keep hero in sync)
$reportSubKey = strtolower((string) ($reportSub ?? ($_GET['sub'] ?? '')));
if ($tabKey === 'reports' && $reportSubKey === 'budget') {
    $tabKey = 'budget';
    $reportSubKey = '';
}
if ($tabKey === 'reports' && !in_array($reportSubKey, ['statement', 'position'], true)) {
    $reportSubKey = 'statement';
}

$budgetEditMode = !empty($budgetEditMode);

$sectionMeta = match ($tabKey) {
    'bills' => [
        'title' => 'Bills',
        'sub' => 'Track what the church owes — paid amounts and balances still due.',
    ],
    'ledger' => [
        'title' => 'Sundays',
        'sub' => 'Collections and expenses for each Sunday service.',
    ],
    'budget' => [
        'title' => $budgetEditMode ? 'Set Budget' : 'Budget',
        'sub' => $budgetEditMode
            ? 'Enter planned income and expenses for the focus month.'
            : 'Plan the month, then compare against Sunday collections and spending.',
    ],
    'reports' => [
        'title' => 'Reports',
        'sub' => match ($reportSubKey) {
            'position' => 'Year-over-year consolidated income and expenditure.',
            default => 'Operating statements and annual income & expenditure.',
        },
    ],
    default => [
        'title' => 'Finance overview',
        'sub' => 'Record Sunday giving and expenses in one place. Everything links automatically.',
    ],
};
?>
<?php if (!($tabKey === 'budget' && $budgetEditMode)): ?>
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
<?php endif; ?>
