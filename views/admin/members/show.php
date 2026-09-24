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

<div class="member-profile-page" x-data="{ tab: 'registration' }">
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

    <div class="member-profile-body">
        <nav class="admin-profile-tabs" role="tablist" aria-label="Member profile sections">
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'registration' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'registration'"
                    @click="tab = 'registration'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="file-text"></i>
                Registration
            </button>
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'review' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'review'"
                    @click="tab = 'review'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="clipboard-check"></i>
                Review
            </button>
            <button type="button"
                    role="tab"
                    class="admin-profile-tabs__item"
                    :class="tab === 'advanced' && 'admin-profile-tabs__item--active'"
                    :aria-selected="tab === 'advanced'"
                    @click="tab = 'advanced'; $nextTick(() => window.lucide?.createIcons())">
                <i data-lucide="settings"></i>
                Advanced
            </button>
        </nav>

        <div class="admin-profile-tabs__panel" x-show="tab === 'registration'" role="tabpanel">
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
        </div>

        <div class="admin-profile-tabs__panel member-profile-panel-stack" x-show="tab === 'review'" x-cloak role="tabpanel">
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
        </div>

        <div class="admin-profile-tabs__panel member-profile-panel-stack" x-show="tab === 'advanced'" x-cloak role="tabpanel">
            <div class="member-profile-card">
                <div class="member-profile-card-header">
                    <h2>Additional settings</h2>
                    <p>Quick actions and record metadata for this registration.</p>
                </div>
                <div class="member-profile-form member-advanced-actions">
                    <a href="/admin/members/<?= (int) $member['id'] ?>/pdf"
                       class="member-advanced-action"
                       download>
                        <span class="member-advanced-action__icon"><i data-lucide="file-text"></i></span>
                        <span class="member-advanced-action__copy">
                            <strong>Download PDF</strong>
                            <span>Export this registration as a printable PDF</span>
                        </span>
                        <i data-lucide="download" class="member-advanced-action__chevron"></i>
                    </a>

                    <a href="/admin/members/<?= (int) $member['id'] ?>/csv"
                       class="member-advanced-action"
                       download>
                        <span class="member-advanced-action__icon"><i data-lucide="file-spreadsheet"></i></span>
                        <span class="member-advanced-action__copy">
                            <strong>Download CSV</strong>
                            <span>Export fields as a spreadsheet-friendly CSV file</span>
                        </span>
                        <i data-lucide="download" class="member-advanced-action__chevron"></i>
                    </a>

                    <a href="/admin/communications?member=<?= (int) $member['id'] ?>" class="member-advanced-action">
                        <span class="member-advanced-action__icon"><i data-lucide="send"></i></span>
                        <span class="member-advanced-action__copy">
                            <strong>Send message</strong>
                            <span>Open Communications with this member selected</span>
                        </span>
                        <i data-lucide="chevron-right" class="member-advanced-action__chevron"></i>
                    </a>

                    <?php if ($status !== 'archived'): ?>
                    <form method="post" action="/admin/members/<?= (int) $member['id'] ?>/status" class="member-advanced-action-form">
                        <input type="hidden" name="status" value="archived">
                        <input type="hidden" name="portal_notes" value="<?= htmlspecialchars($member['portal_notes'] ?? '') ?>">
                        <button type="submit" class="member-advanced-action">
                            <span class="member-advanced-action__icon"><i data-lucide="archive"></i></span>
                            <span class="member-advanced-action__copy">
                                <strong>Archive registration</strong>
                                <span>Mark as archived without deleting the record</span>
                            </span>
                            <i data-lucide="chevron-right" class="member-advanced-action__chevron"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="post" action="/admin/members/<?= (int) $member['id'] ?>/status" class="member-advanced-action-form">
                        <input type="hidden" name="status" value="reviewed">
                        <input type="hidden" name="portal_notes" value="<?= htmlspecialchars($member['portal_notes'] ?? '') ?>">
                        <button type="submit" class="member-advanced-action">
                            <span class="member-advanced-action__icon"><i data-lucide="rotate-ccw"></i></span>
                            <span class="member-advanced-action__copy">
                                <strong>Restore from archive</strong>
                                <span>Set status back to reviewed</span>
                            </span>
                            <i data-lucide="chevron-right" class="member-advanced-action__chevron"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

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
                        <dt>Form type</dt>
                        <dd><?= htmlspecialchars($formTypeLabel) ?></dd>
                    </div>
                    <div>
                        <dt>Campus</dt>
                        <dd><?= htmlspecialchars($campus) ?></dd>
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

            <form method="post"
                  action="/admin/members/<?= (int) $member['id'] ?>/delete"
                  class="member-danger-card"
                  data-confirm="Permanently delete this registration for <?= htmlspecialchars($name, ENT_QUOTES) ?>? This cannot be undone."
                  data-confirm-title="Delete registration?"
                  data-confirm-label="Delete registration"
                  data-confirm-tone="danger">
                <p class="member-danger-card__title">Danger zone</p>
                <p class="member-danger-card__text">Permanently remove this member registration and all submitted form data.</p>
                <button type="submit" class="member-danger-card__btn">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    Delete registration
                </button>
            </form>
        </div>
    </div>
</div>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
