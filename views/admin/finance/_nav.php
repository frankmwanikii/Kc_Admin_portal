<?php
/** @var string $tab */
/** @var int $year */
/** @var string $month */
$tabDashboard = ($tab ?? 'dashboard') === 'dashboard';
$tabBills = ($tab ?? '') === 'bills' || ($tab ?? '') === 'arrears';
$tabLedger = ($tab ?? '') === 'ledger' || ($tab ?? '') === 'weekly' || ($tab ?? '') === 'collections';
$tabReports = ($tab ?? '') === 'reports' || ($tab ?? '') === 'reconciliation' || ($tab ?? '') === 'statement';
$reportSub = $reportSub ?? (($_GET['sub'] ?? '') === 'statement' ? 'statement' : 'reconciliation');
$ledgerSub = $ledgerSub ?? (($_GET['sub'] ?? '') === 'collections' ? 'collections' : 'expenses');
?>
<header class="fin-hero no-print">
    <div class="fin-hero__text">
        <p class="fin-hero__eyebrow">Kingdom City Finance</p>
        <h2 class="fin-hero__title">Church finances, simplified</h2>
        <p class="fin-hero__sub">Record Sunday giving and expenses in one place. Everything links automatically.</p>
    </div>
    <div class="fin-hero__actions">
        <a href="/admin/finance/sunday?month=<?= htmlspecialchars($month) ?>" class="fin-btn fin-btn--primary fin-btn--lg">
            <i data-lucide="calendar-plus" class="w-5 h-5"></i>
            Record Sunday
        </a>
        <form method="get" class="fin-year-form">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <?php if ($tabLedger || ($tabReports && $reportSub === 'statement')): ?>
            <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
            <?php endif; ?>
            <?php if ($tabReports && $reportSub === 'statement'): ?>
            <input type="hidden" name="sub" value="statement">
            <input type="hidden" name="view" value="<?= htmlspecialchars($_GET['view'] ?? 'monthly') ?>">
            <?php elseif ($tabReports): ?>
            <input type="hidden" name="sub" value="reconciliation">
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

<nav class="fin-tabs no-print" role="tablist" aria-label="Finance sections">
    <a href="/admin/finance?tab=dashboard&year=<?= (int) $year ?>"
       class="fin-tabs__item <?= $tabDashboard ? 'fin-tabs__item--active' : '' ?>"
       role="tab"
       <?= $tabDashboard ? 'aria-selected="true"' : '' ?>>
        <i data-lucide="layout-dashboard" class="fin-tabs__icon"></i>
        <span class="fin-tabs__label">Dashboard</span>
    </a>
    <a href="/admin/finance?tab=bills&year=<?= (int) $year ?>"
       class="fin-tabs__item <?= $tabBills ? 'fin-tabs__item--active' : '' ?>"
       role="tab">
        <i data-lucide="receipt" class="fin-tabs__icon"></i>
        <span class="fin-tabs__label">Bills</span>
    </a>
    <a href="/admin/finance?tab=ledger&year=<?= (int) $year ?>&month=<?= htmlspecialchars($month) ?>"
       class="fin-tabs__item <?= $tabLedger ? 'fin-tabs__item--active' : '' ?>"
       role="tab">
        <i data-lucide="table-2" class="fin-tabs__icon"></i>
        <span class="fin-tabs__label">Ledger</span>
    </a>
    <a href="/admin/finance?tab=reports&year=<?= (int) $year ?>&month=<?= htmlspecialchars($month) ?>"
       class="fin-tabs__item <?= $tabReports ? 'fin-tabs__item--active' : '' ?>"
       role="tab">
        <i data-lucide="file-bar-chart" class="fin-tabs__icon"></i>
        <span class="fin-tabs__label">Reports</span>
    </a>
</nav>
