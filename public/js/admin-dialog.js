/**
 * Styled admin dialogs — replaces native confirm() / alert().
 *
 * AdminDialog.confirm({ title, message, confirmLabel, cancelLabel, tone })
 * AdminDialog.alert({ title, message, confirmLabel, tone })
 * Forms: <form data-confirm="..." data-confirm-title="..." data-confirm-tone="danger">
 */
(function () {
    'use strict';

    let root = null;
    let resolveFn = null;
    let previousFocus = null;

    const ICONS = {
        danger: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
        success: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    };

    function ensureRoot() {
        if (root && document.body.contains(root)) return root;

        root = document.createElement('div');
        root.className = 'admin-dialog-root';
        root.hidden = true;
        root.setAttribute('role', 'presentation');
        root.innerHTML = `
            <div class="admin-dialog__backdrop" data-admin-dialog-dismiss></div>
            <div class="admin-dialog__panel" role="alertdialog" aria-modal="true" aria-labelledby="admin-dialog-title" aria-describedby="admin-dialog-message">
                <div class="admin-dialog__icon" data-admin-dialog-icon></div>
                <h2 class="admin-dialog__title" id="admin-dialog-title" data-admin-dialog-title></h2>
                <p class="admin-dialog__message" id="admin-dialog-message" data-admin-dialog-message></p>
                <div class="admin-dialog__actions">
                    <button type="button" class="admin-dialog__btn admin-dialog__btn--ghost" data-admin-dialog-cancel>Cancel</button>
                    <button type="button" class="admin-dialog__btn admin-dialog__btn--primary" data-admin-dialog-confirm>OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(root);

        root.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;
            if (target.closest('[data-admin-dialog-dismiss]') || target.closest('[data-admin-dialog-cancel]')) {
                close(false);
            } else if (target.closest('[data-admin-dialog-confirm]')) {
                close(true);
            }
        });

        return root;
    }

    function onKeydown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            close(false);
        } else if (event.key === 'Enter' && event.target === root?.querySelector('[data-admin-dialog-confirm]')) {
            event.preventDefault();
            close(true);
        }
    }

    function close(result) {
        if (!root || root.hidden) return;
        root.classList.remove('is-open');
        root.hidden = true;
        document.removeEventListener('keydown', onKeydown, true);
        document.body.style.removeProperty('overflow');
        if (previousFocus && typeof previousFocus.focus === 'function') {
            try { previousFocus.focus(); } catch (_) { /* ignore */ }
        }
        previousFocus = null;
        const resolve = resolveFn;
        resolveFn = null;
        if (resolve) resolve(result);
    }

    function open(options) {
        const opts = options || {};
        const tone = opts.tone || (opts.danger ? 'danger' : 'info');
        const mode = opts.mode === 'alert' ? 'alert' : 'confirm';

        ensureRoot();
        previousFocus = document.activeElement;

        const iconEl = root.querySelector('[data-admin-dialog-icon]');
        const titleEl = root.querySelector('[data-admin-dialog-title]');
        const messageEl = root.querySelector('[data-admin-dialog-message]');
        const cancelBtn = root.querySelector('[data-admin-dialog-cancel]');
        const confirmBtn = root.querySelector('[data-admin-dialog-confirm]');

        iconEl.className = 'admin-dialog__icon admin-dialog__icon--' + (tone === 'danger' || tone === 'warning' || tone === 'success' ? tone : 'info');
        iconEl.innerHTML = ICONS[tone] || ICONS.info;
        titleEl.textContent = opts.title || (mode === 'alert' ? 'Notice' : 'Please confirm');
        messageEl.textContent = opts.message || '';

        cancelBtn.hidden = mode === 'alert';
        cancelBtn.textContent = opts.cancelLabel || 'Cancel';
        confirmBtn.textContent = opts.confirmLabel || (mode === 'alert' ? 'OK' : (tone === 'danger' ? 'Delete' : 'Confirm'));
        confirmBtn.className = 'admin-dialog__btn ' + (tone === 'danger' ? 'admin-dialog__btn--danger' : 'admin-dialog__btn--primary');

        root.hidden = false;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onKeydown, true);

        requestAnimationFrame(() => {
            root.classList.add('is-open');
            confirmBtn.focus();
            window.lucide?.createIcons?.();
        });

        return new Promise((resolve) => {
            resolveFn = resolve;
        });
    }

    async function confirm(options) {
        if (typeof options === 'string') {
            options = { message: options };
        }
        return open({ ...(options || {}), mode: 'confirm' });
    }

    async function alert(options) {
        if (typeof options === 'string') {
            options = { message: options };
        }
        await open({ ...(options || {}), mode: 'alert', confirmLabel: (options && options.confirmLabel) || 'OK' });
        return true;
    }

    // Intercept forms marked with data-confirm
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.hasAttribute('data-confirm')) return;
        if (form.dataset.adminDialogConfirmed === '1') {
            delete form.dataset.adminDialogConfirmed;
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const ok = await confirm({
            title: form.getAttribute('data-confirm-title') || 'Please confirm',
            message: form.getAttribute('data-confirm') || 'Are you sure?',
            confirmLabel: form.getAttribute('data-confirm-label') || undefined,
            cancelLabel: form.getAttribute('data-confirm-cancel') || undefined,
            tone: form.getAttribute('data-confirm-tone') || (form.hasAttribute('data-confirm-danger') ? 'danger' : 'warning'),
            danger: form.hasAttribute('data-confirm-danger'),
        });

        if (!ok) return;
        form.dataset.adminDialogConfirmed = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }, true);

    window.AdminDialog = { confirm, alert, open };
})();
