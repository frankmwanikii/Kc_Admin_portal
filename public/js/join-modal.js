/**
 * Join — covenant membership application modal (home page)
 */
(function () {
    'use strict';

    const modal = document.getElementById('join-modal');
    const form = document.getElementById('join-form');
    const successEl = document.getElementById('join-form-success');

    if (!modal || !form) return;

    const openTriggers = document.querySelectorAll('.js-join-open');
    const closeTargets = modal.querySelectorAll('[data-join-close]');
    let lastFocus = null;

    const toggleGroups = {
        'spouse-fields': { field: 'has_spouse', showWhen: 'yes', el: document.getElementById('join-spouse-fields') },
        'children-fields': { field: 'has_children', showWhen: 'yes', el: document.getElementById('join-children-fields') },
        'dependents-fields': { field: 'has_dependents', showWhen: 'yes', el: document.getElementById('join-dependents-fields') },
        'other-church-fields': { field: 'other_church_member', showWhen: 'yes', el: document.getElementById('join-other-church-fields') }
    };

    function lockScroll() {
        document.documentElement.classList.add('nb-modal-open');
        document.body.classList.add('nb-modal-open');
    }

    function unlockScroll() {
        document.documentElement.classList.remove('nb-modal-open');
        document.body.classList.remove('nb-modal-open');
    }

    function clearErrors() {
        form.querySelectorAll('.nb-input.is-invalid, .nb-fieldset.is-invalid, .nb-check-list.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    function markInvalid(el) {
        if (el) el.classList.add('is-invalid');
    }

    function getRadioValue(name) {
        const checked = form.querySelector('[name="' + name + '"]:checked');
        return checked ? checked.value : '';
    }

    function updateConditionals() {
        Object.keys(toggleGroups).forEach(function (key) {
            const group = toggleGroups[key];
            if (!group.el) return;
            const value = getRadioValue(group.field);
            const show = value === group.showWhen;
            group.el.hidden = !show;
            if (!show) {
                group.el.querySelectorAll('[data-join-required-when]').forEach(function (field) {
                    field.value = '';
                    field.classList.remove('is-invalid');
                });
            }
        });
    }

    function isConditionallyRequired(field) {
        const rule = field.getAttribute('data-join-required-when');
        if (!rule) return false;
        const parts = rule.split(':');
        return getRadioValue(parts[0]) === parts[1];
    }

    function openModal() {
        lastFocus = document.activeElement;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        lockScroll();
        clearErrors();
        updateConditionals();
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
        updateConditionals();
    }

    form.querySelectorAll('[data-join-toggle]').forEach(function (input) {
        input.addEventListener('change', updateConditionals);
    });

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
        updateConditionals();

        let valid = true;

        form.querySelectorAll('[required]').forEach(function (field) {
            if (field.type === 'checkbox' || field.type === 'radio') {
                if (field.type === 'radio') {
                    const group = form.querySelectorAll('[name="' + field.name + '"]');
                    const anyChecked = Array.prototype.some.call(group, function (r) { return r.checked; });
                    if (!anyChecked) {
                        const fieldset = field.closest('.nb-fieldset');
                        markInvalid(fieldset);
                        valid = false;
                    }
                } else if (!field.checked) {
                    const list = field.closest('.nb-check-list');
                    markInvalid(list || field);
                    valid = false;
                }
            } else if (!field.value || !String(field.value).trim()) {
                markInvalid(field);
                valid = false;
            }
        });

        form.querySelectorAll('[data-join-required-when]').forEach(function (field) {
            if (isConditionallyRequired(field) && !String(field.value).trim()) {
                markInvalid(field);
                valid = false;
            }
        });

        const email = form.querySelector('[name="email"]');
        if (email && email.value && !email.value.includes('@')) {
            markInvalid(email);
            valid = false;
        }

        const spouseEmail = form.querySelector('[name="spouse_email"]');
        if (spouseEmail && spouseEmail.value && !spouseEmail.value.includes('@')) {
            markInvalid(spouseEmail);
            valid = false;
        }

        const householdSize = form.querySelector('[name="household_size"]');
        if (householdSize && householdSize.value && parseInt(householdSize.value, 10) < 1) {
            markInvalid(householdSize);
            valid = false;
        }

        const maritalStatus = form.querySelector('[name="marital_status"]');
        if (maritalStatus && maritalStatus.value === 'married' && getRadioValue('has_spouse') !== 'yes') {
            markInvalid(maritalStatus);
            const spouseFieldset = form.querySelector('[name="has_spouse"]').closest('.nb-fieldset');
            markInvalid(spouseFieldset);
            valid = false;
        }

        if (valid) {
            KCFormSubmit.send('join', form, {
                onSuccess: function () {
                    form.reset();
                    updateConditionals();
                    window.setTimeout(closeModal, 2800);
                }
            });
        }
    });
})();
