<?php
$pageScripts = array_values(array_unique(array_merge(
    ['/js/admin-pagination.js'],
    $pageScripts ?? []
)));
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <link rel="stylesheet" href="/css/admin-responsive.css">
    <link rel="stylesheet" href="/css/admin-sidebar.css">
    <link rel="stylesheet" href="/css/admin-profile.css">
    <link rel="stylesheet" href="/css/app-copyright.css">
</head>
<body class="h-full bg-slate-50 font-sans antialiased admin-app"
      :class="sidebarCollapsed && 'admin-app--sidebar-collapsed'"
      x-data="{
          sidebarOpen: false,
          sidebarCollapsed: false,
          init() {
              try {
                  this.sidebarCollapsed = localStorage.getItem('adminSidebarCollapsed') === '1';
              } catch (_) {
                  this.sidebarCollapsed = false;
              }
              this.$nextTick(() => window.lucide?.createIcons());
          },
          toggleSidebarCollapse() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              try {
                  localStorage.setItem('adminSidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
              } catch (_) { /* ignore */ }
              this.$nextTick(() => window.lucide?.createIcons());
          }
      }">
    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false" class="fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

    <!-- Sidebar: brand + scrollable nav + pinned Sign out -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="admin-sidebar fixed lg:sticky lg:top-0 inset-y-0 left-0 z-50 bg-church-900 text-white transform lg:translate-x-0 transition-[width,transform] duration-200 flex flex-col shrink-0">
        <div class="admin-sidebar__brand shrink-0">
            <div class="admin-sidebar__brand-row">
                <?php $size = 'md'; $logoBg = 'white'; require __DIR__ . '/../partials/church-logo.php'; ?>
                <div class="admin-sidebar__brand-text min-w-0">
                    <p class="font-semibold text-sm leading-tight truncate"><?= htmlspecialchars(\App\Services\SettingsService::churchName()) ?></p>
                    <p class="text-xs text-white/50">Administration</p>
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
        ?>
        <header class="admin-topbar sticky top-0 z-30 bg-white/80 backdrop-blur-lg border-b border-slate-200/80 px-4 sm:px-6 h-14 sm:h-16 flex items-center gap-3">
            <button @click="sidebarOpen=true" class="lg:hidden p-2 -ml-2 text-slate-600 rounded-lg hover:bg-slate-100 transition">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
            <h1 class="text-lg font-semibold text-church-800 truncate flex-1 min-w-0"><?= htmlspecialchars($title ?? '') ?></h1>

            <div class="admin-profile" x-data="{ open: false }" @keydown.escape.window="open = false">
                <button type="button"
                        class="admin-profile__trigger"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-haspopup="menu">
                    <span class="admin-profile__avatar">
                        <?php if ($headerAvatar): ?>
                        <img src="<?= htmlspecialchars($headerAvatar) ?>" alt="">
                        <?php else: ?>
                        <span><?= htmlspecialchars($headerInitials) ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="admin-profile__meta hidden sm:flex">
                        <span class="admin-profile__name"><?= htmlspecialchars($headerName) ?></span>
                        <span class="admin-profile__role">Administrator</span>
                    </span>
                    <i data-lucide="chevron-down" class="admin-profile__chevron w-4 h-4"></i>
                </button>

                <div class="admin-profile__menu"
                     x-show="open"
                     x-cloak
                     x-transition
                     @click.outside="open = false"
                     role="menu">
                    <div class="admin-profile__menu-head">
                        <p class="admin-profile__menu-name"><?= htmlspecialchars($headerName) ?></p>
                        <?php if ($headerEmail !== ''): ?>
                        <p class="admin-profile__menu-email"><?= htmlspecialchars($headerEmail) ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="/admin/profile" class="admin-profile__item" role="menuitem" @click="open = false">
                        <i data-lucide="user-round" class="w-4 h-4"></i>
                        My profile
                    </a>
                    <a href="/admin/settings" class="admin-profile__item" role="menuitem" @click="open = false">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                        Church settings
                    </a>
                    <div class="admin-profile__divider"></div>
                    <a href="/logout" class="admin-profile__item admin-profile__item--danger" role="menuitem">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Sign out
                    </a>
                </div>
            </div>
        </header>
        <main class="p-3 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full flex-1">
            <?= $content ?>
        </main>
        <?php $copyrightVariant = 'app-copyright--admin'; require __DIR__ . '/../partials/app-copyright.php'; ?>
    </div>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>lucide.createIcons();</script>
    <script src="/js/admin-nav-ajax.js?v=<?= (int) @filemtime(dirname(__DIR__, 2) . '/public/js/admin-nav-ajax.js') ?>"></script>
</body>
</html>
