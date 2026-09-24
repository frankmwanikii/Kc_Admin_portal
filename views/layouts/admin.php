<?php

use App\Services\FormSubmissionService;

$pageScripts = array_values(array_unique(array_merge(
    ['/js/admin-shell.js', '/js/admin-pagination.js'],
    $pageScripts ?? []
)));

$pendingMembers = 0;
try {
    $pendingMembers = FormSubmissionService::countByStatus('new');
} catch (Throwable) {
    $pendingMembers = 0;
}

$jumpLinks = [
    ['href' => '/admin', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'keywords' => 'home overview'],
    ['href' => '/admin/members', 'label' => 'Members', 'icon' => 'users', 'keywords' => 'people registrations'],
    ['href' => '/admin/staff', 'label' => 'Staff', 'icon' => 'id-card', 'keywords' => 'admins users'],
    ['href' => '/admin/inventory', 'label' => 'Inventory', 'icon' => 'package', 'keywords' => 'assets stock'],
    ['href' => '/admin/communications', 'label' => 'Communications', 'icon' => 'megaphone', 'keywords' => 'sms email birthdays'],
    ['href' => '/admin/finance?tab=dashboard', 'label' => 'Finance overview', 'icon' => 'pie-chart', 'keywords' => 'money giving'],
    ['href' => '/admin/finance?tab=ledger', 'label' => 'Sundays', 'icon' => 'calendar-days', 'keywords' => 'collections expenses record'],
    ['href' => '/admin/finance?tab=bills', 'label' => 'Bills', 'icon' => 'receipt', 'keywords' => 'arrears outstanding'],
    ['href' => '/admin/finance?tab=budget', 'label' => 'Budget', 'icon' => 'wallet', 'keywords' => 'plan actual'],
    ['href' => '/admin/finance?tab=reports&sub=statement', 'label' => 'Reports', 'icon' => 'file-bar-chart', 'keywords' => 'statement position pdf'],
    ['href' => '/admin/docs', 'label' => 'Docs', 'icon' => 'book-open', 'keywords' => 'help guide how-to'],
    ['href' => '/admin/settings', 'label' => 'Settings', 'icon' => 'settings', 'keywords' => 'church branding sms'],
    ['href' => '/admin/profile', 'label' => 'My profile', 'icon' => 'user-round', 'keywords' => 'account password avatar'],
];
$jumpLinksJson = json_encode($jumpLinks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <script>
    (function () {
        try {
            var key = 'kc-admin-theme';
            var stored = localStorage.getItem(key);
            var theme = (stored === 'light' || stored === 'dark')
                ? stored
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (theme === 'dark') document.documentElement.classList.add('dark');
            document.documentElement.dataset.adminTheme = theme;
        } catch (_) { /* ignore */ }
    })();
    </script>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <?php
    $adminCss = static function (string $path): string {
        $absolute = dirname(__DIR__, 2) . '/public' . $path;
        $v = is_file($absolute) ? (int) filemtime($absolute) : time();
        return htmlspecialchars($path . '?v=' . $v, ENT_QUOTES);
    };
    ?>
    <link rel="stylesheet" href="<?= $adminCss('/css/admin-responsive.css') ?>">
    <link rel="stylesheet" href="<?= $adminCss('/css/admin-sidebar.css') ?>">
    <link rel="stylesheet" href="<?= $adminCss('/css/admin-profile.css') ?>">
    <link rel="stylesheet" href="<?= $adminCss('/css/admin-theme.css') ?>">
    <link rel="stylesheet" href="<?= $adminCss('/css/app-copyright.css') ?>">
    <script type="application/json" id="admin-jump-links"><?= $jumpLinksJson ?></script>
</head>
<body class="h-full bg-slate-50 font-sans antialiased admin-app dark:bg-[#0b1c28]"
      :class="sidebarCollapsed && 'admin-app--sidebar-collapsed'"
      x-data="adminShell(JSON.parse(document.getElementById('admin-jump-links').textContent))">
    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false" class="fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

    <!-- Sidebar: brand + scrollable nav + pinned Sign out -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="admin-sidebar fixed lg:sticky lg:top-0 inset-y-0 left-0 z-50 transform lg:translate-x-0 transition-[width,transform] duration-200 flex flex-col shrink-0">
        <div class="admin-sidebar__brand shrink-0">
            <div class="admin-sidebar__brand-row">
                <?php $size = 'md'; $logoBg = 'white'; require __DIR__ . '/../partials/church-logo.php'; ?>
                <div class="admin-sidebar__brand-text min-w-0">
                    <p class="admin-sidebar__brand-name font-semibold text-sm leading-tight truncate"><?= htmlspecialchars(\App\Services\SettingsService::churchName()) ?></p>
                    <p class="admin-sidebar__brand-sub text-xs">Administration</p>
                </div>
                <button type="button"
                        class="admin-sidebar__collapse-btn hidden lg:inline-flex"
                        @click="toggleSidebarCollapse()"
                        :aria-expanded="!sidebarCollapsed"
                        :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                        :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                    <i data-lucide="panel-left-close" x-show="!sidebarCollapsed" class="w-4 h-4"></i>
                    <i data-lucide="panel-left-open" x-show="sidebarCollapsed" x-cloak class="w-4 h-4"></i>
                </button>
            </div>
        </div>
        <nav class="admin-sidebar__nav flex-1 min-h-0 overflow-y-auto overscroll-contain">
            <?php require __DIR__ . '/../partials/admin-nav.php'; ?>
        </nav>
        <div class="admin-sidebar__footer shrink-0">
            <a href="/logout"
               class="admin-side-nav__link admin-side-nav__link--muted"
               title="Sign out">
                <span class="admin-side-nav__icon">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </span>
                <span class="admin-side-nav__label">Sign out</span>
            </a>
        </div>
    </aside>

    <div class="admin-main flex-1 min-w-0 min-h-0">
        <?php
        $headerUser = \App\Core\Auth::user();
        if ($headerUser) {
            \App\Models\User::ensureProfileColumns();
            $headerUser = \App\Core\Auth::user() ?? $headerUser;
        }
        $headerAvatar = $headerUser?->avatarUrl();
        $headerName = $headerUser?->fullName() ?? 'Admin';
        $headerInitials = $headerUser?->initials() ?? 'A';
        $headerEmail = $headerUser?->email ?? '';
        $isMacHint = false;
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (preg_match('/Mac|iPhone|iPad|iPod/i', $ua)) {
            $isMacHint = true;
        }
        ?>
        <header class="admin-topbar sticky top-0 z-30 flex flex-col shrink-0 border-b border-slate-200/80 bg-white/90 backdrop-blur-lg dark:border-slate-700/60 dark:bg-[#083552]/90">
            <div class="admin-topbar__row grid h-14 sm:h-16 w-full grid-cols-[minmax(0,1fr)_auto] sm:grid-cols-[minmax(0,1fr)_minmax(12rem,28rem)_minmax(0,1fr)] items-center gap-2 sm:gap-4 px-3 sm:px-5">
                <div class="admin-topbar__left flex min-w-0 items-center gap-2 justify-self-start">
                    <button type="button"
                            class="admin-topbar__menu-btn inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10 lg:hidden"
                            @click="sidebarOpen = true"
                            aria-label="Open navigation">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="admin-topbar__center hidden sm:flex w-full min-w-0 justify-self-center">
                    <div class="admin-topbar__search relative w-full max-w-xl"
                         @click.outside="searchOpen = false"
                         @keydown.escape.window="searchOpen = false">
                        <label class="admin-topbar__search-label relative flex items-center">
                            <span class="sr-only">Jump to section</span>
                            <i data-lucide="search" class="admin-topbar__search-icon pointer-events-none absolute left-3.5 h-4 w-4 text-slate-400"></i>
                            <input type="search"
                                   x-ref="searchDesktop"
                                   class="admin-topbar__search-input h-10 w-full rounded-full border-0 bg-slate-100 pl-10 pr-14 text-sm text-slate-900 outline-none ring-1 ring-inset ring-slate-200/80 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-church-400/50 dark:bg-slate-950/60 dark:text-slate-50 dark:ring-slate-600 dark:placeholder:text-slate-500 dark:focus:bg-slate-950"
                                   placeholder="Search..."
                                   autocomplete="off"
                                   x-model="searchTerm"
                                   @focus="searchOpen = true; searchActive = 0"
                                   @input="searchOpen = true; searchActive = 0"
                                   @keydown="onSearchKeydown($event)">
                            <kbd class="admin-topbar__kbd pointer-events-none absolute right-3 hidden select-none rounded-md bg-white px-1.5 py-0.5 font-sans text-[10px] font-semibold tracking-wide text-slate-400 ring-1 ring-slate-200 sm:inline-block dark:bg-slate-900 dark:text-slate-500 dark:ring-slate-700"><?= $isMacHint ? '⌘K' : 'Ctrl K' ?></kbd>
                        </label>
                        <div class="admin-topbar__search-panel absolute left-0 right-0 top-[calc(100%+0.45rem)] z-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-600 dark:bg-church-800"
                             x-show="searchOpen"
                             x-cloak
                             x-transition.opacity
                             role="listbox"
                             aria-label="Jump to">
                            <template x-for="(item, index) in filteredJumpLinks" :key="item.href">
                                <a :href="item.href"
                                   class="admin-topbar__search-item flex w-full items-center gap-2.5 px-3.5 py-2.5 text-sm font-medium text-slate-700 hover:bg-church-50 dark:text-slate-100 dark:hover:bg-white/10"
                                   :class="searchActive === index && 'admin-topbar__search-item--active bg-church-50 dark:bg-white/10'"
                                   role="option"
                                   @mouseenter="searchActive = index"
                                   @click="searchOpen = false; searchTerm = ''">
                                    <i :data-lucide="item.icon" class="h-4 w-4 shrink-0 text-slate-400"></i>
                                    <span x-text="item.label"></span>
                                </a>
                            </template>
                            <p class="admin-topbar__search-hint m-0 border-t border-slate-100 px-3.5 py-2 text-xs text-slate-400 dark:border-slate-600"
                               x-show="filteredJumpLinks.length === 0"
                               x-cloak>No matching pages.</p>
                            <p class="admin-topbar__search-hint m-0 border-t border-slate-100 px-3.5 py-2 text-xs text-slate-400 dark:border-slate-600" x-show="filteredJumpLinks.length > 0">
                                Jump to a section — Enter to open
                            </p>
                        </div>
                    </div>
                </div>

                <div class="admin-topbar__right flex items-center justify-end gap-1.5 sm:gap-2 justify-self-end col-start-2 sm:col-start-auto">
                    <a href="/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="admin-topbar__icon-btn relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100 hover:text-church-800 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
                       title="View portal / website"
                       aria-label="Open public portal">
                        <i data-lucide="external-link" class="w-[18px] h-[18px]"></i>
                    </a>

                    <div class="admin-theme-toggle relative inline-grid h-9 shrink-0 grid-cols-2 items-center rounded-full bg-slate-100 p-0.5 ring-1 ring-inset ring-slate-200/80 dark:bg-slate-950 dark:ring-slate-600"
                         role="group"
                         aria-label="Color theme">
                        <span class="admin-theme-toggle__thumb pointer-events-none absolute left-0.5 top-0.5 h-8 w-8 rounded-full bg-white shadow transition-transform duration-300 ease-out dark:bg-church-500"
                              :class="isDark && 'admin-theme-toggle__thumb--dark translate-x-8'"
                              aria-hidden="true"></span>
                        <button type="button"
                                class="admin-theme-toggle__btn relative z-10 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition-colors"
                                :class="!isDark ? 'admin-theme-toggle__btn--active text-church-800' : 'hover:text-slate-300'"
                                @click="setTheme('light')"
                                aria-label="Light mode"
                                :aria-pressed="!isDark">
                            <i data-lucide="sun" class="h-3.5 w-3.5"></i>
                        </button>
                        <button type="button"
                                class="admin-theme-toggle__btn relative z-10 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition-colors"
                                :class="isDark ? 'admin-theme-toggle__btn--active text-church-900' : 'hover:text-slate-800'"
                                @click="setTheme('dark')"
                                aria-label="Dark mode"
                                :aria-pressed="isDark">
                            <i data-lucide="moon" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>

                    <a href="/admin/members"
                       class="admin-topbar__icon-btn relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100 hover:text-church-800 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white"
                       title="Members awaiting review"
                       aria-label="Members awaiting review">
                        <i data-lucide="bell" class="w-[18px] h-[18px]"></i>
                        <?php if ($pendingMembers > 0): ?>
                        <span class="admin-topbar__badge absolute top-0.5 right-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-[#083552]"><?= $pendingMembers > 99 ? '99+' : (int) $pendingMembers ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="admin-profile relative shrink-0" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button type="button"
                                class="admin-profile__trigger inline-flex rounded-full p-0.5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-church-400"
                                @click="open = !open"
                                :aria-expanded="open"
                                aria-haspopup="menu"
                                aria-label="Account menu">
                            <span class="admin-profile__avatar inline-flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-church-800 to-church-500 text-xs font-bold text-white">
                                <?php if ($headerAvatar): ?>
                                <img src="<?= htmlspecialchars($headerAvatar) ?>" alt="" class="h-full w-full object-cover">
                                <?php else: ?>
                                <span><?= htmlspecialchars($headerInitials) ?></span>
                                <?php endif; ?>
                            </span>
                        </button>

                        <div class="admin-profile__menu absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-church-800"
                             x-show="open"
                             x-cloak
                             x-transition
                             @click.outside="open = false"
                             role="menu">
                            <div class="admin-profile__menu-head border-b border-slate-100 px-3 py-2.5 dark:border-slate-600">
                                <p class="admin-profile__menu-name m-0 truncate text-sm font-semibold text-slate-900 dark:text-slate-50"><?= htmlspecialchars($headerName) ?></p>
                                <?php if ($headerEmail !== ''): ?>
                                <p class="admin-profile__menu-email m-0 mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($headerEmail) ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="/admin/profile" class="admin-profile__item flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-slate-100 dark:hover:bg-white/10" role="menuitem" @click="open = false">
                                <i data-lucide="user-round" class="w-4 h-4"></i>
                                My profile
                            </a>
                            <a href="/admin/settings" class="admin-profile__item flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-slate-100 dark:hover:bg-white/10" role="menuitem" @click="open = false">
                                <i data-lucide="settings" class="w-4 h-4"></i>
                                Church settings
                            </a>
                            <div class="admin-profile__divider my-1 h-px bg-slate-100 dark:bg-slate-600"></div>
                            <a href="/logout" class="admin-profile__item admin-profile__item--danger flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-rose-300 dark:hover:bg-rose-500/10" role="menuitem">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                Sign out
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-topbar__mobile-search border-t border-slate-100 px-3 pb-3 pt-0 sm:hidden dark:border-slate-700">
                <div class="admin-topbar__search relative w-full"
                     @click.outside="searchOpen = false">
                    <label class="admin-topbar__search-label relative flex items-center">
                        <span class="sr-only">Jump to section</span>
                        <i data-lucide="search" class="admin-topbar__search-icon pointer-events-none absolute left-3.5 h-4 w-4 text-slate-400"></i>
                        <input type="search"
                               x-ref="searchMobile"
                               class="admin-topbar__search-input h-10 w-full rounded-full border-0 bg-slate-100 pl-10 pr-4 text-sm text-slate-900 outline-none ring-1 ring-inset ring-slate-200/80 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-church-400/50 dark:bg-slate-950/60 dark:text-slate-50 dark:ring-slate-600"
                               placeholder="Search..."
                               autocomplete="off"
                               x-model="searchTerm"
                               @focus="searchOpen = true; searchActive = 0"
                               @input="searchOpen = true; searchActive = 0"
                               @keydown="onSearchKeydown($event)">
                    </label>
                    <div class="admin-topbar__search-panel absolute left-0 right-0 top-[calc(100%+0.45rem)] z-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-600 dark:bg-church-800"
                         x-show="searchOpen"
                         x-cloak
                         x-transition.opacity
                         role="listbox">
                        <template x-for="(item, index) in filteredJumpLinks" :key="'m-' + item.href">
                            <a :href="item.href"
                               class="admin-topbar__search-item flex w-full items-center gap-2.5 px-3.5 py-2.5 text-sm font-medium text-slate-700 hover:bg-church-50 dark:text-slate-100 dark:hover:bg-white/10"
                               :class="searchActive === index && 'admin-topbar__search-item--active bg-church-50 dark:bg-white/10'"
                               @mouseenter="searchActive = index"
                               @click="searchOpen = false; searchTerm = ''">
                                <i :data-lucide="item.icon" class="h-4 w-4 shrink-0 text-slate-400"></i>
                                <span x-text="item.label"></span>
                            </a>
                        </template>
                        <p class="admin-topbar__search-hint m-0 border-t border-slate-100 px-3.5 py-2 text-xs text-slate-400 dark:border-slate-600" x-show="filteredJumpLinks.length === 0" x-cloak>No matching pages.</p>
                    </div>
                </div>
            </div>
        </header>
        <main class="p-3 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full flex-1">
            <?= $content ?>
        </main>
        <?php $copyrightVariant = 'app-copyright--admin'; require __DIR__ . '/../partials/app-copyright.php'; ?>
    </div>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
    lucide.createIcons();
    // Re-paint Lucide icons when Alpine updates dynamic search results
    document.addEventListener('alpine:initialized', () => {
        const refresh = () => window.lucide?.createIcons();
        document.body.addEventListener('click', refresh);
    });
    </script>
    <script src="/js/admin-nav-ajax.js?v=<?= (int) @filemtime(dirname(__DIR__, 2) . '/public/js/admin-nav-ajax.js') ?>"></script>
</body>
</html>
