<?php

use App\Services\StaffService;

$person = $person ?? [];
$images = $images ?? [];
$statuses = $statuses ?? StaffService::STATUSES;
$employmentTypes = $employmentTypes ?? StaffService::EMPLOYMENT_TYPES;
$genders = $genders ?? StaffService::GENDERS;

$id = (int) ($person['id'] ?? 0);
$name = (string) ($person['name'] ?? 'Staff member');
$role = trim((string) ($person['role_title'] ?? ''));
$department = trim((string) ($person['department'] ?? ''));
$status = (string) ($person['status'] ?? 'active');
$phone = trim((string) ($person['phone'] ?? ''));
$email = trim((string) ($person['email'] ?? ''));
$photoUrl = StaffService::imageUrl($person['photo_path'] ?? null);
if (!$photoUrl && $images !== []) {
    $photoUrl = StaffService::imageUrl($images[0]['path'] ?? null);
}
$initials = StaffService::initials($name);

$statusClass = match ($status) {
    'active' => 'staff-status--active',
    'on_leave' => 'staff-status--leave',
    'inactive' => 'staff-status--inactive',
    default => 'staff-status--default',
};

$fmtDate = static function (?string $d): string {
    if (!$d) {
        return '—';
    }
    $ts = strtotime($d);

    return $ts ? date('M j, Y', $ts) : '—';
};

$age = null;
if (!empty($person['date_of_birth'])) {
    try {
        $dob = new DateTimeImmutable((string) $person['date_of_birth']);
        $age = $dob->diff(new DateTimeImmutable('today'))->y;
    } catch (Throwable) {
        $age = null;
    }
}

$tenure = null;
if (!empty($person['hire_date'])) {
    try {
        $hire = new DateTimeImmutable((string) $person['hire_date']);
        $end = !empty($person['end_date']) ? new DateTimeImmutable((string) $person['end_date']) : new DateTimeImmutable('today');
        $diff = $hire->diff($end);
        if ($diff->y > 0) {
            $tenure = $diff->y . ' yr' . ($diff->y === 1 ? '' : 's');
            if ($diff->m > 0) {
                $tenure .= ' ' . $diff->m . ' mo';
            }
        } elseif ($diff->m > 0) {
            $tenure = $diff->m . ' month' . ($diff->m === 1 ? '' : 's');
        } else {
            $tenure = $diff->d . ' day' . ($diff->d === 1 ? '' : 's');
        }
    } catch (Throwable) {
        $tenure = null;
    }
}
?>
<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">
<link rel="stylesheet" href="/css/admin-staff.css">

