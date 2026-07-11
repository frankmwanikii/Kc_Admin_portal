(function () {
    'use strict';

    /** Map connect-flow step keys → Lucide icon names */
    const ICON_MAP = {
        'map-pin': 'map-pin',
        hand: 'hand',
        user: 'user-round',
        phone: 'phone',
        mail: 'mail',
        cake: 'cake',
        users: 'users',
        cross: 'cross',
        droplets: 'droplets',
        sprout: 'sprout',
        pray: 'heart-handshake',
        heart: 'heart',
        lightbulb: 'lightbulb',
        message: 'message-circle',
        target: 'target',
        home: 'home',
        handshake: 'handshake',
        church: 'church',
        couple: 'heart-handshake',
        baby: 'baby',
        family: 'users-round',
        backpack: 'backpack',
        household: 'house',
        hash: 'hash',
        'file-text': 'file-text',
        book: 'book-open',
        emergency: 'life-buoy',
        'user-circle': 'circle-user',
        serve: 'hand-heart',
        briefcase: 'briefcase',
        gift: 'gift',
        scroll: 'scroll-text',
        calendar: 'calendar-check',
        commitment: 'shield-check',
        review: 'clipboard-check',
        success: 'circle-check',
        sparkles: 'sparkles',
    };

    window.ConnectStepIcons = {
        resolve(key) {
            return ICON_MAP[key] || key || 'sparkles';
        },

        refresh(root) {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons({ root: root || document });
            }
        },
    };
})();
