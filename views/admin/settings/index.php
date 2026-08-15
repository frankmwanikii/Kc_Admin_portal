<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-profile.css">

<?php
$smsProvider = $smsProvider ?? 'log';
$smsEnabled = $smsProvider !== 'log';
$logoModeDefault = !empty($logoUrl) ? 'url' : 'upload';
?>

<div class="settings-layout"
     x-data="{
         section: 'branding',
         logoMode: '<?= $logoModeDefault ?>',
         smsEnabled: <?= $smsEnabled ? 'true' : 'false' ?>,
         smsProvider: '<?= htmlspecialchars($smsProvider, ENT_QUOTES) ?>',
         removeLogo: false
     }">
    <div class="settings-hero">
        <p class="profile-hero__eyebrow">System</p>
        <h2>Church settings</h2>
        <p>Manage branding, contact details, SMS providers, and the website forms database.</p>
    </div>

    <?php if (!empty($success)): ?>
    <div class="admin-alert admin-alert--success">Settings saved successfully.</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="settings-nav-wrap">
        <div class="settings-nav" role="tablist" aria-label="Settings sections">
            <button type="button" role="tab" class="settings-nav-btn" :class="section === 'branding' && 'settings-nav-btn--active'" @click="section = 'branding'">
                <i data-lucide="image" class="w-4 h-4"></i>
                Branding
            </button>
            <button type="button" role="tab" class="settings-nav-btn" :class="section === 'details' && 'settings-nav-btn--active'" @click="section = 'details'">
                <i data-lucide="building-2" class="w-4 h-4"></i>
                Church details
            </button>
            <button type="button" role="tab" class="settings-nav-btn" :class="section === 'messaging' && 'settings-nav-btn--active'" @click="section = 'messaging'">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                Messaging
            </button>
            <button type="button" role="tab" class="settings-nav-btn" :class="section === 'website' && 'settings-nav-btn--active'" @click="section = 'website'">
                <i data-lucide="database" class="w-4 h-4"></i>
                Forms DB
            </button>
        </div>
    </div>

    <form method="POST" action="/admin/settings" enctype="multipart/form-data" class="settings-form">
        <input type="hidden" name="sms_provider" :value="smsEnabled ? smsProvider : 'log'">

        <!-- Branding -->
        <div x-show="section === 'branding'" x-cloak class="settings-card">
            <div class="settings-card-header">
                <h3>Church logo</h3>
                <p>Shown in the sidebar, login page, and member portal</p>
            </div>
            <div class="settings-card-body space-y-5">
                <?php if (!empty($currentLogo)): ?>
                <div class="settings-logo-preview">
                    <div class="settings-logo-preview__media">
                        <img src="<?= htmlspecialchars($currentLogo) ?>" alt="Current logo">
                    </div>
                    <div class="settings-logo-preview__body">
                        <p class="settings-logo-preview__title">Current logo</p>
                        <p class="settings-logo-preview__url"><?= htmlspecialchars($currentLogo) ?></p>
                        <label class="settings-switch settings-logo-preview__remove">
                            <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo">
                            <span class="settings-switch__track" aria-hidden="true"><span class="settings-switch__thumb"></span></span>
                            <span class="settings-switch__label">Remove logo</span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <div class="settings-switch-row">
                    <div class="settings-switch-row__copy">
                        <strong>Logo source</strong>
                        <span>Upload a file or paste an image URL</span>
                    </div>
                </div>

                <div class="settings-logo-mode">
                    <button type="button"
                            class="settings-logo-mode__btn"
                            :class="logoMode === 'upload' && 'settings-logo-mode__btn--active'"
                            @click="logoMode = 'upload'">Upload file</button>
                    <button type="button"
                            class="settings-logo-mode__btn"
                            :class="logoMode === 'url' && 'settings-logo-mode__btn--active'"
                            @click="logoMode = 'url'">Image URL</button>
                </div>

                <div x-show="logoMode === 'upload'" x-cloak>
                    <label class="finance-label" for="church_logo">Upload logo</label>
                    <input type="file"
                           id="church_logo"
                           :name="logoMode === 'upload' ? 'church_logo' : null"
                           accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                           class="settings-file-input">
                    <p class="text-xs text-slate-400 mt-1.5">JPG, PNG, WebP, GIF or SVG. Max 2 MB.</p>
                </div>

                <div x-show="logoMode === 'url'" x-cloak>
                    <label class="finance-label" for="church_logo_url">Logo URL</label>
                    <input type="url"
                           id="church_logo_url"
                           :name="logoMode === 'url' ? 'church_logo_url' : null"
                           value="<?= htmlspecialchars($logoUrl ?? '') ?>"
                           placeholder="https://yoursite.com/logo.png"
                           class="finance-input">
                </div>
            </div>
        </div>

        <!-- Church details -->
        <div x-show="section === 'details'" x-cloak class="settings-card">
            <div class="settings-card-header">
                <h3>Church details</h3>
                <p>Contact information shown across the portal</p>
            </div>
            <div class="settings-card-body space-y-4">
                <div class="finance-field">
                    <label class="finance-label" for="church_name">Church name</label>
                    <input type="text" id="church_name" name="church_name" value="<?= htmlspecialchars($churchName ?? '') ?>" required class="finance-input">
                </div>
                <div class="finance-field">
                    <label class="finance-label" for="church_address">Address</label>
                    <input type="text" id="church_address" name="church_address" value="<?= htmlspecialchars($churchAddress ?? '') ?>" class="finance-input">
                </div>
                <div class="finance-field">
                    <label class="finance-label" for="church_phone">Phone</label>
                    <input type="text" id="church_phone" name="church_phone" value="<?= htmlspecialchars($churchPhone ?? '') ?>" class="finance-input">
                </div>
            </div>
        </div>

        <!-- Messaging -->
        <div x-show="section === 'messaging'" x-cloak class="settings-card">
            <div class="settings-card-header settings-card-header--split">
                <div>
                    <h3>SMS & messaging</h3>
                    <p>Used by Communications to send bulk and individual SMS</p>
                </div>
                <span class="settings-provider-badge">Active: <?= htmlspecialchars($smsProviderLabel ?? 'Development') ?></span>
            </div>
            <div class="settings-card-body space-y-4">
                <label class="settings-switch-row">
                    <div class="settings-switch-row__copy">
                        <strong>Enable live SMS</strong>
                        <span>When off, messages are logged only (development mode)</span>
                    </div>
                    <span class="settings-switch">
                        <input type="checkbox"
                               x-model="smsEnabled"
                               @change="if (smsEnabled && smsProvider === 'log') smsProvider = 'africas_talking'">
                        <span class="settings-switch__track" aria-hidden="true"><span class="settings-switch__thumb"></span></span>
                    </span>
                </label>

                <div x-show="smsEnabled" x-cloak class="space-y-4">
                    <div>
                        <p class="finance-label mb-2">Provider</p>
                        <div class="settings-provider-cards">
                            <button type="button"
                                    class="settings-provider-card"
                                    :class="smsProvider === 'africas_talking' && 'settings-provider-card--active'"
                                    @click="smsProvider = 'africas_talking'">
                                <strong>Africa's Talking</strong>
                                <span>Popular for Kenya &amp; East Africa SMS</span>
                            </button>
                            <button type="button"
                                    class="settings-provider-card"
                                    :class="smsProvider === 'twilio' && 'settings-provider-card--active'"
                                    @click="smsProvider = 'twilio'">
                                <strong>Twilio</strong>
                                <span>SMS &amp; WhatsApp messaging</span>
                            </button>
                            <button type="button"
                                    class="settings-provider-card"
                                    :class="smsProvider === 'log' && 'settings-provider-card--active'"
                                    @click="smsProvider = 'log'; smsEnabled = false">
                                <strong>Log only</strong>
                                <span>Safe for testing — no messages sent</span>
                            </button>
                        </div>
                    </div>

                    <div class="settings-provider-panel settings-provider-panel--at" x-show="smsProvider === 'africas_talking'" x-cloak>
                        <p class="text-sm font-semibold text-church-800 mb-3">Africa's Talking credentials</p>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="finance-field">
                                <label class="finance-label" for="sms_username">Username</label>
                                <input type="text" id="sms_username" name="sms_username" value="<?= htmlspecialchars($smsUsername ?? '') ?>" class="finance-input" placeholder="sandbox or live username">
                            </div>
                            <div class="finance-field">
                                <label class="finance-label" for="sms_sender_id">Sender ID</label>
                                <input type="text" id="sms_sender_id" name="sms_sender_id" value="<?= htmlspecialchars($smsSenderId ?? '') ?>" class="finance-input" placeholder="CHURCH">
                            </div>
                            <div class="finance-field sm:col-span-2">
                                <label class="finance-label" for="sms_api_key">API key</label>
                                <input type="password" id="sms_api_key" name="sms_api_key" value="" class="finance-input" autocomplete="off" placeholder="<?= !empty($smsApiKey) ? '•••••••• (saved — leave blank to keep)' : 'Africa\'s Talking API key' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="settings-provider-panel settings-provider-panel--twilio" x-show="smsProvider === 'twilio'" x-cloak>
                        <p class="text-sm font-semibold text-church-800 mb-3">Twilio credentials</p>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="finance-field">
                                <label class="finance-label" for="twilio_account_sid">Account SID</label>
                                <input type="text" id="twilio_account_sid" name="twilio_account_sid" value="<?= htmlspecialchars($twilioAccountSid ?? '') ?>" class="finance-input">
                            </div>
                            <div class="finance-field">
                                <label class="finance-label" for="twilio_from_number">SMS from number</label>
                                <input type="text" id="twilio_from_number" name="twilio_from_number" value="<?= htmlspecialchars($twilioFromNumber ?? '') ?>" class="finance-input" placeholder="+1234567890">
                            </div>
                            <div class="finance-field sm:col-span-2">
                                <label class="finance-label" for="twilio_whatsapp_from">WhatsApp from number</label>
                                <input type="text" id="twilio_whatsapp_from" name="twilio_whatsapp_from" value="<?= htmlspecialchars($twilioWhatsappFrom ?? '') ?>" class="finance-input" placeholder="whatsapp:+14155238886">
                                <p class="text-xs text-slate-400 mt-1">Use format <code class="text-church-600">whatsapp:+254…</code> or just the number.</p>
                            </div>
                            <div class="finance-field sm:col-span-2">
                                <label class="finance-label" for="twilio_auth_token">Auth token</label>
                                <input type="password" id="twilio_auth_token" name="twilio_auth_token" value="" class="finance-input" autocomplete="off" placeholder="<?= !empty($twilioAuthToken) ? '•••••••• (saved — leave blank to keep)' : 'Twilio auth token' ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shared forms database -->
        <div x-show="section === 'website'" x-cloak class="settings-card">
            <div class="settings-card-header">
                <h3>Website forms database</h3>
                <p>Must match the public website config so Connect With Us submissions appear in Members.</p>
            </div>
            <div class="settings-card-body space-y-4">
                <?php $formsDbStatus = $formsDbStatus ?? []; ?>
                <?php if (!empty($formsDbStatus['connected']) && empty($formsDbStatus['warning']) && empty($formsDbStatus['error'])): ?>
                <div class="admin-alert admin-alert--success">
                    Connected to <strong><?= htmlspecialchars($formsDbStatus['database'] ?? '') ?></strong>
                    — <?= (int) ($formsDbStatus['member_submissions'] ?? 0) ?> connect submission(s) found.
                </div>
                <?php elseif (!empty($formsDbStatus['warning'])): ?>
                <div class="admin-alert admin-alert--error"><?= htmlspecialchars($formsDbStatus['warning']) ?></div>
                <?php elseif (!empty($formsDbStatus['error'])): ?>
                <div class="admin-alert admin-alert--error">Connection failed: <?= htmlspecialchars($formsDbStatus['error']) ?></div>
                <?php endif; ?>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="finance-field">
                        <label class="finance-label" for="forms_db_host">Host</label>
                        <input type="text" id="forms_db_host" name="forms_db_host" value="<?= htmlspecialchars($formsDbHost ?? '127.0.0.1') ?>" class="finance-input">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="forms_db_port">Port</label>
                        <input type="text" id="forms_db_port" name="forms_db_port" value="<?= htmlspecialchars($formsDbPort ?? '3306') ?>" class="finance-input">
                    </div>
                    <div class="finance-field sm:col-span-2">
                        <label class="finance-label" for="forms_db_name">Database name</label>
                        <input type="text" id="forms_db_name" name="forms_db_name" value="<?= htmlspecialchars($formsDbName ?? '') ?>" class="finance-input" placeholder="kingdomcity_forms">
                        <p class="text-xs text-slate-400 mt-1">Same database the public website writes to when visitors submit Connect forms.</p>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="forms_db_username">Username</label>
                        <input type="text" id="forms_db_username" name="forms_db_username" value="<?= htmlspecialchars($formsDbUsername ?? '') ?>" class="finance-input">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="forms_db_password">Password</label>
                        <input type="password" id="forms_db_password" name="forms_db_password" value="" class="finance-input" autocomplete="off" placeholder="Leave blank to keep current password">
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-form-actions">
            <button type="submit" class="finance-btn-primary px-8">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save settings
            </button>
        </div>
    </form>
</div>
