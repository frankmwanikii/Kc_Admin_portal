<?php



use App\Services\SettingsService;



$churchName = SettingsService::churchName();

?>

<style>

    @keyframes kc-rise {

        from { opacity: 0; transform: translateY(20px); }

        to { opacity: 1; transform: translateY(0); }

    }

    .kc-rise { animation: kc-rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) both; }

    html.connect-form-open,

    body.connect-form-open { overflow: hidden; }

    @media (prefers-reduced-motion: reduce) {

        .kc-rise { animation: none !important; }

    }

</style>



<div class="page-portal-home min-h-full flex flex-col bg-slate-50">

    <?php require __DIR__ . '/partials/site-header.php'; ?>



    <main class="flex-1">

        <?php require __DIR__ . '/partials/site-hero.php'; ?>

        <?php require __DIR__ . '/partials/connect-section.php'; ?>

    </main>



    <?php require __DIR__ . '/partials/site-footer.php'; ?>

</div>

