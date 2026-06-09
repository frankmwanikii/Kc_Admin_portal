<?php
$links = [
    ['/portal', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['/portal/giving', 'Giving History', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['/portal/pledges', 'Pledge Tracker', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['/portal/profile', 'My Profile', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
?>
<div class="space-y-1">
    <?php foreach ($links as [$href, $label, $icon]): ?>
    <a href="<?= $href ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $current === $href ? 'bg-church-50 text-church-700' : 'text-slate-600 hover:bg-slate-100' ?>">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $icon ?>"/></svg>
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>
