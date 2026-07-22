<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <link rel="stylesheet" href="/css/admin-responsive.css">
    <link rel="stylesheet" href="/css/admin-sidebar.css">
</head>
<body class="min-h-full bg-slate-50 font-sans antialiased lg:flex admin-app" x-data="{ sidebarOpen: false }">
    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false" class="fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="admin-sidebar fixed lg:sticky lg:top-0 inset-y-0 left-0 z-50 w-72 bg-church-900 text-white transform lg:translate-x-0 transition-transform duration-200 flex flex-col shrink-0 min-h-screen">
        <div class="admin-sidebar__brand p-5 border-b border-white/10">
            <div class="flex items-center gap-3">
                <?php $size = 'md'; $logoBg = 'white'; require __DIR__ . '/../partials/church-logo.php'; ?>
                <div class="min-w-0">
                    <p class="font-semibold text-sm leading-tight truncate"><?= htmlspecialchars(\App\Services\SettingsService::churchName()) ?></p>
                    <p class="text-xs text-white/50">Administration</p>
                </div>
            </div>
        </div>
        <nav class="admin-sidebar__nav flex-1 p-3 overflow-y-auto">
            <?php require __DIR__ . '/../partials/admin-nav.php'; ?>
        </nav>
        <div class="admin-sidebar__footer p-3 border-t border-white/10">
            <a href="/logout" class="admin-side-nav__link admin-side-nav__link--muted">
                <span class="admin-side-nav__icon">
                    <i data-lucide="log-out" class="w-[18px] h-[18px]"></i>
                </span>
                <span class="admin-side-nav__label">Sign out</span>
            </a>
        </div>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-lg border-b border-slate-200/80 px-4 sm:px-6 h-14 sm:h-16 flex items-center gap-4">
            <button @click="sidebarOpen=true" class="lg:hidden p-2 -ml-2 text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
            <h1 class="text-lg font-semibold text-church-800 truncate"><?= htmlspecialchars($title ?? '') ?></h1>
        </header>
        <main class="p-3 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
            <?= $content ?>
        </main>
    </div>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
