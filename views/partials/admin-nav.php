<?php
$links = [
    ['/admin', 'Dashboard', 'layout-dashboard'],
    ['/admin/members', 'Members', 'users'],
    ['/admin/inventory', 'Inventory', 'package'],
    ['/admin/finance', 'Finance', 'wallet'],
    ['/admin/communications', 'Communications', 'megaphone'],
    ['/admin/settings', 'Settings', 'settings'],
];
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
foreach ($links as [$href, $label, $icon]):
    $active = $current === $href || (str_starts_with($current, $href) && $href !== '/admin');
?>
<a href="<?= $href ?>" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?= $active ? 'bg-white/15 text-white shadow-sm shadow-black/10' : 'text-white/55 hover:bg-white/10 hover:text-white' ?>">
    <span class="flex items-center justify-center w-9 h-9 rounded-lg shrink-0 transition-colors <?= $active ? 'bg-white/20 text-white' : 'bg-white/5 text-white/70 group-hover:bg-white/10 group-hover:text-white' ?>">
        <i data-lucide="<?= $icon ?>" class="w-[18px] h-[18px]"></i>
    </span>
    <span class="truncate"><?= $label ?></span>
</a>
<?php endforeach; ?>