<div class="staff-profile-page" x-data="{ galleryOpen: false, gallerySrc: '', tab: <?= !empty($_GET['photo']) ? "'photos'" : "'overview'" ?> }">
    <a href="/admin/staff" class="member-profile-back">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to staff
    </a>

    <?php if (!empty($success)): ?>
    <div class="admin-alert admin-alert--success mb-4">
        <?php if (!empty($_GET['photo'])): ?>
            Photos updated.
        <?php elseif (!empty($_GET['added'])): ?>
            Staff member created. Complete their profile below.
        <?php else: ?>
            Staff profile saved.
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error mb-4"><?= htmlspecialchars(is_string($error) ? $error : 'Something went wrong.') ?></div>
    <?php endif; ?>

    <div class="staff-profile-hero">
        <div class="staff-profile-hero__media">
            <?php if ($photoUrl): ?>
            <button type="button"
                    class="staff-profile-hero__photo"
                    @click="galleryOpen = true; gallerySrc = '<?= htmlspecialchars($photoUrl, ENT_QUOTES) ?>'"
                    aria-label="View profile photo">
                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="<?= htmlspecialchars($name) ?>">
            </button>
            <?php else: ?>
            <div class="staff-profile-hero__photo staff-profile-hero__photo--empty" aria-hidden="true">
                <span><?= htmlspecialchars($initials) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="staff-profile-hero__body">
            <div class="staff-profile-hero__badges">
                <span class="staff-status <?= $statusClass ?>"><?= htmlspecialchars(StaffService::statusLabel($status)) ?></span>
                <?php if (!empty($person['employment_type'])): ?>
                <span class="staff-badge"><?= htmlspecialchars(StaffService::employmentLabel($person['employment_type'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($person['campus'])): ?>
                <span class="staff-badge"><?= htmlspecialchars(ucfirst((string) $person['campus'])) ?> campus</span>
                <?php endif; ?>
            </div>
            <h1 class="staff-profile-hero__title"><?= htmlspecialchars($name) ?></h1>
            <p class="staff-profile-hero__sub">
                <?= $role !== '' ? htmlspecialchars($role) : 'Staff member' ?>
                <?= $department !== '' ? ' · ' . htmlspecialchars($department) : '' ?>
                <?php if (!empty($person['staff_code'])): ?>
                · ID <?= htmlspecialchars((string) $person['staff_code']) ?>
                <?php endif; ?>
            </p>
            <div class="member-profile-chips staff-profile-chips">
                <?php if ($phone !== ''): ?>
                <a class="member-profile-chip" href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $phone) ?? $phone) ?>">
                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                    <?= htmlspecialchars($phone) ?>
                </a>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                <a class="member-profile-chip" href="mailto:<?= htmlspecialchars($email) ?>">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                    <?= htmlspecialchars($email) ?>
                </a>
                <?php endif; ?>
                <?php if (!empty($person['office_location'])): ?>
                <span class="member-profile-chip">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                    <?= htmlspecialchars((string) $person['office_location']) ?>
                </span>
                <?php endif; ?>
                <?php if ($tenure): ?>
                <span class="member-profile-chip">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    Tenure <?= htmlspecialchars($tenure) ?>
                </span>
                <?php endif; ?>
                <?php if ($age !== null): ?>
                <span class="member-profile-chip">
                    <i data-lucide="cake" class="w-3.5 h-3.5"></i>
                    Age <?= (int) $age ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="staff-profile-hero__actions">
                <?php if ($phone !== ''): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $phone) ?? $phone) ?>" class="staff-hero-btn">
                    <i data-lucide="phone" class="w-4 h-4"></i> Call
                </a>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                <a href="mailto:<?= htmlspecialchars($email) ?>" class="staff-hero-btn staff-hero-btn--solid">
                    <i data-lucide="mail" class="w-4 h-4"></i> Email
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="staff-profile-body">
        <nav class="admin-profile-tabs" role="tablist" aria-label="Staff profile sections">
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'overview' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'overview'"
                    @click="tab = 'overview'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="layout-dashboard"></i>
                Overview
            </button>
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'photos' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'photos'"
                    @click="tab = 'photos'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="images"></i>
                Photos
            </button>
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'profile' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'profile'"
                    @click="tab = 'profile'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="user-round"></i>
                Profile
            </button>
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'manage' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'manage'"
                    @click="tab = 'manage'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="settings"></i>
                Manage
            </button>
        </nav>

        <div class="admin-profile-tabs__panel staff-profile-panel-stack" x-show="tab === 'overview'" role="tabpanel">
            <div class="admin-profile-overview-span">
<?php if (!empty($person['bio'])): ?>
        <section class="member-profile-card staff-card">
            <div class="member-profile-card-header">
                <h2>About</h2>
            </div>
            <p class="staff-bio"><?= nl2br(htmlspecialchars((string) $person['bio'])) ?></p>
        </section>
        <?php endif; ?>
            </div>
            <div class="admin-profile-overview-grid">
<div class="member-profile-card member-profile-card--muted">
                <div class="member-profile-card-header">
                    <h2>At a glance</h2>
                </div>
                <dl class="member-profile-meta-list">
                    <div>
                        <dt>Role</dt>
                        <dd><?= $role !== '' ? htmlspecialchars($role) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Department</dt>
                        <dd><?= $department !== '' ? htmlspecialchars($department) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Employment</dt>
                        <dd><?= htmlspecialchars(StaffService::employmentLabel($person['employment_type'] ?? null)) ?></dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd><?= htmlspecialchars(StaffService::statusLabel($status)) ?></dd>
                    </div>
                    <div>
                        <dt>Reports to</dt>
                        <dd><?= !empty($person['reports_to']) ? htmlspecialchars((string) $person['reports_to']) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Started</dt>
                        <dd><?= $fmtDate($person['hire_date'] ?? null) ?></dd>
                    </div>
                    <div>
                        <dt>Tenure</dt>
                        <dd><?= $tenure ? htmlspecialchars($tenure) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Photos</dt>
                        <dd><?= count($images) ?></dd>
                    </div>
                </dl>
            </div>
