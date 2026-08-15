<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-profile.css">

<?php
/** @var \App\Models\User $user */
$avatarUrl = $user->avatarUrl();
$initials = $user->initials();
?>

<div class="profile-layout">
    <div class="settings-hero profile-hero">
        <div class="profile-hero__copy">
            <p class="profile-hero__eyebrow">Account</p>
            <h2>My profile</h2>
            <p>Update your name, contact details, login credentials, and profile photo.</p>
        </div>
        <div class="profile-hero__avatar" aria-hidden="true">
            <?php if ($avatarUrl): ?>
            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="">
            <?php else: ?>
            <span><?= htmlspecialchars($initials) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($success)): ?>
    <div class="admin-alert admin-alert--success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST"
          action="/admin/profile"
          enctype="multipart/form-data"
          class="settings-form profile-form"
          x-data="{
              preview: <?= htmlspecialchars(json_encode($avatarUrl), ENT_QUOTES) ?>,
              removeAvatar: false,
              onFile(event) {
                  const file = event.target.files && event.target.files[0];
                  if (!file) return;
                  this.removeAvatar = false;
                  const reader = new FileReader();
                  reader.onload = (e) => { this.preview = e.target.result; };
                  reader.readAsDataURL(file);
              }
          }">

        <div class="settings-card">
            <div class="settings-card-header">
                <h3>Profile photo</h3>
                <p>Shown in the top bar across the admin portal</p>
            </div>
            <div class="settings-card-body profile-avatar-row">
                <div class="profile-avatar-preview">
                    <template x-if="preview && !removeAvatar">
                        <img :src="preview" alt="Profile preview">
                    </template>
                    <template x-if="!preview || removeAvatar">
                        <span class="profile-avatar-preview__fallback"><?= htmlspecialchars($initials) ?></span>
                    </template>
                </div>
                <div class="profile-avatar-actions">
                    <label class="finance-btn-secondary profile-upload-btn">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        Upload image
                        <input type="file"
                               name="avatar"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               class="sr-only"
                               @change="onFile($event)">
                    </label>
                    <?php if ($avatarUrl): ?>
                    <label class="settings-switch profile-remove-switch">
                        <input type="checkbox"
                               name="remove_avatar"
                               value="1"
                               x-model="removeAvatar">
                        <span class="settings-switch__track" aria-hidden="true"><span class="settings-switch__thumb"></span></span>
                        <span class="settings-switch__label">Remove current photo</span>
                    </label>
                    <?php endif; ?>
                    <p class="text-xs text-slate-400">JPG, PNG, WebP or GIF. Max 2 MB.</p>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-header">
                <h3>Profile details</h3>
                <p>How you appear and how we can reach you</p>
            </div>
            <div class="settings-card-body space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="finance-field">
                        <label class="finance-label" for="display_name">Display name</label>
                        <input type="text"
                               id="display_name"
                               name="display_name"
                               value="<?= htmlspecialchars($user->display_name ?? '') ?>"
                               class="finance-input"
                               placeholder="e.g. Pastor James">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="username">Username</label>
                        <input type="text"
                               id="username"
                               name="username"
                               value="<?= htmlspecialchars($user->username ?? '') ?>"
                               required
                               autocomplete="username"
                               class="finance-input">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="email">Email</label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="<?= htmlspecialchars($user->email) ?>"
                               required
                               autocomplete="email"
                               class="finance-input">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="phone">Phone</label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               value="<?= htmlspecialchars($user->phone ?? '') ?>"
                               class="finance-input"
                               placeholder="+254…">
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-header">
                <h3>Password</h3>
                <p>Required when changing username, email, or password</p>
            </div>
            <div class="settings-card-body space-y-4">
                <div class="finance-field">
                    <label class="finance-label" for="current_password">Current password</label>
                    <input type="password"
                           id="current_password"
                           name="current_password"
                           class="finance-input"
                           autocomplete="current-password"
                           placeholder="Enter current password">
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="finance-field">
                        <label class="finance-label" for="new_password">New password</label>
                        <input type="password"
                               id="new_password"
                               name="new_password"
                               class="finance-input"
                               autocomplete="new-password"
                               placeholder="Leave blank to keep">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="confirm_password">Confirm new password</label>
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               class="finance-input"
                               autocomplete="new-password"
                               placeholder="Repeat new password">
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-form-actions">
            <button type="submit" class="finance-btn-primary px-8">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save profile
            </button>
        </div>
    </form>
</div>
