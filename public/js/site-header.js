(function () {
    'use strict';

    const navToggle = document.getElementById('nav-toggle');
    const mainNav = document.getElementById('main-nav');
    const siteHeader = document.getElementById('site-header');

    if (!navToggle || !mainNav) return;

    let navOverlay = document.querySelector('.nav-overlay');
    if (!navOverlay) {
        navOverlay = document.createElement('div');
        navOverlay.className = 'nav-overlay';
        navOverlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(navOverlay);
    }

    let navScrollLockY = 0;

    function lockNavScroll() {
        navScrollLockY = window.scrollY;
        document.documentElement.classList.add('nav-open');
        document.body.classList.add('nav-open');
        document.body.style.top = '-' + navScrollLockY + 'px';
    }

    function unlockNavScroll() {
        document.documentElement.classList.remove('nav-open');
        document.body.classList.remove('nav-open');
        document.body.style.top = '';
        window.scrollTo(0, navScrollLockY);
    }

    function setNavOpen(open) {
        navToggle.classList.toggle('is-open', open);
        mainNav.classList.toggle('is-open', open);
        navOverlay.classList.toggle('is-visible', open);
        navOverlay.setAttribute('aria-hidden', String(!open));
        navToggle.setAttribute('aria-expanded', String(open));
        navToggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');

        if (open) lockNavScroll();
        else unlockNavScroll();
    }

    navToggle.addEventListener('click', function () {
        setNavOpen(!mainNav.classList.contains('is-open'));
    });

    navOverlay.addEventListener('click', function () {
        setNavOpen(false);
    });

    mainNav.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            setNavOpen(false);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mainNav.classList.contains('is-open')) {
            setNavOpen(false);
        }
    });

    if (siteHeader) {
        function onScroll() {
            siteHeader.classList.toggle('is-scrolled', window.scrollY > 8);
        }
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }
})();
