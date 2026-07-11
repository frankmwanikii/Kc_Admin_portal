<?php
use App\Services\FormSubmissionService;

$payload = $member['payload'] ?? [];
$profileSections = FormSubmissionService::joinProfileSections($payload);
$formTypeLabel = FormSubmissionService::formTypeLabel((string) ($member['form_type'] ?? 'join'));
$name = $member['submitter_name'] ?? 'Member';
$phone = trim((string) ($member['submitter_phone'] ?? ''));
$email = trim((string) ($member['submitter_email'] ?? ''));
$campus = ucfirst((string) ($member['campus_id'] ?? 'nanyuki'));
$status = $member['status'] ?? 'new';
$statusClass = match ($status) {
    'new' => 'member-profile-status--new',
    'reviewed' => 'member-profile-status--reviewed',
    'archived' => 'member-profile-status--archived',
    default => 'member-profile-status--default',
};
?>
<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">

<div class="member-profile-page">
    <a href="/admin/members" class="member-profile-back">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to members
    </a>

    <div class="member-profile-hero">
        <div class="member-profile-hero-inner">
            <div class="member-profile-hero-main">
                <span class="member-profile-status <?= $statusClass ?>"><?= ucfirst($status) ?></span>
                <h1 class="member-profile-name"><?= htmlspecialchars($name) ?></h1>
                <p class="member-profile-meta">Website registration · <?= htmlspecialchars($formTypeLabel) ?></p>
                <div class="member-profile-chips">
                    <?php if ($phone !== ''): ?>
                    <span class="member-profile-chip">
                        <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                        <?= htmlspecialchars($phone) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($email !== ''): ?>
                    <span class="member-profile-chip">
                        <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                        <?= htmlspecialchars($email) ?>
                    </span>
                    <?php endif; ?>
                    <span class="member-profile-chip">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                        <?= htmlspecialchars($campus) ?> campus
                    </span>
                    <span class="member-profile-chip">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        Joined <?= date('M j, Y', strtotime($member['created_at'])) ?>
                    </span>
                </div>
            </div>
            <div class="member-profile-hero-actions">
                <a href="/admin/communications?member=<?= (int) $member['id'] ?>" class="member-profile-action-btn">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Send message
                </a>
            </div>
        </div>
    </div>

    <div class="member-profile-grid">
        <div class="member-profile-card">
            <div class="member-profile-card-header">
                <h2>Registration details</h2>
                <p>All fields submitted via <?= htmlspecialchars($formTypeLabel) ?></p>
            </div>
            <?php if (empty($profileSections)): ?>
            <p class="member-profile-empty">No registration fields recorded.</p>
            <?php else: ?>
            <?php foreach ($profileSections as $section): ?>
            <div class="member-profile-section">
                <h3 class="member-profile-section-title"><?= htmlspecialchars($section['title']) ?></h3>
                <div class="member-profile-details">
                    <?php foreach ($section['rows'] as $row): ?>
                    <div class="member-profile-detail-row">
                        <span class="member-profile-detail-label"><?= htmlspecialchars($row['label']) ?></span>
                        <span class="member-profile-detail-value"><?= htmlspecialchars($row['value']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="member-profile-sidebar">
            <form method="post" action="/admin/members/<?= (int) $member['id'] ?>/status" class="member-profile-card">
                <div class="member-profile-card-header">
                    <h2>Admin review</h2>
                    <p>Update status and internal notes</p>
                </div>
                <div class="member-profile-form">
                    <div class="finance-field">
                        <label class="finance-label" for="member-status">Status</label>
                        <select id="member-status" name="status" class="finance-input">
                            <?php foreach (['new', 'reviewed', 'archived'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $status === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="finance-field">
                        <label class="finance-label" for="portal-notes">Portal notes</label>
                        <textarea id="portal-notes" name="portal_notes" rows="4" class="finance-input finance-textarea" placeholder="Internal follow-up notes…"><?= htmlspecialchars($member['portal_notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="member-profile-save-btn">Save changes</button>
                </div>
            </form>

            <div class="member-profile-card member-profile-card--muted">
                <div class="member-profile-card-header">
                    <h2>Record info</h2>
                </div>
                <dl class="member-profile-meta-list">
                    <div>
                        <dt>Registration ID</dt>
                        <dd>#<?= (int) $member['id'] ?></dd>
                    </div>
                    <div>
                        <dt>Submitted</dt>
                        <dd><?= date('F j, Y g:i A', strtotime($member['created_at'])) ?></dd>
                    </div>
                    <?php if (!empty($member['updated_at'])): ?>
                    <div>
                        <dt>Last updated</dt>
                        <dd><?= date('F j, Y g:i A', strtotime($member['updated_at'])) ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
