/**
 * Admin shell Alpine component — theme, sidebar, topbar search.
 * Must load before Alpine (defer order in head.php).
 */
window.adminShell = function adminShell(jumpLinks) {
    const links = Array.isArray(jumpLinks) ? jumpLinks : [];

    return {
        sidebarOpen: false,
        sidebarCollapsed: false,
        theme: document.documentElement.dataset.adminTheme === 'dark' ? 'dark' : 'light',
        searchOpen: false,
        searchTerm: '',
        searchActive: 0,
        jumpLinks: links,

        get isDark() {
            return this.theme === 'dark';
        },

        get filteredJumpLinks() {
            const q = this.searchTerm.trim().toLowerCase();
            if (!q) return this.jumpLinks.slice(0, 8);
            return this.jumpLinks
                .filter((item) => {
                    const hay = (item.label + ' ' + (item.keywords || '')).toLowerCase();
                    return hay.includes(q);
                })
                .slice(0, 8);
        },

        init() {
            try {
                this.sidebarCollapsed = localStorage.getItem('adminSidebarCollapsed') === '1';
            } catch (_) {
                this.sidebarCollapsed = false;
            }

            this.applyTheme(this.theme, false);
            this.$watch('theme', (value) => this.applyTheme(value, true));
            this.$watch('searchTerm', () => {
                this.$nextTick(() => window.lucide?.createIcons());
            });
            this.$watch('searchOpen', (open) => {
                if (open) this.$nextTick(() => window.lucide?.createIcons());
            });

            this._onGlobalKeydown = (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    this.focusSearch();
                }
            };
            window.addEventListener('keydown', this._onGlobalKeydown);
            this.$nextTick(() => window.lucide?.createIcons());
        },

        destroy() {
            if (this._onGlobalKeydown) {
                window.removeEventListener('keydown', this._onGlobalKeydown);
            }
        },

        applyTheme(value, persist) {
            const dark = value === 'dark';
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.dataset.adminTheme = dark ? 'dark' : 'light';
            document.body.classList.toggle('dark', dark);
            const themeMeta = document.querySelector('meta[name="theme-color"]');
            if (themeMeta) {
                themeMeta.setAttribute('content', dark ? '#0b1c28' : '#0b486d');
            }
            if (persist) {
                try {
                    localStorage.setItem('kc-admin-theme', dark ? 'dark' : 'light');
                } catch (_) { /* ignore */ }
            }
            this.$nextTick(() => window.lucide?.createIcons());
        },

        setTheme(value) {
            this.theme = value === 'dark' ? 'dark' : 'light';
        },

        toggleSidebarCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            try {
                localStorage.setItem(
                    'adminSidebarCollapsed',
                    this.sidebarCollapsed ? '1' : '0'
                );
            } catch (_) { /* ignore */ }
            this.$nextTick(() => window.lucide?.createIcons());
        },

        focusSearch() {
            this.searchOpen = true;
            this.$nextTick(() => {
                const input = this.$refs.searchDesktop || this.$refs.searchMobile;
                input?.focus();
                input?.select?.();
            });
        },

        goToSearchResult(href) {
            this.searchOpen = false;
            this.searchTerm = '';
            if (href) window.location.href = href;
        },

        onSearchKeydown(e) {
            const items = this.filteredJumpLinks;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.searchActive = Math.min(
                    this.searchActive + 1,
                    Math.max(items.length - 1, 0)
                );
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.searchActive = Math.max(this.searchActive - 1, 0);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const hit = items[this.searchActive] || items[0];
                if (hit) this.goToSearchResult(hit.href);
            } else if (e.key === 'Escape') {
                this.searchOpen = false;
                this.searchTerm = '';
                e.target.blur();
            }
        },
    };
};
