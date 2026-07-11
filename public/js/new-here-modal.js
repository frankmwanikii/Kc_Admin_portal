/**
 * New Here — first-time visitor connect form modal (home page)
 */
(function () {
    'use strict';

    const modal = document.getElementById('new-here-modal');
    const form = document.getElementById('new-here-form');
    const successEl = document.getElementById('nh-form-success');

    if (!modal || !form) return;

    const openTriggers = document.querySelectorAll('.js-new-here-open');
    const closeTargets = modal.querySelectorAll('[data-nh-close]');
    let lastFocus = null;

    function lockScroll() {
        document.documentElement.classList.add('nb-modal-open');
        document.body.classList.add('nb-modal-open');
    }

    function unlockScroll() {
        document.documentElement.classList.remove('nb-modal-open');
        document.body.classList.remove('nb-modal-open');
    }

    function clearErrors() {
        form.querySelectorAll('.nb-input.is-invalid, .nb-fieldset.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    function markInvalid(el) {
        if (el) el.classList.add('is-invalid');
    }

    function openModal() {
        lastFocus = document.activeElement;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        lockScroll();
        clearErrors();
        const closeBtn = modal.querySelector('.nb-modal-close');
        if (closeBtn) closeBtn.focus();
    }

    function closeModal() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        unlockScroll();
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    function showSuccess() {
        form.hidden = true;
        if (successEl) successEl.hidden = false;
    }

    function resetModal() {
        form.reset();
        form.hidden = false;
        if (successEl) successEl.hidden = true;
        clearErrors();
    }

    openTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            resetModal();
            openModal();
        });
    });

    closeTargets.forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        let valid = true;
        const name = form.querySelector('[name="name"]');
        const phone = form.querySelector('[name="phone"]');
        const email = form.querySelector('[name="email"]');
        const age = form.querySelector('[name="age_range"]');
        const gender = form.querySelector('[name="gender"]:checked');
        const heardAbout = form.querySelector('[name="heard_about"]');

        if (!name.value.trim()) {
            markInvalid(name);
            valid = false;
        }
        if (!phone.value.trim()) {
            markInvalid(phone);
            valid = false;
        }
        if (!email.value.trim() || !email.value.includes('@')) {
            markInvalid(email);
            valid = false;
        }
        if (!age.value) {
            markInvalid(age);
            valid = false;
        }
        if (!gender) {
            const genderFieldset = form.querySelector('[name="gender"]').closest('.nb-fieldset');
            markInvalid(genderFieldset);
            valid = false;
        }
        if (!heardAbout.value) {
            markInvalid(heardAbout);
            valid = false;
        }

        if (valid) {
            KCFormSubmit.send('new-here', form, {
                onSuccess: function () {
                    form.reset();
                    window.setTimeout(closeModal, 2800);
                }
            });
        }
    });
})();
