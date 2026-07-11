<?php
$commsConfig = json_encode([
    'preselectMember' => (int) ($preselectMember ?? 0),
    'recipients' => array_values($recipients ?? []),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<link rel="stylesheet" href="/css/admin-finance.css">
<link rel="stylesheet" href="/css/admin-hub.css">

<div class="comms-layout" x-data="commsComposer(<?= htmlspecialchars($commsConfig, ENT_QUOTES) ?>)">
    <?php if ($sent !== null): ?>
    <div class="admin-alert admin-alert--success">
        Message sent successfully to <?= (int) $sent ?> recipient<?= (int) $sent === 1 ? '' : 's' ?>.
    </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="admin-alert admin-alert--error">
        <?php
        echo match ($error) {
            'empty' => 'Please enter a message before sending.',
            'member' => 'Please select a valid member.',
            'phone' => 'No phone numbers found for the selected audience. Check member records.',
            'email' => 'No valid email addresses found for the selected audience.',
            'delivery' => 'Message could not be delivered. Check channel settings and member contact details.',
            default => 'Unable to send message. Please try again.',
        };
        ?>
    </div>
    <?php endif; ?>

    <div class="comms-composer">
        <div class="comms-composer-header">
            <h2>Message composer</h2>
            <p>SMS via <?= htmlspecialchars($smsProvider ?? 'SMS') ?> · WhatsApp via <?= htmlspecialchars($whatsappProvider ?? 'WhatsApp') ?></p>
        </div>
        <form method="POST" action="/admin/communications/send" class="comms-composer-body">
            <div class="comms-audience-toggle" role="tablist" aria-label="Message audience">
                <button type="button"
                        role="tab"
                        class="comms-audience-btn"
                        :class="{ 'comms-audience-btn--active': audience === 'all' }"
                        :aria-selected="audience === 'all'"
                        @click="setAudience('all')">
                    Everyone
                </button>
                <button type="button"
                        role="tab"
                        class="comms-audience-btn"
                        :class="{ 'comms-audience-btn--active': audience === 'individual' }"
                        :aria-selected="audience === 'individual'"
                        @click="setAudience('individual')">
                    Individual
                </button>
            </div>
            <input type="hidden" name="audience" :value="audience">

            <div x-show="audience === 'individual'" x-cloak class="finance-field mb-4">
                <label class="finance-label" for="member_id">Select member</label>
                <select id="member_id" name="member_id" x-model="memberId" class="finance-input" :required="audience === 'individual'">
                    <option value="">Choose a member…</option>
                    <template x-for="r in recipients" :key="r.id">
                        <option :value="r.id" x-text="(r.submitter_name || 'Member') + (r.submitter_phone ? ' · ' + r.submitter_phone : '')"></option>
                    </template>
                </select>
            </div>

            <div class="finance-field mb-4">
                <label class="finance-label" for="comms-title">Subject / title</label>
                <input type="text" id="comms-title" name="title" class="finance-input" placeholder="e.g. Sunday service reminder">
            </div>

            <div class="finance-field mb-4">
                <label class="finance-label" for="comms-channel">Channel</label>
                <select id="comms-channel" name="channel" x-model="channel" class="finance-input">
                    <option value="sms">SMS only</option>
                    <option value="whatsapp">WhatsApp only</option>
                    <option value="email">Email only</option>
                    <option value="both">SMS + Email</option>
                    <option value="all">SMS + WhatsApp + Email</option>
                </select>
            </div>

            <div class="finance-field mb-4">
                <label class="finance-label" for="comms-message">Message</label>
                <textarea id="comms-message"
                          name="message"
                          rows="6"
                          required
                          x-model="message"
                          class="finance-input finance-textarea"
                          placeholder="Write your announcement…"></textarea>
                <p class="comms-char-count"
                   :class="usesSmsLimit && message.length > 160 && 'comms-char-count--warn'"
                   x-show="usesSmsLimit"
                   x-text="message.length + ' characters' + (message.length > 160 ? ' (SMS may split into multiple parts)' : '')"></p>
            </div>

            <div class="comms-form-actions">
                <button type="submit" class="comms-btn-send">
                    <i data-lucide="send" class="comms-btn-send-icon" aria-hidden="true"></i>
                    <span class="comms-btn-send-text">Send</span>
                </button>
            </div>
        </form>
    </div>

    <div class="comms-history admin-hub-page">
        <h2 class="arrears-title">Message history</h2>
        <p class="finance-tab-hint">Recent announcements sent from this hub.</p>

        <div class="arrears-card finance-table-card">
            <?php if (empty($communications)): ?>
            <p class="arrears-empty px-5 py-10 text-center">No messages sent yet. Use the composer above to send your first announcement.</p>
            <?php else: ?>
            <div class="divide-y divide-slate-50">
                <?php foreach ($communications as $c):
                    $channelClass = match ($c['channel'] ?? '') {
                        'sms' => 'comms-channel-badge--sms',
                        'whatsapp' => 'comms-channel-badge--whatsapp',
                        'email' => 'comms-channel-badge--email',
                        'both' => 'comms-channel-badge--both',
                        'all' => 'comms-channel-badge--all',
                        default => '',
                    };
                    $channelLabel = match ($c['channel'] ?? '') {
                        'both' => 'SMS+Email',
                        'all' => 'All channels',
                        default => strtoupper((string) ($c['channel'] ?? '')),
                    };
                    $audienceLabel = ($c['audience'] ?? 'all') === 'individual' ? 'Individual' : 'Everyone';
                ?>
                <div class="comms-history-row">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <p class="font-semibold text-slate-800"><?= htmlspecialchars($c['title'] ?: 'Announcement') ?></p>
                            <span class="comms-channel-badge <?= $channelClass ?>"><?= htmlspecialchars($channelLabel) ?></span>
                            <span class="text-xs text-slate-400"><?= htmlspecialchars($audienceLabel) ?></span>
                        </div>
                        <p class="text-sm text-slate-500 line-clamp-2"><?= htmlspecialchars($c['message']) ?></p>
                        <p class="text-xs text-slate-400 mt-1"><?= date('M j, Y g:i A', strtotime($c['sent_at'] ?? $c['created_at'])) ?></p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-lg font-bold text-church-700"><?= (int) ($c['sent_count'] ?? 0) ?></p>
                        <p class="text-xs text-slate-400">delivered</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/js/admin-comms.js"></script>
