<?php

use App\Services\SettingsService;

$churchName = SettingsService::churchName();
?>
<div class="login-split">
    <div class="login-split__bg" aria-hidden="true"></div>
    <div class="login-split__divider" aria-hidden="true"></div>

    <section class="login-split__pane login-split__pane--brand">
        <div class="login-brand-block">
            <div class="login-brand-block__mark">
                <?php $size = 'xl'; $variant = 'dark'; $logoBg = 'none'; $rounded = 'rounded-none'; $imgClass = ''; require __DIR__ . '/../partials/church-logo.php'; ?>
            </div>
            <h1 class="login-brand-block__name"><?= htmlspecialchars($churchName) ?></h1>
        </div>
    </section>

    <section class="login-split__pane login-split__pane--form">
        <div class="login-panel">
            <h2 class="login-panel__welcome">Welcome</h2>
            <p class="login-panel__lead">Please login to admin dashboard.</p>

            <?php if (!empty($_GET['setup'])): ?>
            <div class="login-alert login-alert--success">Setup complete! Sign in with your admin credentials.</div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
            <div class="login-alert login-alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/login" class="login-form"
                  x-data="{ showPassword: false, showForgot: false, username: <?= htmlspecialchars(json_encode((string) ($username ?? '')), ENT_QUOTES, 'UTF-8') ?>, password: '' }">
                <?php if (!empty($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
                <?php endif; ?>

                <div class="login-field">
                    <label class="sr-only" for="login-username">Username</label>
                    <input type="text"
                           id="login-username"
                           name="username"
                           x-model="username"
                           required
                           autocomplete="username"
                           autofocus
                           class="login-input"
                           placeholder="Username">
                </div>

                <div class="login-field">
                    <label class="sr-only" for="login-password">Password</label>
                    <input :type="showPassword ? 'text' : 'password'"
                           id="login-password"
                           name="password"
                           x-model="password"
                           required
                           autocomplete="current-password"
                           class="login-input login-input--password"
                           placeholder="Password">
                    <button type="button"
                            class="login-toggle"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            :aria-pressed="showPassword">
                        <svg x-show="!showPassword" x-cloak width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                        <svg x-show="showPassword" x-cloak width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5a9.956 9.956 0 0 1-4.594-1.112M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>

                <button type="submit" class="login-submit">Login</button>

                <button type="button"
                        class="login-forgot"
                        @click="showForgot = !showForgot">
                    Forgotten your password?
                </button>
                <div x-show="showForgot" x-cloak class="login-alert login-alert--success login-forgot-note">
                    Contact your system administrator to reset your password.
                </div>

                <div class="login-demo">
                    <p class="login-demo__label">Demo</p>
                    <button type="button"
                            class="login-demo__user"
                            @click="username = 'Admin'; password = '12345678'"
                            title="Fill Admin demo credentials">
                        Admin
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
