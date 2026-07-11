<?php

use App\Services\WebsiteContentService;

$content = WebsiteContentService::bootstrap();
$siteName = $content['site_name'];
$logoUrl = WebsiteContentService::logoUrl();
$logoHeights = WebsiteContentService::logoHeights();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$mainWebsiteUrl = rtrim((string) ($content['public_website_url'] ?? ''), '/');
if ($mainWebsiteUrl === '') {
    $mainWebsiteUrl = WebsiteContentService::pageUrl('index.php');
}

$navItems = [
    ['label' => 'Home', 'href' => '/'],
    ['label' => 'Pledge', 'href' => '/login?redirect=/portal/pledges'],
    ['label' => 'Redeem', 'href' => WebsiteContentService::pageUrl('give.php')],
    ['label' => 'Statement', 'href' => '/login?redirect=/portal/giving'],
    ['label' => 'Main Website', 'href' => $mainWebsiteUrl, 'external' => true],
    ['label' => 'Member Login', 'href' => '/login?redirect=/portal'],
    ['label' => 'Admin Login', 'href' => '/login?redirect=/admin'],
];
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/site-header.css">
<style id="site-logo-vars">
.page-portal-home { --logo-height: <?= $logoHeights['height_mobile'] ?>px; }
@media (min-width: 992px) {
    .page-portal-home { --logo-height: <?= $logoHeights['height_desktop'] ?>px; }
}
</style>

<header class="site-header" id="site-header">
    <div class="header-main">
        <div class="container header-inner">
            <a href="/" class="logo-link" aria-label="<?= htmlspecialchars($siteName) ?> — Home">
                <img
                    src="<?= htmlspecialchars($logoUrl) ?>"
                    alt="<?= htmlspecialchars($siteName) ?> logo"
                    class="logo-img"
                    width="180"
                    height="auto"
                >
            </a>

            <button
                class="nav-toggle"
                id="nav-toggle"
                type="button"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="main-nav"
            >
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>

            <nav class="main-nav" id="main-nav" aria-label="Main navigation">
                <ul class="nav-list">
                    <?php foreach ($navItems as $item): ?>
                    <?php
                    $label = $item['label'];
                    $href = $item['href'];
                    $external = !empty($item['external']);
                    $isActive = !$external && (
                        ($href === '/' && $currentPath === '/')
                        || ($href !== '/' && str_starts_with($currentPath, strtok($href, '?')))
                    );
                    ?>
                    <li class="nav-item">
                        <a
                            href="<?= htmlspecialchars($href) ?>"
                            class="nav-link<?= $isActive ? ' is-active' : '' ?><?= $external ? ' nav-link--external' : '' ?>"
                            <?= $isActive ? 'aria-current="page"' : '' ?>
                            <?= $external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                        ><?= htmlspecialchars($label) ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </div>
</header>

<script src="/js/site-header.js" defer></script>
