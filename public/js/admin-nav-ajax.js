/**
 * Admin SPA-style sidebar navigation.
 * Intercepts sidebar / profile menu links and swaps main content via AJAX.
 */
(function () {
    'use strict';

    const loadedStyles = new Set();
    const loadedScripts = new Set();
    let busy = false;

    function absUrl(href) {
        try {
            return new URL(href, window.location.origin);
        } catch (_) {
            return null;
        }
    }

    function styleKey(href) {
        const url = absUrl(href);
        return url ? url.pathname + url.search : '';
    }

    function headStyleLink(href) {
        const key = styleKey(href);
        if (!key) return null;
        for (const el of document.head.querySelectorAll('link[rel="stylesheet"][href]')) {
            if (styleKey(el.getAttribute('href')) === key) return el;
        }
        return null;
    }

    /**
     * Page views often emit <link> tags inside <main>. Those are destroyed on the
     * next AJAX swap — move them into <head> so styles survive navigation.
     */
    function promoteMainStylesToHead() {
        const main = document.querySelector('.admin-main main');
        if (!main) return;
        main.querySelectorAll('link[rel="stylesheet"][href]').forEach((el) => {
            const href = el.getAttribute('href');
            const key = styleKey(href);
            if (!key) {
                el.remove();
                return;
            }
            if (headStyleLink(href)) {
                el.remove();
                loadedStyles.add(key);
                return;
            }
            document.head.appendChild(el);
            loadedStyles.add(key);
        });
    }

    function seedAssets() {
        promoteMainStylesToHead();
        document.head.querySelectorAll('link[rel="stylesheet"][href]').forEach((el) => {
            const key = styleKey(el.getAttribute('href'));
            if (key) loadedStyles.add(key);
        });
        document.querySelectorAll('script[src]').forEach((el) => {
            const url = absUrl(el.getAttribute('src'));
            if (url) loadedScripts.add(url.pathname + url.search);
        });
    }

    function ensureStyle(href) {
        const url = absUrl(href);
        if (!url) return Promise.resolve();
        const key = url.pathname + url.search;

        // Only trust stylesheets that still live in <head> (main is swapped away).
        const existing = headStyleLink(href);
        if (existing) {
            loadedStyles.add(key);
            return Promise.resolve();
        }
        loadedStyles.delete(key);

        return new Promise((resolve) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = () => {
                loadedStyles.add(key);
                resolve();
            };
            link.onerror = () => resolve();
            document.head.appendChild(link);
        });
    }

    function ensureScript(src) {
        const url = absUrl(src);
        if (!url) return Promise.resolve();
        const key = url.pathname + url.search;
        if (loadedScripts.has(key)) {
            return Promise.resolve();
        }
        // Already in DOM from a prior full page load
        if (document.querySelector('script[src="' + src + '"]') || document.querySelector('script[src="' + url.pathname + '"]')) {
            loadedScripts.add(key);
            return Promise.resolve();
        }
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = false;
            script.onload = () => {
                loadedScripts.add(key);
                resolve();
            };
            script.onerror = () => reject(new Error('Failed to load ' + src));
            document.head.appendChild(script);
        });
    }

    async function ensureAssets(styles, scripts) {
        for (const href of styles || []) {
            await ensureStyle(href);
        }
        for (const src of scripts || []) {
            await ensureScript(src);
        }
    }

    function runInlineScript(code) {
        const script = document.createElement('script');
        script.textContent = `
            (function(){
                var __ready = document.readyState !== 'loading';
                var __orig = document.addEventListener.bind(document);
                document.addEventListener = function(type, fn, opts) {
                    if ((type === 'DOMContentLoaded' || type === 'load') && __ready) {
                        try { fn.call(document); } catch (e) { console.error(e); }
                        return;
                    }
                    return __orig(type, fn, opts);
                };
                try {
                    ${code}
                } finally {
                    document.addEventListener = __orig;
                }
            })();
        `;
        document.body.appendChild(script);
        script.remove();
    }

    async function injectMain(html) {
        const main = document.querySelector('.admin-main main');
        if (!main) throw new Error('Main content area missing.');

        // Keep current page CSS alive before main is wiped.
        promoteMainStylesToHead();

        const parser = new DOMParser();
        const doc = parser.parseFromString('<div id="admin-nav-root">' + html + '</div>', 'text/html');
        const root = doc.getElementById('admin-nav-root');
        if (!root) throw new Error('Could not parse page content.');

        const externalScripts = [];
        const inlineScripts = [];
        root.querySelectorAll('script').forEach((node) => {
            const src = node.getAttribute('src');
            if (src) externalScripts.push(src);
            else inlineScripts.push(node.textContent || '');
            node.remove();
        });

        // DOMParser moves <link> into the parsed document <head>, so scan both.
        const styleHrefs = [];
        doc.querySelectorAll('link[rel="stylesheet"][href]').forEach((node) => {
            const href = node.getAttribute('href');
            node.remove();
            if (href) styleHrefs.push(href);
        });
        await ensureAssets(styleHrefs, []);

        if (window.Alpine && typeof window.Alpine.destroyTree === 'function') {
            try {
                window.Alpine.destroyTree(main);
            } catch (_) { /* ignore */ }
        }

        main.innerHTML = root.innerHTML;

        // Any leftover body <link> tags from the new fragment → head.
        promoteMainStylesToHead();

        for (const src of externalScripts) {
            await ensureScript(src);
        }
        inlineScripts.forEach((code) => {
            if (code.trim()) runInlineScript(code);
        });

        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(main);
        }
        window.lucide?.createIcons();
    }

    function updateSidebarActive(href) {
        const target = absUrl(href);
        if (!target) return;

        document.querySelectorAll('.admin-side-nav__link').forEach((link) => {
            if (link.classList.contains('admin-side-nav__link--muted')) return;
            const linkUrl = absUrl(link.getAttribute('href') || '');
            if (!linkUrl) return;

            let active = linkUrl.pathname === target.pathname;

            if (active && target.pathname === '/admin/finance') {
                const targetTab = (target.searchParams.get('tab') || 'dashboard').toLowerCase();
                const linkTab = (linkUrl.searchParams.get('tab') || 'dashboard').toLowerCase();
                // Map legacy tabs
                const normalize = (tab) => {
                    if (tab === 'arrears') return 'bills';
                    if (tab === 'weekly' || tab === 'collections') return 'ledger';
                    if (tab === 'statement') return 'reports';
                    return tab;
                };
                active = normalize(targetTab) === normalize(linkTab);
            } else if (active && target.pathname === '/admin') {
                active = linkUrl.pathname === '/admin' && !linkUrl.search;
            } else if (active && target.pathname.startsWith('/admin/')) {
                // Keep exact path match; avoid marking parent paths
                active = linkUrl.pathname === target.pathname;
            }

            link.classList.toggle('admin-side-nav__link--active', !!active);
        });
    }

    function setBusy(on) {
        busy = on;
        document.body.classList.toggle('admin-nav-busy', on);
    }

    async function navigate(href, { push = true } = {}) {
        const target = absUrl(href);
        if (!target) {
            window.location.href = href;
            return;
        }

        const next = target.pathname + target.search + target.hash;
        if (busy) return;

        setBusy(true);
        try {
            const response = await fetch(next, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Admin-Nav': '1',
                },
                credentials: 'same-origin',
            });

            // Session expired / hard redirect
            if (response.redirected && /\/login\b/.test(response.url)) {
                window.location.href = response.url;
                return;
            }

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                window.location.href = next;
                return;
            }

            const data = await response.json();
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (!data.ok || typeof data.html !== 'string') {
                window.location.href = next;
                return;
            }

            await ensureAssets(data.styles || [], data.scripts || []);
            await injectMain(data.html);

            const titleEl = document.querySelector('.admin-topbar h1');
            if (titleEl) titleEl.textContent = data.title || '';
            if (data.documentTitle) document.title = data.documentTitle;
            else if (data.title) document.title = data.title;

            updateSidebarActive(data.url || next);

            const mainPane = document.querySelector('.admin-main main');
            if (mainPane) mainPane.scrollTop = 0;

            // Close mobile drawer if open (Alpine on body)
            try {
                const bodyData = window.Alpine?.$data?.(document.body);
                if (bodyData && 'sidebarOpen' in bodyData) bodyData.sidebarOpen = false;
            } catch (_) { /* ignore */ }

            if (push) {
                window.history.pushState({ adminNav: true }, '', next);
            }
        } catch (err) {
            console.error(err);
            window.location.href = next;
        } finally {
            setBusy(false);
        }
    }

    function shouldIntercept(anchor) {
        if (!anchor || anchor.target === '_blank' || anchor.hasAttribute('download')) return false;
        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;

        const url = absUrl(href);
        if (!url || url.origin !== window.location.origin) return false;
        if (!url.pathname.startsWith('/admin')) return false;
        if (url.pathname === '/logout' || url.pathname.startsWith('/logout')) return false;

        const inSidebar = !!anchor.closest('.admin-sidebar__nav, .admin-side-nav');
        const inProfile = !!anchor.closest('.admin-profile__menu');
        return inSidebar || inProfile;
    }

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented) return;
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const anchor = event.target.closest('a[href]');
        if (!shouldIntercept(anchor)) return;

        event.preventDefault();
        navigate(anchor.getAttribute('href'));
    });

    window.addEventListener('popstate', () => {
        if (!window.location.pathname.startsWith('/admin')) return;
        navigate(window.location.pathname + window.location.search + window.location.hash, { push: false });
    });

    seedAssets();
})();
