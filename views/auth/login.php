<?php

use App\Services\SettingsService;

$churchName = SettingsService::churchName();
$tagline = 'Transformed lives';
?>
<div class="min-h-full flex items-center justify-center p-4" x-data="{ qrOpen: false }" @keydown.escape.window="qrOpen = false">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="flex justify-center">
                <?php $size = 'xl'; $variant = 'dark'; require __DIR__ . '/../partials/church-logo.php'; ?>
            </div>
            <h1 class="mt-4 text-2xl sm:text-3xl font-bold text-church-800 tracking-tight"><?= htmlspecialchars($churchName) ?></h1>
            <p class="text-church-500 text-sm font-semibold mt-1.5 tracking-wide"><?= htmlspecialchars($tagline) ?></p>
            <p class="mt-5 text-lg font-semibold text-church-900">Welcome back</p>
            <?php if (!empty($_GET['redirect']) && str_starts_with($_GET['redirect'], '/admin')): ?>
            <p class="text-slate-500 mt-1 text-sm">Sign in with your <strong>administrator</strong> account</p>
            <?php else: ?>
            <p class="text-slate-500 mt-1 text-sm">Sign in to your member or admin portal</p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6 sm:p-8">
            <?php if (!empty($_GET['setup'])): ?>
            <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm border border-emerald-100">Setup complete! Sign in with your admin credentials.</div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-100"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/login" class="space-y-5">
                <?php if (!empty($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 focus:border-church-500 outline-none transition text-base"
                        placeholder="you@email.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 focus:border-church-500 outline-none transition text-base"
                        placeholder="••••••••">
                </div>
                <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-church-500 to-church-800 text-white font-semibold hover:from-church-600 hover:to-church-900 transition shadow-lg shadow-church-800/20">
                    Sign in
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                New member?
                <button type="button" @click="qrOpen = true" class="text-church-600 font-medium hover:underline">Register via QR code</button>
            </p>
        </div>

    </div>

    <div x-show="qrOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="qr-dialog-title">
        <div class="absolute inset-0 bg-church-900/50 backdrop-blur-sm" @click="qrOpen = false"></div>
        <div x-show="qrOpen" x-transition class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-100 p-6 sm:p-8 text-center">
            <button type="button" @click="qrOpen = false" aria-label="Close"
                class="absolute top-3 right-3 w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h2 id="qr-dialog-title" class="text-xl font-bold text-church-800 tracking-tight"><?= htmlspecialchars($churchName) ?></h2>
            <p class="text-church-500 text-sm font-semibold mt-1.5 tracking-wide"><?= htmlspecialchars($tagline) ?></p>
            <p class="text-slate-500 text-sm mt-3">Point your phone camera at the Qr code to open our welcome form.</p>

            <?php if (!empty($qrDataUri)): ?>
            <div class="mt-5 inline-block p-3 rounded-2xl bg-gradient-to-br from-church-50 to-white border border-church-100 shadow-lg shadow-church-800/5">
                <img src="<?= $qrDataUri ?>" alt="QR code for new member registration" width="200" height="200" class="w-44 h-44 sm:w-48 sm:h-48 rounded-xl bg-white">
            </div>
            <?php endif; ?>

            <a href="<?= htmlspecialchars($onboardingUrl ?? '/onboard/church-onboard-2026') ?>"
               class="inline-flex items-center gap-1.5 mt-5 text-sm font-semibold text-church-600 hover:text-church-800 transition">
                Or open the form directly
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </div>
</div>
