<?php

use App\Services\WebsiteFooterService;

$footer = WebsiteFooterService::data();
$siteName = $footer['site_name'];
$siteEmail = $footer['site_email'];
$siteAddress = $footer['site_address'];
$navItems = $footer['nav_items'];
$ministriesList = $footer['ministries_list'];
$footerPhones = $footer['footer_phones'];
$socialLinks = $footer['social_links'];
$formApiUrl = $footer['form_api_url'];
$year = date('Y');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site-footer.css">

<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-panels">
            <section class="footer-panel" data-footer-panel>
                <button class="footer-panel-trigger" type="button" aria-expanded="false">
                    <span class="footer-panel-title">Quick Links</span>
                    <span class="footer-panel-chevron" aria-hidden="true"></span>
                </button>
                <div class="footer-panel-body">
                    <ul class="footer-links">
                        <?php foreach ($navItems as $label => $file): ?>
                        <li><a href="<?= htmlspecialchars(WebsiteFooterService::pageUrl($file)) ?>"><?= htmlspecialchars($label) ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="/visit">First-time Visitor</a></li>
                        <li><a href="/login?redirect=/portal">Member Portal</a></li>
                    </ul>
                </div>
            </section>

            <section class="footer-panel" data-footer-panel>
                <button class="footer-panel-trigger" type="button" aria-expanded="false">
                    <span class="footer-panel-title">Ministries</span>
                    <span class="footer-panel-chevron" aria-hidden="true"></span>
                </button>
                <div class="footer-panel-body">
                    <ul class="footer-links">
                        <li><a href="<?= htmlspecialchars(WebsiteFooterService::pageUrl('ministries.php')) ?>">All Ministries</a></li>
                        <?php foreach ($ministriesList as $ministry): ?>
                        <li>
                            <a href="<?= htmlspecialchars(WebsiteFooterService::ministryUrl($ministry['slug'])) ?>">
                                <?= htmlspecialchars($ministry['title']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section class="footer-panel" data-footer-panel>
                <button class="footer-panel-trigger" type="button" aria-expanded="false">
                    <span class="footer-panel-title">Contact Us</span>
                    <span class="footer-panel-chevron" aria-hidden="true"></span>
                </button>
                <div class="footer-panel-body">
                    <ul class="footer-contact-list">
                        <li class="footer-contact-item">
                            <span class="footer-contact-icon footer-contact-icon--email"><?= render_contact_icon('email') ?></span>
                            <a href="mailto:<?= htmlspecialchars($siteEmail) ?>"><?= htmlspecialchars($siteEmail) ?></a>
                        </li>
                        <?php foreach ($footerPhones as $phone): ?>
                        <li class="footer-contact-item">
                            <span class="footer-contact-icon footer-contact-icon--phone"><?= render_contact_icon('phone') ?></span>
                            <span>
                                <strong><?= htmlspecialchars($phone['label']) ?></strong>
                                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $phone['number'])) ?>"><?= htmlspecialchars($phone['number']) ?></a>
                            </span>
                        </li>
                        <?php endforeach; ?>
                        <li class="footer-contact-item">
                            <span class="footer-contact-icon footer-contact-icon--location"><?= render_contact_icon('location') ?></span>
                            <span><?= htmlspecialchars($siteAddress) ?></span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="footer-panel footer-panel--newsletter" data-footer-panel>
                <button class="footer-panel-trigger" type="button" aria-expanded="false">
                    <span class="footer-panel-title">Newsletter</span>
                    <span class="footer-panel-chevron" aria-hidden="true"></span>
                </button>
                <div class="footer-panel-body">
                    <div id="newsletter-success" class="footer-newsletter-success" hidden>
                        <p>Thank you for subscribing!</p>
                    </div>
                    <div id="newsletter-error" class="footer-newsletter-error" hidden></div>
                    <form id="newsletter-form" class="footer-newsletter-form" action="#" method="post" novalidate>
                        <div class="footer-field">
                            <label class="footer-field-label" for="newsletter-first-name">First Name</label>
                            <input class="footer-field-input" type="text" id="newsletter-first-name" name="first_name" required>
                        </div>
                        <div class="footer-field">
                            <label class="footer-field-label" for="newsletter-last-name">Last Name</label>
                            <input class="footer-field-input" type="text" id="newsletter-last-name" name="last_name" required>
                        </div>
                        <div class="footer-field">
                            <label class="footer-field-label" for="newsletter-email">Email Address</label>
                            <input class="footer-field-input" type="email" id="newsletter-email" name="email" required>
                        </div>
                        <button type="submit" class="footer-subscribe-btn">Subscribe</button>
                    </form>
                </div>
            </section>
        </div>

        <div class="footer-social-row">
            <?php foreach ($socialLinks as $social): ?>
            <a
                href="<?= htmlspecialchars($social['url']) ?>"
                class="footer-social-link footer-social-link--<?= htmlspecialchars($social['icon']) ?>"
                aria-label="<?= htmlspecialchars($social['label']) ?>"
                target="_blank"
                rel="noopener noreferrer"
            >
                <?= render_social_icon($social['icon']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="footer-legal">
            <p class="footer-copyright">&copy; <?= $year ?> <?= htmlspecialchars($siteName) ?>. <span class="footer-rights-reserved">All rights reserved.</span></p>
            <p class="footer-meta">
                <a href="<?= htmlspecialchars(WebsiteFooterService::pageUrl('privacy-policy.php')) ?>" class="footer-privacy-link">Privacy Policy</a>
                <span class="footer-meta-sep" aria-hidden="true">·</span>
                <span class="footer-credit">Designed by <a href="https://fraittech.co.ke" target="_blank" rel="noopener noreferrer">Fraittech</a></span>
            </p>
        </div>
    </div>
</footer>

<script>
window.KC_SITE = { apiUrl: <?= json_encode($formApiUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> };
</script>
<script src="/js/site-footer.js" defer></script>
