(function () {
    'use strict';

    function registerCommsAlpine() {
        if (!window.Alpine || window.__kcCommsAlpineRegistered) return;
        window.__kcCommsAlpineRegistered = true;

        Alpine.data('commsComposer', (config) => ({
            audience: config.preselectMember ? 'individual' : 'all',
            memberId: config.preselectMember ? String(config.preselectMember) : '',
            channel: 'sms',
            message: '',
            recipients: config.recipients || [],

            get usesSmsLimit() {
                return ['sms', 'both', 'all'].includes(this.channel);
            },

            setAudience(mode) {
                this.audience = mode;
                if (mode === 'all') {
                    this.memberId = '';
                }
            },
        }));
    }

    document.addEventListener('alpine:init', registerCommsAlpine);
    if (window.Alpine) {
        registerCommsAlpine();
    }
})();
