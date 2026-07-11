<?php

use App\Services\WebsiteContentService;

$content = WebsiteContentService::bootstrap();
$site_name = $content['site_name'];
$ministries_list = $content['ministries_list'];
$campuses = $content['campuses'];
$formApiUrl = $content['form_api_url'];

$modalFiles = [
    'new-beginning-modal.php',
    'new-here-modal.php',
    'kingdom-groups-modal.php',
    'join-modal.php',
];

foreach ($modalFiles as $file) {
    require __DIR__ . '/connect-modals/' . $file;
}
?>
<link rel="stylesheet" href="/css/site-connect-modals.css">
<script>
window.KC_SITE = { apiUrl: <?= json_encode($formApiUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> };
</script>
<script src="/js/form-submit.js" defer></script>
<script src="/js/new-beginning-modal.js" defer></script>
<script src="/js/new-here-modal.js" defer></script>
<script src="/js/kingdom-groups-modal.js" defer></script>
<script src="/js/join-modal.js" defer></script>
