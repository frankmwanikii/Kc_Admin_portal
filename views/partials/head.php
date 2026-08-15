<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0b486d">
<title><?= htmlspecialchars($title ?? 'Church MIS') ?> — <?= htmlspecialchars(\App\Services\SettingsService::churchName()) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                church: {
                    50:  '#eef9fd',
                    100: '#d6f0fa',
                    200: '#ade2f5',
                    300: '#7dd0ef',
                    400: '#4dbfe9',
                    500: '#35afe6',
                    600: '#2da0d9',
                    700: '#1a7aab',
                    800: '#0b486d',
                    900: '#083552',
                },
                gold: { 400:'#fbbf24',500:'#f59e0b' }
            },
            fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
        }
    }
}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php foreach ($pageStyles ?? [] as $href): ?>
<link rel="stylesheet" href="<?= htmlspecialchars((string) $href) ?>">
<?php endforeach; ?>
<style>
    [x-cloak] { display: none !important; }
    .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 0); }
</style>
<?php foreach ($pageScripts ?? [] as $src): ?>
<?php
    $src = (string) $src;
    if (str_starts_with($src, '/') && !str_contains($src, '?')) {
        $absolute = dirname(__DIR__, 2) . '/public' . $src;
        if (is_file($absolute)) {
            $src .= '?v=' . (int) filemtime($absolute);
        }
    }
?>
<script defer src="<?= htmlspecialchars($src) ?>"></script>
<?php endforeach; ?>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
