<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">

<div class="settings-layout" x-data="{ section: 'branding' }">
    <div class="settings-hero">
        <h2>Church settings</h2>
        <p>Manage branding, contact details, and SMS messaging providers (Twilio or Africa's Talking).</p>
    </div>

    <?php if (!empty($success)): ?>
    <div class="admin-alert admin-alert--success">Settings saved successfully.</div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="settings-nav-wrap">
        <div class="settings-nav" role="tablist" aria-label="Settings sections">
            <button type="button" role="tab" class="settings-nav-btn" :class="section === 'branding' && 'settings-nav-btn--active'" @click="section = 'branding'">Branding</button>
            <button type="button" role="tab" class="settings-nav-btn" :class="section === 'details' && 'settings-nav-btn--active'" @click="section = 'details'">Church details</button>
            <button type="button" role="tab" class="settings-nav-btn" :class="section === 'messaging' && 'settings-nav-btn--active'" @click="section = 'messaging'">Messaging</button>
            <button type="button" role="tab" class="settings-nav-btn" :class="section === 'website' && 'settings-nav-btn--active'" @click="section = 'website'">Forms DB</button>
        </div>
    </div>

    <form method="POST" action="/admin/settings" enctype="multipart/form-data" class="settings-form" x-data="{ logoMode: '<?= !empty($logoUrl) ? 'url' : 'upload' ?>' }">
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
                        <label class="settings-logo-preview__remove">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded text-red-600">
                            Remove logo
                        </label>
                    </div>
                </div>
                <?php endif; ?>

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
                <div class="finance-field">
                    <label class="finance-label" for="sms_provider">SMS provider</label>
                    <select id="sms_provider" name="sms_provider" class="finance-input" x-data x-model="$el.value">
                        <option value="log" <?= ($smsProvider ?? 'log') === 'log' ? 'selected' : '' ?>>Development (log only)</option>
                        <option value="africas_talking" <?= ($smsProvider ?? '') === 'africas_talking' ? 'selected' : '' ?>>Africa's Talking</option>
                        <option value="twilio" <?= ($smsProvider ?? '') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                    </select>
                </div>

                <div class="settings-provider-panel settings-provider-panel--at">
                    <p class="text-sm font-semibold text-church-800 mb-3">Africa's Talking</p>
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

                <div class="settings-provider-panel settings-provider-panel--twilio">
                    <p class="text-sm font-semibold text-church-800 mb-3">Twilio</p>
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
                            <p class="text-xs text-slate-400 mt-1">Twilio WhatsApp sender. Use format <code class="text-church-600">whatsapp:+254…</code> or just the number.</p>
                        </div>
                        <div class="finance-field sm:col-span-2">
                            <label class="finance-label" for="twilio_auth_token">Auth token</label>
                            <input type="password" id="twilio_auth_token" name="twilio_auth_token" value="" class="finance-input" autocomplete="off" placeholder="<?= !empty($twilioAuthToken) ? '•••••••• (saved — leave blank to keep)' : 'Twilio auth token' ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shared forms database (Kc_website) -->
        <div x-show="section === 'website'" x-cloak class="settings-card">
            <div class="settings-card-header">
                <h3>Website forms database</h3>
                <p>Must match <code class="text-church-600">Kc_website/includes/database-config.php</code> so Connect With Us submissions appear in Members.</p>
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
            <button type="submit" class="finance-btn-primary px-8">Save settings</button>
        </div>
    </form>
</div>
