<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-profile.css">

<?php
$smsProvider = $smsProvider ?? 'log';
$smsEnabled = $smsProvider !== 'log';
$hasLogo = !empty($currentLogo);
$sectionDefault = in_array(($_GET['tab'] ?? ''), ['branding', 'details', 'messaging', 'website'], true)
    ? (string) $_GET['tab']
    : 'branding';
?>

<div class="settings-layout"
     x-data="{
         section: '<?= htmlspecialchars($sectionDefault, ENT_QUOTES) ?>',
         smsEnabled: <?= $smsEnabled ? 'true' : 'false' ?>,
         smsProvider: '<?= htmlspecialchars($smsProvider, ENT_QUOTES) ?>',
         removeLogo: false,
         logoUrlMode: false
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

    <nav class="admin-profile-tabs settings-tabs" role="tablist" aria-label="Settings sections">
        <button type="button"
                role="tab"
                class="admin-profile-tabs__item"
                :class="section === 'branding' && 'admin-profile-tabs__item--active'"
                :aria-selected="section === 'branding'"
                @click="section = 'branding'; $nextTick(() => window.lucide?.createIcons())">
            <i data-lucide="image"></i>
            Branding
        </button>
        <button type="button"
                role="tab"
                class="admin-profile-tabs__item"
                :class="section === 'details' && 'admin-profile-tabs__item--active'"
                :aria-selected="section === 'details'"
                @click="section = 'details'; $nextTick(() => window.lucide?.createIcons())">
            <i data-lucide="building-2"></i>
            Church details
        </button>
        <button type="button"
                role="tab"
                class="admin-profile-tabs__item"
                :class="section === 'messaging' && 'admin-profile-tabs__item--active'"
                :aria-selected="section === 'messaging'"
                @click="section = 'messaging'; $nextTick(() => window.lucide?.createIcons())">
            <i data-lucide="message-square"></i>
            Messaging
        </button>
        <button type="button"
                role="tab"
                class="admin-profile-tabs__item"
                :class="section === 'website' && 'admin-profile-tabs__item--active'"
                :aria-selected="section === 'website'"
                @click="section = 'website'; $nextTick(() => window.lucide?.createIcons())">
            <i data-lucide="database"></i>
            Forms DB
        </button>
    </nav>

    <form method="POST" action="/admin/settings" enctype="multipart/form-data" class="settings-form">
        <input type="hidden" name="sms_provider" :value="smsEnabled ? smsProvider : 'log'">

        <!-- Branding -->
        <div class="admin-profile-tabs__panel" x-show="section === 'branding'" role="tabpanel">
            <section class="member-profile-card settings-panel-card">
                <div class="member-profile-card-header">
                    <h2>Church logo</h2>
                    <p>Shown in the sidebar, login page, and member portal.</p>
                </div>

                <?php if ($hasLogo): ?>
                <div class="settings-logo-current" :class="removeLogo && 'settings-logo-current--removing'">
                    <div class="settings-logo-current__media" aria-hidden="true">
                        <img src="<?= htmlspecialchars($currentLogo) ?>" alt="">
                    </div>
                    <div class="settings-logo-current__body">
                        <p class="settings-logo-current__title">Current logo</p>
                        <p class="settings-logo-current__hint">Replace it with a new upload below, or remove it.</p>
                        <label class="settings-switch settings-logo-current__remove">
                            <input type="checkbox"
                                   name="remove_logo"
                                   value="1"
                                   x-model="removeLogo"
                                   @change="if (removeLogo) { logoUrlMode = false; }">
                            <span class="settings-switch__track" aria-hidden="true"><span class="settings-switch__thumb"></span></span>
                            <span class="settings-switch__label">Remove logo</span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <div class="admin-dropzone-form admin-dropzone-form--embed"
                     x-show="!removeLogo"
                     x-cloak
                     x-data="adminPhotoDropzone({
                         maxBytes: 2 * 1024 * 1024,
                         multiple: false,
                         acceptSvg: true,
                         title: 'Drop logo here or click to upload',
                         hint: 'JPG, PNG, WebP, GIF or SVG · max 2 MB'
                     })">
                    <div class="admin-dropzone"
                         :class="{ 'is-drag': dragging, 'has-files': previews.length > 0 }"
                         @dragenter.prevent="dragging = true"
                         @dragover.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="onDrop($event); $root.logoUrlMode = false"
                         @click="if (!previews.length) openPicker()">
                        <input type="file"
                               id="church_logo"
                               name="church_logo"
                               class="sr-only"
                               accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                               x-ref="fileInput"
                               @change="onPick($event); $root.logoUrlMode = false"
                               @click.stop>

                        <template x-if="previews.length === 0">
                            <div class="admin-dropzone__idle">
                                <span class="admin-dropzone__icon" aria-hidden="true">
                                    <i data-lucide="cloud-upload"></i>
                                </span>
                                <strong class="admin-dropzone__title" x-text="title"></strong>
                                <span class="admin-dropzone__hint" x-text="hint"></span>
                            </div>
                        </template>

                        <div class="admin-dropzone__previews" x-show="previews.length > 0" x-cloak @click.stop>
                            <template x-for="(item, index) in previews" :key="item.name + '-' + index">
                                <div class="admin-dropzone__preview">
                                    <img :src="item.url" :alt="item.name">
                                    <span class="admin-dropzone__preview-name" x-text="item.name"></span>
                                    <button type="button"
                                            class="admin-dropzone__remove"
                                            @click="removeAt(index)"
                                            :aria-label="'Remove ' + item.name">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="settings-logo-url" x-show="!removeLogo" x-cloak>
                    <button type="button"
                            class="settings-logo-url__toggle"
                            @click="logoUrlMode = !logoUrlMode; $nextTick(() => window.lucide?.createIcons())">
                        <i data-lucide="link" class="w-4 h-4"></i>
                        <span x-text="logoUrlMode ? 'Hide image URL' : 'Or use an image URL'"></span>
                    </button>
                    <div class="finance-field" x-show="logoUrlMode" x-cloak>
                        <label class="finance-label" for="church_logo_url">Logo URL</label>
                        <input type="url"
                               id="church_logo_url"
                               name="church_logo_url"
                               value="<?= htmlspecialchars($logoUrl ?? '') ?>"
                               placeholder="https://yoursite.com/logo.png"
                               class="finance-input">
                        <p class="finance-field-hint">Uploading a file clears any saved URL.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Church details -->
        <div class="admin-profile-tabs__panel" x-show="section === 'details'" x-cloak role="tabpanel">
            <section class="member-profile-card settings-panel-card admin-profile-form">
                <div class="member-profile-card-header">
                    <h2>Church details</h2>
                    <p>Contact information shown across the portal.</p>
                </div>
                <div class="settings-fields">
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
            </section>
        </div>

        <!-- Messaging -->
        <div class="admin-profile-tabs__panel" x-show="section === 'messaging'" x-cloak role="tabpanel">
            <section class="member-profile-card settings-panel-card">
                <div class="member-profile-card-header settings-panel-header--split">
                    <div>
                        <h2>SMS &amp; messaging</h2>
                        <p>Used by Communications to send bulk and individual SMS.</p>
                    </div>
                    <span class="settings-provider-badge">Active: <?= htmlspecialchars($smsProviderLabel ?? 'Development') ?></span>
                </div>

                <div class="settings-fields">
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

                    <div x-show="smsEnabled" x-cloak class="settings-fields">
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
                            <p class="settings-provider-panel__title">Africa's Talking credentials</p>
                            <div class="settings-fields settings-fields--grid">
                                <div class="finance-field">
                                    <label class="finance-label" for="sms_username">Username</label>
                                    <input type="text" id="sms_username" name="sms_username" value="<?= htmlspecialchars($smsUsername ?? '') ?>" class="finance-input" placeholder="sandbox or live username">
                                </div>
                                <div class="finance-field">
                                    <label class="finance-label" for="sms_sender_id">Sender ID</label>
                                    <input type="text" id="sms_sender_id" name="sms_sender_id" value="<?= htmlspecialchars($smsSenderId ?? '') ?>" class="finance-input" placeholder="CHURCH">
                                </div>
                                <div class="finance-field settings-fields__full">
                                    <label class="finance-label" for="sms_api_key">API key</label>
                                    <input type="password" id="sms_api_key" name="sms_api_key" value="" class="finance-input" autocomplete="off" placeholder="<?= !empty($smsApiKey) ? '•••••••• (saved — leave blank to keep)' : 'Africa\'s Talking API key' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="settings-provider-panel settings-provider-panel--twilio" x-show="smsProvider === 'twilio'" x-cloak>
                            <p class="settings-provider-panel__title">Twilio credentials</p>
                            <div class="settings-fields settings-fields--grid">
                                <div class="finance-field">
                                    <label class="finance-label" for="twilio_account_sid">Account SID</label>
                                    <input type="text" id="twilio_account_sid" name="twilio_account_sid" value="<?= htmlspecialchars($twilioAccountSid ?? '') ?>" class="finance-input">
                                </div>
                                <div class="finance-field">
                                    <label class="finance-label" for="twilio_from_number">SMS from number</label>
                                    <input type="text" id="twilio_from_number" name="twilio_from_number" value="<?= htmlspecialchars($twilioFromNumber ?? '') ?>" class="finance-input" placeholder="+1234567890">
                                </div>
                                <div class="finance-field settings-fields__full">
                                    <label class="finance-label" for="twilio_whatsapp_from">WhatsApp from number</label>
                                    <input type="text" id="twilio_whatsapp_from" name="twilio_whatsapp_from" value="<?= htmlspecialchars($twilioWhatsappFrom ?? '') ?>" class="finance-input" placeholder="whatsapp:+14155238886">
                                    <p class="finance-field-hint">Use format <code>whatsapp:+254…</code> or just the number.</p>
                                </div>
                                <div class="finance-field settings-fields__full">
                                    <label class="finance-label" for="twilio_auth_token">Auth token</label>
                                    <input type="password" id="twilio_auth_token" name="twilio_auth_token" value="" class="finance-input" autocomplete="off" placeholder="<?= !empty($twilioAuthToken) ? '•••••••• (saved — leave blank to keep)' : 'Twilio auth token' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Shared forms database -->
        <div class="admin-profile-tabs__panel" x-show="section === 'website'" x-cloak role="tabpanel">
            <section class="member-profile-card settings-panel-card admin-profile-form">
                <div class="member-profile-card-header">
                    <h2>Website forms database</h2>
                    <p>Must match the public website config so Connect With Us submissions appear in Members.</p>
                </div>

                <div class="settings-fields">
                    <?php $formsDbStatus = $formsDbStatus ?? []; ?>
                    <?php if (!empty($formsDbStatus['connected']) && empty($formsDbStatus['warning']) && empty($formsDbStatus['error'])): ?>
                    <div class="admin-alert admin-alert--success">
                        Connected<?= isset($formsDbStatus['member_submissions'])
                            ? ' — ' . (int) $formsDbStatus['member_submissions'] . ' connect submission(s) found'
                            : '' ?>.
                    </div>
                    <?php elseif (!empty($formsDbStatus['warning'])): ?>
                    <div class="admin-alert admin-alert--error"><?= htmlspecialchars($formsDbStatus['warning']) ?></div>
                    <?php elseif (!empty($formsDbStatus['error'])): ?>
                    <div class="admin-alert admin-alert--error">Connection failed: <?= htmlspecialchars($formsDbStatus['error']) ?></div>
                    <?php endif; ?>

                    <div class="settings-fields settings-fields--grid">
                        <div class="finance-field">
                            <label class="finance-label" for="forms_db_host">Host</label>
                            <input type="text" id="forms_db_host" name="forms_db_host" value="<?= htmlspecialchars($formsDbHost ?? '127.0.0.1') ?>" class="finance-input">
                        </div>
                        <div class="finance-field">
                            <label class="finance-label" for="forms_db_port">Port</label>
                            <input type="text" id="forms_db_port" name="forms_db_port" value="<?= htmlspecialchars($formsDbPort ?? '3306') ?>" class="finance-input">
                        </div>
                        <div class="finance-field settings-fields__full">
                            <label class="finance-label" for="forms_db_name">Database name</label>
                            <input type="text" id="forms_db_name" name="forms_db_name" value="<?= htmlspecialchars($formsDbName ?? '') ?>" class="finance-input" placeholder="kingdomcity_forms">
                            <p class="finance-field-hint">Same database the public website writes to when visitors submit Connect forms.</p>
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
            </section>
        </div>

        <div class="settings-form-actions">
            <button type="submit" class="finance-btn-primary">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save settings
            </button>
        </div>
    </form>
</div>
