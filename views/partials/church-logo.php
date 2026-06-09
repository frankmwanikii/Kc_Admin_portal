<?php

use App\Services\SettingsService;

$size = $size ?? 'md';
$logoUrl = SettingsService::logoUrl();

$sizes = [
    'sm' => ['box' => 'w-8 h-8', 'img' => 'w-8 h-8', 'icon' => 'w-4 h-4'],
    'md' => ['box' => 'w-10 h-10', 'img' => 'w-10 h-10', 'icon' => 'w-5 h-5'],
    'lg' => ['box' => 'w-14 h-14', 'img' => 'w-14 h-14', 'icon' => 'w-7 h-7'],
    'xl' => ['box' => 'w-16 h-16', 'img' => 'w-16 h-16', 'icon' => 'w-8 h-8'],
];
$s = $sizes[$size] ?? $sizes['md'];
$rounded = $rounded ?? 'rounded-xl';
$variant = $variant ?? 'dark';
$logoBg = $logoBg ?? 'default';
$boxClass = $variant === 'light'
    ? 'bg-church-50 text-church-600'
    : 'bg-gradient-to-br from-church-500 to-church-800 text-white shadow-lg shadow-church-800/30';
$imgWrapClass = match ($logoBg) {
    'white' => 'bg-white p-1.5 shadow-sm ring-1 ring-white/20',
    default => $imgClass ?? 'bg-white/10',
};
?>
<?php if ($logoUrl): ?>
<img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars(SettingsService::churchName()) ?>"
     class="<?= $s['img'] ?> <?= $rounded ?> object-contain shrink-0 <?= is_string($imgWrapClass) ? $imgWrapClass : '' ?>">
<?php else: ?>
<div class="<?= $s['box'] ?> <?= $rounded ?> <?= $boxClass ?> flex items-center justify-center shrink-0">
    <svg class="<?= $s['icon'] ?>" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 8h4m-4 4h4m-5 8h6a2 2 0 002-2V6.414a1 1 0 00-.293-.707l-3.414-3.414A1 1 0 0012.586 2H11.414a1 1 0 00-.707.293L7.293 5.707A1 1 0 007 6.414V18a2 2 0 002 2z"/>
    </svg>
</div>
<?php endif; ?>
