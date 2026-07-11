(function () {
    'use strict';

    const footerPanels = document.querySelectorAll('[data-footer-panel]');
    const isDesktopFooter = function () {
        return window.matchMedia('(min-width: 992px)').matches;
    };

    footerPanels.forEach(function (panel) {
        const trigger = panel.querySelector('.footer-panel-trigger');
        if (!trigger) return;

        trigger.addEventListener('click', function () {
            if (isDesktopFooter()) return;

            const isOpen = panel.classList.contains('is-open');

            footerPanels.forEach(function (other) {
                if (other !== panel) {
                    other.classList.remove('is-open');
                    const otherTrigger = other.querySelector('.footer-panel-trigger');
                    if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
                }
            });

            panel.classList.toggle('is-open', !isOpen);
            trigger.setAttribute('aria-expanded', String(!isOpen));
        });
    });

    const newsletterForm = document.getElementById('newsletter-form');
    const successEl = document.getElementById('newsletter-success');
    const errorEl = document.getElementById('newsletter-error');
    const apiUrl = window.KC_SITE && window.KC_SITE.apiUrl;

    if (newsletterForm && apiUrl) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = '';
            }

            const submitBtn = newsletterForm.querySelector('[type="submit"]');
            const data = {};
            new FormData(newsletterForm).forEach(function (value, key) {
                data[key] = value;
            });

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.textContent;
                submitBtn.textContent = 'Sending…';
            }

            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ form: 'newsletter', data: data }),
            })
                .then(function (res) {
                    return res.json().then(function (body) {
                        return { ok: res.ok, body: body };
                    });
                })
                .then(function (result) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.dataset.originalText || 'Subscribe';
                    }

                    if (result.body && result.body.ok) {
                        newsletterForm.reset();
                        if (successEl) successEl.hidden = false;
                        return;
                    }

                    const msg = (result.body && result.body.message) || 'Unable to subscribe. Please try again.';
                    if (errorEl) {
                        errorEl.textContent = msg;
                        errorEl.hidden = false;
                    }
                })
                .catch(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.dataset.originalText || 'Subscribe';
                    }
                    if (errorEl) {
                        errorEl.textContent = 'Network error. Please try again.';
                        errorEl.hidden = false;
                    }
                });
        });
    }
})();
