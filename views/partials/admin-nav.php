<?php

use App\Services\FormSubmissionService;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$financeTab = strtolower((string) ($_GET['tab'] ?? 'dashboard'));
if (in_array($financeTab, ['arrears'], true)) {
    $financeTab = 'bills';
}
if (in_array($financeTab, ['weekly', 'collections'], true)) {
    $financeTab = 'ledger';
}
if (in_array($financeTab, ['reconciliation', 'statement'], true)) {
    $financeTab = 'reports';
}
if ($currentPath === '/admin/finance' && $financeTab === '') {
    $financeTab = 'dashboard';
}

$newMembers = 0;
try {
    $newMembers = FormSubmissionService::countByStatus('new');
} catch (Throwable) {
    $newMembers = 0;
}

$sections = [
    'Overview' => [
        [
            'href' => '/admin',
            'label' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'active' => $currentPath === '/admin',
        ],
    ],
    'Management' => [
        [
            'href' => '/admin/members',
            'label' => 'Members',
            'icon' => 'users',
            'active' => str_starts_with($currentPath, '/admin/members'),
            'badge' => $newMembers > 0 ? $newMembers : null,
        ],
        [
            'href' => '/admin/staff',
            'label' => 'Staff',
            'icon' => 'id-card',
            'active' => str_starts_with($currentPath, '/admin/staff'),
        ],
        [
            'href' => '/admin/inventory',
            'label' => 'Inventory',
            'icon' => 'package',
            'active' => str_starts_with($currentPath, '/admin/inventory'),
        ],
        [
            'href' => '/admin/communications',
            'label' => 'Communications',
            'icon' => 'megaphone',
            'active' => str_starts_with($currentPath, '/admin/communications'),
        ],
    ],
    'Finance' => [
        [
            'href' => '/admin/finance?tab=dashboard',
            'label' => 'Overview',
            'icon' => 'pie-chart',
            'active' => $currentPath === '/admin/finance' && $financeTab === 'dashboard',
        ],
        [
            'href' => '/admin/finance/sunday',
            'label' => 'Record Sunday',
            'icon' => 'calendar-plus',
            'active' => str_starts_with($currentPath, '/admin/finance/sunday'),
        ],
        [
            'href' => '/admin/finance?tab=bills',
            'label' => 'Bills',
            'icon' => 'receipt',
            'active' => $currentPath === '/admin/finance' && $financeTab === 'bills',
        ],
        [
            'href' => '/admin/finance?tab=ledger',
            'label' => 'Ledger',
            'icon' => 'table-2',
            'active' => $currentPath === '/admin/finance' && $financeTab === 'ledger',
        ],
        [
            'href' => '/admin/finance?tab=reports',
            'label' => 'Reports',
            'icon' => 'file-bar-chart',
            'active' => $currentPath === '/admin/finance' && $financeTab === 'reports',
        ],
    ],
    'System' => [
        [
            'href' => '/admin/settings',
            'label' => 'Settings',
            'icon' => 'settings',
            'active' => str_starts_with($currentPath, '/admin/settings'),
        ],
    ],
];
?>
<div class="admin-side-nav">
<?php foreach ($sections as $sectionLabel => $links): ?>
    <div class="admin-side-nav__section">
        <p class="admin-side-nav__heading"><?= htmlspecialchars($sectionLabel) ?></p>
        <div class="admin-side-nav__list">
            <?php foreach ($links as $link):
                $active = !empty($link['active']);
                $badge = $link['badge'] ?? null;
            ?>
            <a href="<?= htmlspecialchars($link['href']) ?>"
               class="admin-side-nav__link <?= $active ? 'admin-side-nav__link--active' : '' ?>">
                <span class="admin-side-nav__icon">
                    <i data-lucide="<?= htmlspecialchars($link['icon']) ?>" class="w-[18px] h-[18px]"></i>
                </span>
                <span class="admin-side-nav__label"><?= htmlspecialchars($link['label']) ?></span>
                <?php if ($badge !== null): ?>
                <span class="admin-side-nav__badge"><?= (int) $badge > 99 ? '99+' : (int) $badge ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