<div class="member-profile-card member-profile-card--muted">
                <div class="member-profile-card-header">
                    <h2>Emergency</h2>
                </div>
                <dl class="member-profile-meta-list">
                    <div>
                        <dt>Name</dt>
                        <dd><?= !empty($person['emergency_contact_name']) ? htmlspecialchars((string) $person['emergency_contact_name']) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd><?= !empty($person['emergency_contact_phone']) ? htmlspecialchars((string) $person['emergency_contact_phone']) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Relation</dt>
                        <dd><?= !empty($person['emergency_contact_relation']) ? htmlspecialchars((string) $person['emergency_contact_relation']) : '—' ?></dd>
                    </div>
                </dl>
            </div>
<div class="member-profile-card member-profile-card--muted">
                <div class="member-profile-card-header">
                    <h2>Record info</h2>
                </div>
                <dl class="member-profile-meta-list">
                    <div>
                        <dt>Staff ID</dt>
                        <dd>#<?= $id ?></dd>
                    </div>
                    <div>
                        <dt>Created</dt>
                        <dd><?= !empty($person['created_at']) ? date('F j, Y g:i A', strtotime((string) $person['created_at'])) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Last updated</dt>
                        <dd><?= !empty($person['updated_at']) ? date('F j, Y g:i A', strtotime((string) $person['updated_at'])) : '—' ?></dd>
                    </div>
                </dl>
            </div>
            </div>
        </div>

        <div class="admin-profile-tabs__panel" x-show="tab === 'photos'" x-cloak role="tabpanel">
            <section class="member-profile-card staff-card">
                <div class="member-profile-card-header">
                    <h2>Photos</h2>
                    <p>Profile and gallery photos — JPG, PNG, WebP or GIF · max 5 MB each.</p>
                </div>

                <?php if ($images !== []): ?>
                <div class="staff-gallery">
                    <?php foreach ($images as $image): ?>
                    <?php
                        $imgUrl = StaffService::imageUrl($image['path'] ?? null);
                        if (!$imgUrl) {
                            continue;
                        }
                        $isPrimary = (int) ($image['is_primary'] ?? 0) === 1;
                    ?>
                    <figure class="staff-gallery__item <?= $isPrimary ? 'staff-gallery__item--primary' : '' ?>">
                        <button type="button"
                                class="staff-gallery__thumb"
                                @click="galleryOpen = true; gallerySrc = '<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>'">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars((string) ($image['caption'] ?? $name)) ?>">
                        </button>
                        <?php if ($isPrimary): ?>
                        <span class="staff-gallery__badge">Profile</span>
                        <?php endif; ?>
                        <?php if (!empty($image['caption'])): ?>
                        <figcaption><?= htmlspecialchars((string) $image['caption']) ?></figcaption>
                        <?php endif; ?>
                        <div class="staff-gallery__actions">
                            <?php if (!$isPrimary): ?>
                            <form method="post" action="/admin/staff/<?= $id ?>/images/<?= (int) $image['id'] ?>/primary">
                                <button type="submit" class="staff-gallery__btn">Set as profile</button>
                            </form>
                            <?php endif; ?>
                            <form method="post"
                                  action="/admin/staff/<?= $id ?>/images/<?= (int) $image['id'] ?>/delete"
                                  data-confirm="Remove this photo from the staff profile?"
                                  data-confirm-title="Remove photo?"
                                  data-confirm-label="Remove photo"
                                  data-confirm-tone="danger">
                                <button type="submit" class="staff-gallery__btn staff-gallery__btn--danger">Delete</button>
                            </form>
                        </div>
                    </figure>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php
                $uploadAction = '/admin/staff/' . $id . '/images';
                $inputId = 'staff-photos';
                $captionId = 'staff-photo-caption';
                $captionPlaceholder = 'e.g. Official headshot, team event…';
                $solo = $images === [];
                require __DIR__ . '/../partials/photo-dropzone.php';
                ?>
            </section>
        </div>

        <div class="admin-profile-tabs__panel" x-show="tab === 'profile'" x-cloak role="tabpanel">
