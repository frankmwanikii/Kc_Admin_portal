<?php

use App\Services\SettingsService;

$churchBrand = 'Kingdomcity church';
$churchBrandLine1 = 'Kingdomcity';
$churchBrandLine2 = 'church';
$slogan = 'Transformed lives';
$churchAddress = SettingsService::churchAddress();
$churchPhone = SettingsService::churchPhone();

$features = [
    [
        'Church Family',
        'Keep your household profile up to date and stay rooted in the Kingdomcity family.',
        'from-violet-500 to-purple-600 shadow-violet-500/35',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
    ],
    [
        'Sunday & Groups',
        'Check in to services and life groups as we gather and grow together each week.',
        'from-[#35afe6] to-[#0b486d] shadow-[#35afe6]/35',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4"/>',
    ],
    [
        'Give & Partner',
        'Tithes, offerings, and pledges that help Kingdomcity reach more transformed lives.',
        'from-emerald-500 to-teal-600 shadow-emerald-500/35',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
    ],
    [
        'Stay in the Loop',
        'Hear from Kingdomcity through SMS, email, and announcements — right when it matters.',
        'from-amber-500 to-orange-500 shadow-amber-500/35',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
    ],
];
?>
<style>
    @keyframes kc-float {
        0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
        50% { transform: translate3d(0, -18px, 0) scale(1.04); }
    }
    @keyframes kc-float-slow {
        0%, 100% { transform: translate3d(0, 0, 0); }
        50% { transform: translate3d(12px, -10px, 0); }
    }
    @keyframes kc-fade-up {
        from { opacity: 0; transform: translate3d(0, 28px, 0); }
        to { opacity: 1; transform: translate3d(0, 0, 0); }
    }
    @keyframes kc-shimmer {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    @keyframes kc-icon-pop {
        0%, 100% { transform: translateZ(12px) scale(1); }
        50% { transform: translateZ(20px) scale(1.08); }
    }
    .kc-float { animation: kc-float 7s ease-in-out infinite; }
    .kc-float-slow { animation: kc-float-slow 9s ease-in-out infinite; }
    .kc-float-delay { animation-delay: -3s; }
    .kc-animate { animation: kc-fade-up 0.8s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .kc-shimmer {
        background-size: 200% auto;
        animation: kc-shimmer 5s ease-in-out infinite;
    }
    .kc-card-3d {
        transform-style: preserve-3d;
        transition: transform 0.15s ease-out, box-shadow 0.3s ease;
        will-change: transform;
    }
    .kc-card-3d::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 1rem;
        background: linear-gradient(135deg, rgba(255,255,255,0.7) 0%, transparent 50%, rgba(53,175,230,0.06) 100%);
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .kc-card-3d:hover::before { opacity: 1; }
    .kc-icon-3d {
        transform: translateZ(16px);
        animation: kc-icon-pop 4s ease-in-out infinite;
    }
    .kc-icon-3d-delay-1 { animation-delay: -1s; }
    .kc-icon-3d-delay-2 { animation-delay: -2s; }
    .kc-icon-3d-delay-3 { animation-delay: -3s; }
    @media (prefers-reduced-motion: reduce) {
        .kc-float, .kc-float-slow, .kc-animate, .kc-shimmer, .kc-icon-3d { animation: none !important; }
        .kc-card-3d { transition: box-shadow 0.2s ease !important; }
    }
</style>

<div class="min-h-full flex flex-col relative overflow-hidden" style="perspective: 1400px;">
    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
        <div class="kc-float absolute -top-32 left-1/2 -translate-x-1/2 w-[min(100%,48rem)] h-[28rem] rounded-full bg-gradient-to-b from-church-200/60 via-church-100/40 to-transparent blur-3xl"></div>
        <div class="kc-float-slow kc-float-delay absolute top-1/4 -right-20 w-80 h-80 rounded-full bg-gradient-to-br from-gold-400/20 to-church-300/10 blur-3xl"></div>
        <div class="kc-float absolute bottom-10 -left-20 w-72 h-72 rounded-full bg-gradient-to-tr from-church-400/25 to-violet-300/15 blur-3xl kc-float-delay"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(53,175,230,0.08),transparent_55%)]"></div>
    </div>

    <header class="sticky top-0 z-20 px-4 sm:px-6 py-3 sm:py-4 border-b border-white/60 bg-white/70 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 kc-animate">
        <div class="flex items-center justify-between max-w-6xl mx-auto w-full gap-4">
            <a href="/" class="flex items-center gap-2.5 min-w-0 group">
                <?php $size = 'md'; $variant = 'dark'; require __DIR__ . '/partials/church-logo.php'; ?>
                <span class="font-semibold text-church-800 leading-tight group-hover:text-church-700 transition-colors">
                    <span class="block sm:inline text-[15px] sm:text-lg leading-none"><?= htmlspecialchars($churchBrandLine1) ?></span>
                    <span class="block sm:inline text-base sm:text-lg font-semibold text-church-600 sm:text-church-800 leading-none sm:before:content-['\00a0']"><?= htmlspecialchars($churchBrandLine2) ?></span>
                </span>
            </a>
            <nav class="flex items-center shrink-0">
                <a href="/login?redirect=/admin" class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 text-sm font-semibold text-white bg-gradient-to-br from-church-500 to-church-800 hover:from-church-600 hover:to-church-900 rounded-lg shadow-lg shadow-church-800/30 hover:shadow-xl hover:shadow-church-800/40 hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-4 h-4 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Admin
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        <section class="px-4 py-14 sm:py-20 lg:py-24">
            <div class="max-w-4xl mx-auto text-center">
                <div class="kc-animate inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/90 text-church-700 text-sm font-medium mb-6 border border-church-100 shadow-md shadow-church-900/5 backdrop-blur-sm" style="animation-delay: 0.1s;">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-church-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-church-500"></span>
                    </span>
                    <?= htmlspecialchars($churchBrand) ?>
                </div>

                <h1 class="kc-animate text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-[1.1]" style="animation-delay: 0.2s;">
                    <span class="kc-shimmer bg-gradient-to-r from-church-800 via-church-500 to-church-800 bg-clip-text text-transparent drop-shadow-sm"><?= htmlspecialchars($slogan) ?></span>
                </h1>

                <p class="kc-animate mt-6 text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed" style="animation-delay: 0.35s;">
                    Your home for staying connected with <?= htmlspecialchars($churchBrand) ?> — manage your profile, track giving, and keep up with church life from anywhere.
                </p>

                <div class="kc-animate mt-10 flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center" style="animation-delay: 0.5s;">
                    <a href="/login?redirect=/portal" class="group inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-br from-church-500 to-church-800 text-white font-semibold shadow-xl shadow-church-800/30 hover:shadow-2xl hover:shadow-church-800/40 hover:-translate-y-1 hover:scale-[1.02] transition-all duration-300">
                        Member Portal
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="<?= htmlspecialchars($onboardingUrl ?? '/onboard/church-onboard-2026') ?>" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-white/90 text-church-700 font-semibold border border-slate-200/80 hover:border-church-300 hover:bg-white hover:-translate-y-1 hover:scale-[1.02] hover:shadow-lg transition-all duration-300 shadow-md backdrop-blur-sm">
                        <svg class="w-4 h-4 text-church-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        New Member Registration
                    </a>
                </div>
            </div>
        </section>

        <section class="px-4 pb-14 sm:pb-20">
            <div class="max-w-4xl mx-auto kc-animate" style="animation-delay: 0.55s;">
                <div class="rounded-2xl bg-white/90 backdrop-blur-sm border border-white/80 shadow-xl shadow-church-900/5 overflow-hidden">
                    <div class="flex flex-col sm:flex-row items-center gap-8 sm:gap-10 p-6 sm:p-10">
                        <div class="flex-1 text-center sm:text-left order-2 sm:order-1">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-church-50 text-church-700 text-xs font-semibold uppercase tracking-wider mb-4 border border-church-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                Quick registration
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-bold text-church-900 tracking-tight">Scan to join <?= htmlspecialchars($churchBrand) ?></h2>
                            <p class="mt-3 text-slate-600 leading-relaxed max-w-md mx-auto sm:mx-0">
                                Point your phone camera at the QR code to open our welcome form — a few minutes and you're part of the family.
                            </p>
                            <ol class="mt-5 space-y-2 text-sm text-slate-500 text-left max-w-md mx-auto sm:mx-0">
                                <li class="flex items-start gap-2.5"><span class="shrink-0 w-5 h-5 rounded-full bg-church-100 text-church-700 text-xs font-bold flex items-center justify-center mt-0.5">1</span><span>Scan the code with your phone camera</span></li>
                                <li class="flex items-start gap-2.5"><span class="shrink-0 w-5 h-5 rounded-full bg-church-100 text-church-700 text-xs font-bold flex items-center justify-center mt-0.5">2</span><span>Fill in your details step by step</span></li>
                                <li class="flex items-start gap-2.5"><span class="shrink-0 w-5 h-5 rounded-full bg-church-100 text-church-700 text-xs font-bold flex items-center justify-center mt-0.5">3</span><span>Receive a welcome email with portal access</span></li>
                            </ol>
                            <a href="<?= htmlspecialchars($onboardingUrl ?? '/onboard/church-onboard-2026') ?>" class="inline-flex items-center gap-1.5 mt-6 text-sm font-semibold text-church-600 hover:text-church-800 transition">
                                Or open the form directly
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        </div>

                        <div class="shrink-0 order-1 sm:order-2">
                            <div class="relative p-4 rounded-2xl bg-gradient-to-br from-church-50 to-white border border-church-100 shadow-lg shadow-church-900/5">
                                <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-church-200/40 to-church-400/20 blur-sm -z-10" aria-hidden="true"></div>
                                <?php if (!empty($qrDataUri)): ?>
                                <img src="<?= $qrDataUri ?>" alt="Scan to open the new member registration form" width="200" height="200" class="w-44 h-44 sm:w-48 sm:h-48 rounded-xl bg-white p-1">
                                <?php endif; ?>
                                <p class="mt-3 text-center text-xs font-medium text-church-700">Scan with your camera</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-4 pb-16 sm:pb-24">
            <div class="max-w-6xl mx-auto">
                <div class="kc-animate text-center mb-10 sm:mb-12" style="animation-delay: 0.6s;">
                    <h2 class="text-2xl sm:text-3xl font-bold text-church-900 tracking-tight">Life at Kingdomcity</h2>
                    <p class="mt-3 text-slate-500 max-w-xl mx-auto">Your member portal for belonging, growing, and living out <?= htmlspecialchars($slogan) ?> together.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6" style="perspective: 1200px;">
                    <?php foreach ($features as $i => [$title, $desc, $gradient, $iconPaths]): ?>
                    <article
                        class="kc-card-3d kc-animate group relative rounded-2xl p-6 text-left bg-white/90 backdrop-blur-sm border border-white/80 shadow-lg shadow-slate-200/60 hover:shadow-2xl hover:shadow-church-900/10 hover:border-church-200/80"
                        style="animation-delay: <?= 0.7 + ($i * 0.12) ?>s;"
                        x-data="{ rx: 0, ry: 0 }"
                        @mousemove="const r = $el.getBoundingClientRect(); rx = ((event.clientY - r.top) / r.height - 0.5) * -10; ry = ((event.clientX - r.left) / r.width - 0.5) * 10;"
                        @mouseleave="rx = 0; ry = 0"
                        :style="`transform: rotateX(${rx}deg) rotateY(${ry}deg) translateZ(0)`"
                    >
                        <div class="absolute -inset-px rounded-2xl bg-gradient-to-br from-church-200/40 via-transparent to-violet-200/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 -z-10 blur-sm"></div>

                        <div class="relative mb-5" style="transform-style: preserve-3d;">
                            <div class="kc-icon-3d <?= $i > 0 ? 'kc-icon-3d-delay-' . min($i, 3) : '' ?> relative w-14 h-14 rounded-2xl bg-gradient-to-br <?= $gradient ?> shadow-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <div class="absolute inset-0 rounded-2xl bg-gradient-to-t from-black/25 via-transparent to-white/30"></div>
                                <div class="absolute -bottom-1 left-3 right-3 h-3 rounded-full bg-black/10 blur-md"></div>
                                <svg class="relative w-7 h-7 text-white drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $iconPaths ?></svg>
                            </div>
                        </div>

                        <h3 class="font-semibold text-church-800 text-base relative" style="transform: translateZ(8px);"><?= $title ?></h3>
                        <p class="text-sm text-slate-500 mt-2 leading-relaxed relative" style="transform: translateZ(4px);"><?= $desc ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="mt-auto border-t border-slate-200/80 bg-white/50 backdrop-blur-sm kc-animate" style="animation-delay: 1.1s;">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
            <div class="flex items-center gap-2.5">
                <?php $size = 'sm'; $variant = 'dark'; require __DIR__ . '/partials/church-logo.php'; ?>
                <span class="font-medium text-church-800 leading-tight text-center sm:text-left">
                    <span class="block sm:inline"><?= htmlspecialchars($churchBrandLine1) ?></span>
                    <span class="block sm:inline text-base sm:text-inherit sm:before:content-['\00a0']"><?= htmlspecialchars($churchBrandLine2) ?></span>
                </span>
            </div>
            <?php if ($churchAddress || $churchPhone): ?>
            <div class="flex flex-col sm:flex-row items-center gap-1 sm:gap-4 text-center sm:text-right">
                <?php if ($churchAddress): ?>
                <span><?= htmlspecialchars($churchAddress) ?></span>
                <?php endif; ?>
                <?php if ($churchPhone): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $churchPhone)) ?>" class="text-church-600 hover:text-church-800 font-medium transition"><?= htmlspecialchars($churchPhone) ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </footer>
</div>
