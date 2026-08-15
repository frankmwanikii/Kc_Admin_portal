<?php
$copyrightYear = 2026;
$copyrightText = 'Copyright Fraittech Inc © ' . $copyrightYear;
$copyrightClass = trim('app-copyright ' . (string) ($copyrightVariant ?? ''));
?>
<footer class="<?= htmlspecialchars($copyrightClass) ?>" role="contentinfo">
    <p class="app-copyright__text"><?= htmlspecialchars($copyrightText) ?></p>
</footer>