<form method="post" action="/admin/staff/<?= $id ?>" class="member-profile-card staff-card admin-profile-form">
            <div class="member-profile-card-header">
                <h2>Full profile</h2>
                <p>Keep contact, role, and employment details up to date.</p>
            </div>

            <div class="staff-form-section">
                <h3 class="staff-form-section__title"><i data-lucide="user-round"></i> Identity</h3>
                <div class="staff-form-grid">
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-name">Full name <span class="finance-req">*</span></label>
                        <input type="text" id="staff-name" name="name" required class="finance-input"
                               value="<?= htmlspecialchars($name) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-code">Staff / employee ID</label>
                        <input type="text" id="staff-code" name="staff_code" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['staff_code'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-gender">Gender</label>
                        <select id="staff-gender" name="gender" class="finance-input">
                            <option value="">—</option>
                            <?php foreach ($genders as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= (($person['gender'] ?? '') === $val) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-dob">Date of birth</label>
                        <input type="date" id="staff-dob" name="date_of_birth" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['date_of_birth'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-national-id">National ID / passport</label>
                        <input type="text" id="staff-national-id" name="national_id" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['national_id'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-marital">Marital status</label>
                        <input type="text" id="staff-marital" name="marital_status" class="finance-input"
                               placeholder="e.g. Single, Married"
                               value="<?= htmlspecialchars((string) ($person['marital_status'] ?? '')) ?>">
                    </div>
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-bio">About / bio</label>
                        <textarea id="staff-bio" name="bio" rows="3" class="finance-input finance-textarea"
                                  placeholder="Short introduction, calling, background…"><?= htmlspecialchars((string) ($person['bio'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="staff-form-section">
                <h3 class="staff-form-section__title"><i data-lucide="briefcase"></i> Role & ministry</h3>
                <div class="staff-form-grid">
                    <div class="finance-field">
                        <label class="finance-label" for="staff-role">Role / title</label>
                        <input type="text" id="staff-role" name="role_title" class="finance-input"
                               placeholder="e.g. Worship Pastor, Sound Engineer"
                               value="<?= htmlspecialchars($role) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-department">Department</label>
                        <input type="text" id="staff-department" name="department" class="finance-input"
                               list="staff-dept-suggestions"
                               placeholder="e.g. Production, Administration"
                               value="<?= htmlspecialchars($department) ?>">
                        <datalist id="staff-dept-suggestions">
                            <option value="Pastoral"></option>
                            <option value="Worship"></option>
                            <option value="Production"></option>
                            <option value="Media"></option>
                            <option value="Administration"></option>
                            <option value="Finance"></option>
                            <option value="Hospitality"></option>
                            <option value="Children"></option>
                            <option value="Youth"></option>
                            <option value="Facilities"></option>
                        </datalist>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-employment">Employment type</label>
                        <select id="staff-employment" name="employment_type" class="finance-input">
                            <?php foreach ($employmentTypes as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= (($person['employment_type'] ?? 'full_time') === $val) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-status">Status</label>
                        <select id="staff-status" name="status" class="finance-input">
                            <?php foreach ($statuses as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-reports">Reports to</label>
                        <input type="text" id="staff-reports" name="reports_to" class="finance-input"
                               placeholder="Supervisor / lead pastor"
                               value="<?= htmlspecialchars((string) ($person['reports_to'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-campus">Campus</label>
                        <select id="staff-campus" name="campus" class="finance-input">
                            <option value="">—</option>
                            <?php foreach (['nanyuki' => 'Nanyuki', 'nairobi' => 'Nairobi'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= (($person['campus'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-ministries">Ministries involved</label>
                        <textarea id="staff-ministries" name="ministries" rows="2" class="finance-input finance-textarea"
                                  placeholder="e.g. Sunday service, Youth, Connect groups…"><?= htmlspecialchars((string) ($person['ministries'] ?? '')) ?></textarea>
                    </div>
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-skills">Skills & gifts</label>
                        <textarea id="staff-skills" name="skills" rows="2" class="finance-input finance-textarea"
                                  placeholder="e.g. Mixing, photography, counselling, teaching…"><?= htmlspecialchars((string) ($person['skills'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="staff-form-section">
                <h3 class="staff-form-section__title"><i data-lucide="phone"></i> Contact</h3>
                <div class="staff-form-grid">
                    <div class="finance-field">
                        <label class="finance-label" for="staff-phone">Primary phone</label>
                        <input type="tel" id="staff-phone" name="phone" class="finance-input"
                               value="<?= htmlspecialchars($phone) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-phone-2">Secondary phone</label>
                        <input type="tel" id="staff-phone-2" name="secondary_phone" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['secondary_phone'] ?? '')) ?>">
                    </div>
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-email">Email</label>
                        <input type="email" id="staff-email" name="email" class="finance-input"
                               value="<?= htmlspecialchars($email) ?>">
                    </div>
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-address">Home address</label>
                        <input type="text" id="staff-address" name="address" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['address'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-city">City / town</label>
                        <input type="text" id="staff-city" name="city" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['city'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-office">Office / desk location</label>
                        <input type="text" id="staff-office" name="office_location" class="finance-input"
                               placeholder="e.g. Admin block, Media booth"
                               value="<?= htmlspecialchars((string) ($person['office_location'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="staff-form-section">
                <h3 class="staff-form-section__title"><i data-lucide="heart-handshake"></i> Emergency contact</h3>
                <div class="staff-form-grid">
                    <div class="finance-field">
                        <label class="finance-label" for="staff-ec-name">Contact name</label>
                        <input type="text" id="staff-ec-name" name="emergency_contact_name" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['emergency_contact_name'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-ec-phone">Contact phone</label>
                        <input type="tel" id="staff-ec-phone" name="emergency_contact_phone" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['emergency_contact_phone'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-ec-relation">Relationship</label>
                        <input type="text" id="staff-ec-relation" name="emergency_contact_relation" class="finance-input"
                               placeholder="e.g. Spouse, Parent, Sibling"
                               value="<?= htmlspecialchars((string) ($person['emergency_contact_relation'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="staff-form-section">
                <h3 class="staff-form-section__title"><i data-lucide="calendar-days"></i> Employment timeline</h3>
                <div class="staff-form-grid">
                    <div class="finance-field">
                        <label class="finance-label" for="staff-hire">Hire / start date</label>
                        <input type="date" id="staff-hire" name="hire_date" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['hire_date'] ?? '')) ?>">
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="staff-end">End date (if applicable)</label>
                        <input type="date" id="staff-end" name="end_date" class="finance-input"
                               value="<?= htmlspecialchars((string) ($person['end_date'] ?? '')) ?>">
                    </div>
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-schedule">Work schedule</label>
                        <input type="text" id="staff-schedule" name="work_schedule" class="finance-input"
                               placeholder="e.g. Mon–Fri 9am–5pm, Sundays on duty"
                               value="<?= htmlspecialchars((string) ($person['work_schedule'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="staff-form-section">
                <h3 class="staff-form-section__title"><i data-lucide="graduation-cap"></i> Background</h3>
                <div class="staff-form-grid">
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-education">Education</label>
                        <textarea id="staff-education" name="education" rows="2" class="finance-input finance-textarea"
                                  placeholder="Schools, certificates, theological training…"><?= htmlspecialchars((string) ($person['education'] ?? '')) ?></textarea>
                    </div>
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-languages">Languages</label>
                        <input type="text" id="staff-languages" name="languages" class="finance-input"
                               placeholder="e.g. English, Swahili, Kikuyu"
                               value="<?= htmlspecialchars((string) ($person['languages'] ?? '')) ?>">
                    </div>
                    <div class="finance-field staff-field--full">
                        <label class="finance-label" for="staff-notes">Internal notes</label>
                        <textarea id="staff-notes" name="notes" rows="3" class="finance-input finance-textarea"
                                  placeholder="HR notes, access cards, reminders…"><?= htmlspecialchars((string) ($person['notes'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="staff-form-footer">
                <button type="submit" class="member-profile-save-btn"><i data-lucide="save" class="w-4 h-4"></i> Save profile</button>
            </div>
        </form>
        </div>

        <div class="admin-profile-tabs__panel" x-show="tab === 'manage'" x-cloak role="tabpanel">
<form method="post"
                  action="/admin/staff/<?= $id ?>/delete"
                  class="staff-danger-card"
                  data-confirm="Permanently delete this staff member? All profile photos will also be removed."
                  data-confirm-title="Delete staff member?"
                  data-confirm-label="Delete staff"
                  data-confirm-tone="danger">
                <p class="staff-danger-card__title">Danger zone</p>
                <p class="staff-danger-card__text">Permanently remove this staff record and all photos.</p>
                <button type="submit" class="staff-danger-card__btn">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Delete staff
                </button>
            </form>
        </div>
    </div>

    <div class="staff-lightbox"
         x-show="galleryOpen"
         x-cloak
         @keydown.escape.window="galleryOpen = false"
         @click.self="galleryOpen = false">
        <button type="button" class="staff-lightbox__close" @click="galleryOpen = false" aria-label="Close">
            <i data-lucide="x"></i>
        </button>
        <img :src="gallerySrc" alt="" class="staff-lightbox__img">
    </div>
</div>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
