<?php

use App\Services\WebsiteContentService;

$content = WebsiteContentService::bootstrap();
$heroSlides = $content['hero_slides'];
$connectCards = $content['connect_cards'];

if (empty($heroSlides)) {
    $heroSlides = [[
        'image' => '/images/kingdomcity-church-hero.png',
        'alt' => $content['site_name'],
    ]];
}
?>
<link rel="stylesheet" href="/css/site-hero.css">

<div class="hero-block">
    <section class="hero-slider" aria-label="Featured highlights" role="region">
        <div class="hero-slides">
            <?php foreach ($heroSlides as $i => $slide): ?>
            <?php
            $imageUrl = WebsiteContentService::assetUrl($slide['image'] ?? '');
            ?>
            <article
                class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>"
                aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>"
                data-slide="<?= $i ?>"
            >
                <div
                    class="hero-slide-bg"
                    style="background-image: url('<?= htmlspecialchars($imageUrl) ?>')"
                    role="img"
                    aria-label="<?= htmlspecialchars($slide['alt'] ?? 'Kingdomcity Church') ?>"
                ></div>
                <div class="hero-slide-overlay" aria-hidden="true"></div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if (count($heroSlides) > 1): ?>
        <button class="hero-arrow hero-arrow-prev" type="button" aria-label="Previous slide">&#10094;</button>
        <button class="hero-arrow hero-arrow-next" type="button" aria-label="Next slide">&#10095;</button>
        <div class="hero-controls">
            <div class="hero-dots" role="tablist" aria-label="Slide navigation">
                <?php foreach ($heroSlides as $i => $slide): ?>
                <button
                    class="hero-dot<?= $i === 0 ? ' is-active' : '' ?>"
                    type="button"
                    role="tab"
                    aria-label="Go to slide <?= $i + 1 ?>"
                    aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                ></button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="hero-portal-overlay">
            <div class="hero-portal-content">
                <p class="hero-portal-tagline"><?= htmlspecialchars($content['site_tagline'] ?? 'Transformed Lives') ?></p>
                <div class="hero-portal-primary">
                    <button type="button" class="btn btn-primary"
                            onclick="window.dispatchEvent(new CustomEvent('kc-connect-open',{detail:{form:'join'}}))">
                        Register
                    </button>
                </div>
                <?php if (!empty($connectCards)): ?>
                <div class="hero-portal-connect">
                    <span class="hero-portal-connect-label">Connect With Us</span>
                    <div class="hero-portal-connect-btns">
                        <?php foreach ($connectCards as $card): ?>
                        <?php
                        $formType = $card['modal'] ?? '';
                        $label = $card['title'] ?? 'Connect';
                        ?>
                        <?php if ($formType !== ''): ?>
                        <button type="button" class="btn btn-outline hero-connect-btn"
                                onclick="window.dispatchEvent(new CustomEvent('kc-connect-open',{detail:{form:'<?= htmlspecialchars($formType) ?>'}}))">
                            <?= htmlspecialchars($label) ?>
                        </button>
                        <?php else: ?>
                        <a href="<?= htmlspecialchars(WebsiteContentService::pageUrl($card['link'] ?? 'contact.php')) ?>" class="btn btn-outline hero-connect-btn">
                            <?= htmlspecialchars($label) ?>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<script src="/js/hero-slider-portal.js" defer></script>
