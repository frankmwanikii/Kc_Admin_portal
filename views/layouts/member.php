<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <link rel="stylesheet" href="/css/app-copyright.css">
</head>
<body class="h-full bg-slate-50 font-sans antialiased" x-data="{ menuOpen: false }">
    <!-- Top bar -->
    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-lg border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-14 sm:h-16">
            <a href="/portal" class="flex items-center gap-2.5 min-w-0">
                <?php $size = 'sm'; $variant = 'dark'; require __DIR__ . '/../partials/church-logo.php'; ?>
                <span class="font-semibold text-church-800 text-sm sm:text-base truncate hidden sm:block"><?= htmlspecialchars(\App\Services\SettingsService::churchName()) ?></span>
            </a>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-600 hidden sm:block"><?= htmlspecialchars($member->fullName() ?? '') ?></span>
                <a href="/logout" class="text-sm text-slate-500 hover:text-red-600 transition">Sign out</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto flex">
        <!-- Sidebar (desktop) -->
        <nav class="hidden lg:block w-64 shrink-0 p-6">
            <?php require __DIR__ . '/../partials/member-nav.php'; ?>
        </nav>

        <!-- Mobile bottom nav -->
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-slate-200 safe-bottom">
            <div class="flex justify-around py-2">
                <?php
                $nav = [
                    ['/portal', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['/portal/giving', 'Giving', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['/portal/pledges', 'Pledges', 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                    ['/portal/profile', 'Profile', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ];
                $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
                foreach ($nav as [$href, $label, $icon]):
                    $active = $current === $href;
                ?>
                <a href="<?= $href ?>" class="flex flex-col items-center gap-0.5 px-3 py-1 <?= $active ? 'text-church-600' : 'text-slate-400' ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $icon ?>"/></svg>
                    <span class="text-[10px] font-medium"><?= $label ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </nav>

        <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8 min-w-0">
            <?= $content ?>
        </main>
    </div>
    <?php $copyrightVariant = 'app-copyright--member'; require __DIR__ . '/../partials/app-copyright.php'; ?>
</body>
</html>
