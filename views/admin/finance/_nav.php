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
if ($tabKey === 'statement') {
    $tabKey = 'reports';
}

$sectionMeta = match ($tabKey) {
    'bills' => [
        'title' => 'Bills',
        'sub' => 'Track what the church owes — paid amounts and balances still due.',
    ],
    'ledger' => [
        'title' => 'Ledger',
        'sub' => 'Weekly expenses and collections for each Sunday.',
    ],
    'reconciliation' => [
        'title' => 'Reconciliation',
        'sub' => 'Compare collections against expenses for the month.',
    ],
    'budget' => [
        'title' => 'Budget',
        'sub' => 'Set planned amounts and track budget vs actual.',
    ],
    'reports' => [
        'title' => 'Reports',
        'sub' => 'Generate and download financial statements.',
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
</header>
