<?php

use App\Services\SettingsService;

$churchName = SettingsService::churchName();
?>
<div class="min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="flex justify-center">
                <?php $size = 'xl'; $variant = 'dark'; require __DIR__ . '/../partials/church-logo.php'; ?>
            </div>
            <h1 class="mt-4 text-2xl sm:text-3xl font-bold text-church-800 tracking-tight"><?= htmlspecialchars($churchName) ?></h1>
            <p class="mt-5 text-lg font-semibold text-church-900">Admin sign in</p>
            <p class="text-slate-500 mt-1 text-sm">Sign in with your administrator account</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6 sm:p-8">
            <?php if (!empty($_GET['setup'])): ?>
            <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm border border-emerald-100">Setup complete! Sign in with your admin credentials.</div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-100"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/login" class="space-y-5" x-data="{ showPassword: false }">
                <?php if (!empty($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="login-email">Email address</label>
                    <input type="email" id="login-email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required
                        autocomplete="email"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 focus:border-church-500 outline-none transition text-base"
                        placeholder="Enter your email">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="login-password">Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               id="login-password"
                               name="password"
                               required
                               autocomplete="current-password"
                               class="w-full px-4 py-3 pr-12 rounded-xl border border-slate-200 focus:ring-2 focus:ring-church-500 focus:border-church-500 outline-none transition text-base"
                               placeholder="Enter your password">
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-slate-400 hover:text-slate-600 transition rounded-r-xl"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                :aria-pressed="showPassword">
                            <svg x-show="!showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5a9.956 9.956 0 0 1-4.594-1.112M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-church-500 to-church-800 text-white font-semibold hover:from-church-600 hover:to-church-900 transition shadow-lg shadow-church-800/20">
                    Sign in
                </button>
            </form>
        </div>
    </div>
</div>
