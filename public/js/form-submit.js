/**
 * Shared AJAX form submission + in-form toast notifications
 */
(function () {
    'use strict';

    const TOAST_DURATION = 3200;

    function getApiUrl() {
        if (window.KC_SITE && window.KC_SITE.apiUrl) {
            return window.KC_SITE.apiUrl;
        }
        return 'api/send-form.php';
    }

    function ensureHoneypot(form) {
        if (!form.querySelector('[name="_hp"]')) {
            const hp = document.createElement('input');
            hp.type = 'text';
            hp.name = '_hp';
            hp.tabIndex = -1;
            hp.autocomplete = 'off';
            hp.setAttribute('aria-hidden', 'true');
            hp.className = 'kc-honeypot';
            form.appendChild(hp);
        }
    }

    function prepareForm(form) {
        form.classList.add('kc-form--toastable');
        ensureHoneypot(form);
    }

    function getSubmitAnchor(form) {
        const actions = form.querySelector('.nb-form-actions');
        if (actions) {
            actions.classList.add('kc-form-toast-anchor');
            return actions;
        }

        const submitBtn = form.querySelector('[type="submit"]');
        if (!submitBtn) {
            form.classList.add('kc-form-toast-anchor');
            return form;
        }

        let anchor = submitBtn.closest('.kc-form-toast-anchor');
        if (anchor) {
            return anchor;
        }

        anchor = document.createElement('div');
        anchor.className = 'kc-form-toast-anchor';
        submitBtn.parentNode.insertBefore(anchor, submitBtn);
        anchor.appendChild(submitBtn);
        return anchor;
    }

    function getFormToastHost(form) {
        const anchor = getSubmitAnchor(form);
        let host = anchor.querySelector(':scope > .kc-form-toast-host');
        if (!host) {
            host = document.createElement('div');
            host.className = 'kc-form-toast-host';
            host.setAttribute('aria-live', 'polite');
            host.setAttribute('aria-atomic', 'true');
            anchor.insertBefore(host, anchor.firstChild);
        }
        return host;
    }

    function tickIcon() {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('width', '14');
        svg.setAttribute('height', '14');
        svg.setAttribute('aria-hidden', 'true');
        svg.innerHTML = '<path fill="currentColor" d="M9.2 16.2 4.8 11.8l1.4-1.4 3 3 8.4-8.4 1.4 1.4z"/>';
        return svg;
    }

    function errorIcon() {
        const span = document.createElement('span');
        span.textContent = '!';
        return span;
    }

    function showToast(message, type, form) {
        if (!form) return;

        prepareForm(form);
        const host = getFormToastHost(form);
        host.innerHTML = '';

        const toast = document.createElement('div');
        toast.className = 'kc-form-toast kc-form-toast--' + (type || 'success');
        if (form.closest('.site-footer')) {
            toast.classList.add('kc-form-toast--footer');
        }
        toast.setAttribute('role', 'status');

        const iconWrap = document.createElement('span');
        iconWrap.className = 'kc-form-toast-icon';
        iconWrap.appendChild(type === 'error' ? errorIcon() : tickIcon());

        const text = document.createElement('span');
        text.className = 'kc-form-toast-text';
        text.textContent = message;

        toast.appendChild(iconWrap);
        toast.appendChild(text);
        host.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });

        window.setTimeout(function () {
            toast.classList.remove('is-visible');
            window.setTimeout(function () {
                toast.remove();
            }, 300);
        }, TOAST_DURATION);
    }

    function serializeForm(form) {
        prepareForm(form);
        const data = {};
        const fd = new FormData(form);

        fd.forEach(function (value, key) {
            const arrayKey = key.endsWith('[]') ? key.slice(0, -2) : key;
            if (key.endsWith('[]')) {
                if (!data[arrayKey]) data[arrayKey] = [];
                data[arrayKey].push(value);
            } else if (Object.prototype.hasOwnProperty.call(data, arrayKey)) {
                if (!Array.isArray(data[arrayKey])) {
                    data[arrayKey] = [data[arrayKey]];
                }
                data[arrayKey].push(value);
            } else {
                data[arrayKey] = value;
            }
        });

        return data;
    }

    function setButtonLoading(button, loading) {
        if (!button) return;
        if (loading) {
            button.disabled = true;
            button.dataset.kcOriginalText = button.textContent;
            button.textContent = 'Sending…';
            button.classList.add('is-loading');
        } else {
            button.disabled = false;
            if (button.dataset.kcOriginalText) {
                button.textContent = button.dataset.kcOriginalText;
            }
            button.classList.remove('is-loading');
        }
    }

    function send(formType, form, options) {
        options = options || {};
        const submitBtn = options.submitBtn || (form ? form.querySelector('[type="submit"]') : null);
        let data;

        if (options.data) {
            if (form) {
                prepareForm(form);
            }
            data = Object.assign({}, options.data);
            if (form) {
                const hp = form.querySelector('[name="_hp"]');
                if (hp) {
                    data._hp = hp.value;
                }
            }
        } else if (form) {
            data = serializeForm(form);
        } else {
            data = {};
        }

        setButtonLoading(submitBtn, true);

        return fetch(getApiUrl(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                form: formType,
                data: data
            })
        })
            .then(function (response) {
                return response.json().then(function (body) {
                    return { response: response, body: body };
                });
            })
            .then(function (result) {
                setButtonLoading(submitBtn, false);

                if (result.body && result.body.ok) {
                    if (!options.suppressToast) {
                        showToast(result.body.message || 'Sent successfully!', 'success', form);
                    }
                    if (typeof options.onSuccess === 'function') {
                        options.onSuccess(result.body);
                    }
                    return result.body;
                }

                const message = (result.body && result.body.message)
                    ? result.body.message
                    : 'Unable to send. Please try again.';
                if (!options.suppressToast) {
                    showToast(message, 'error', form);
                }
                if (typeof options.onError === 'function') {
                    options.onError(result.body);
                }
                const handled = new Error(message);
                handled.kcHandled = true;
                throw handled;
            })
            .catch(function (err) {
                setButtonLoading(submitBtn, false);
                if (!err || !err.kcHandled) {
                    if (!options.suppressToast) {
                        showToast('Network error. Please check your connection and try again.', 'error', form);
                    }
                }
                if (typeof options.onError === 'function') {
                    options.onError(err);
                }
                throw err;
            });
    }

    window.KCFormSubmit = {
        send: send,
        showToast: showToast,
        serializeForm: serializeForm,
        setButtonLoading: setButtonLoading
    };
})();
