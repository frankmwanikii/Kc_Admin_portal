(function () {
    'use strict';

    document.addEventListener('alpine:init', () => {
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
    });
})();
