<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\FormSubmissionService;
use App\Services\MailService;
use App\Services\SmsService;
use App\Services\WhatsAppService;

class CommunicationController
{
    private const CHANNELS = ['sms', 'whatsapp', 'email', 'both', 'all'];

    public function index(): void
    {
        Auth::requireAdmin();
        FormSubmissionService::ensureTable();

        $db = Database::connection();
        $communications = $db->query('SELECT * FROM communications ORDER BY created_at DESC LIMIT 30')->fetchAll();
        $recipients = FormSubmissionService::membersList();
        $preselectMember = (int) ($_GET['member'] ?? 0);

        View::render('admin/communications/index', [
            'title' => 'Communications',
            'communications' => $communications,
            'recipients' => $recipients,
            'preselectMember' => $preselectMember,
            'smsProvider' => SmsService::providerLabel(),
            'whatsappProvider' => WhatsAppService::providerLabel(),
            'sent' => isset($_GET['sent']) ? (int) $_GET['sent'] : null,
            'error' => $_GET['error'] ?? null,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();
        View::redirect('/admin/communications');
    }

    public function send(): void
    {
        Auth::requireAdmin();
        FormSubmissionService::ensureTable();

        $channel = $_POST['channel'] ?? 'sms';
        $message = trim($_POST['message'] ?? '');
        $title = trim($_POST['title'] ?? 'Announcement');
        $audience = $_POST['audience'] ?? 'all';
        $memberId = (int) ($_POST['member_id'] ?? 0);

        if (!in_array($channel, self::CHANNELS, true)) {
            $channel = 'sms';
        }

        if ($message === '') {
            View::redirect('/admin/communications?error=empty');
            return;
        }

        if ($audience === 'individual') {
            $member = FormSubmissionService::find($memberId);
            if (!$member || !in_array($member['form_type'], FormSubmissionService::MEMBER_FORM_TYPES, true)) {
                View::redirect('/admin/communications?error=member');
                return;
            }
            $list = [$member];
            $audienceKey = 'individual';
        } else {
            $list = FormSubmissionService::membersList();
            $audienceKey = 'all';
        }

        $includesSms = in_array($channel, ['sms', 'both', 'all'], true);
        $includesWhatsapp = in_array($channel, ['whatsapp', 'all'], true);
        $includesEmail = in_array($channel, ['email', 'both', 'all'], true);

        $phoneRecipients = [];
        foreach ($list as $m) {
            $phone = trim((string) ($m['submitter_phone'] ?? ''));
            if ($phone !== '') {
                $phoneRecipients[] = ['phone' => $phone, 'member_id' => (int) $m['id']];
            }
        }

        $sent = 0;

        if ($includesSms) {
            if ($phoneRecipients === []) {
                View::redirect('/admin/communications?error=phone');
                return;
            }
            $sent += (new SmsService())->sendBulk($phoneRecipients, $message);
        }

        if ($includesWhatsapp) {
            if ($phoneRecipients === []) {
                View::redirect('/admin/communications?error=phone');
                return;
            }
            $sent += (new WhatsAppService())->sendBulk($phoneRecipients, $message);
        }

        if ($includesEmail) {
            $mail = new MailService();
            $emailSent = 0;
            foreach ($list as $m) {
                $email = trim((string) ($m['submitter_email'] ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)
                    && $mail->sendAnnouncement($email, $title, $message)) {
                    $emailSent++;
                }
            }
            if ($emailSent === 0 && $includesEmail && !$includesSms && !$includesWhatsapp) {
                View::redirect('/admin/communications?error=email');
                return;
            }
            $sent += $emailSent;
        }

        if ($sent === 0) {
            View::redirect('/admin/communications?error=delivery');
            return;
        }

        Database::connection()->prepare(
            'INSERT INTO communications (title, message, channel, audience, status, sent_at, sent_count, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $title,
            $message,
            $channel,
            $audienceKey,
            'sent',
            date('Y-m-d H:i:s'),
            $sent,
            Auth::user()?->id,
        ]);

        View::redirect('/admin/communications?sent=' . $sent);
    }

    public function birthdays(): void
    {
        Auth::requireAdmin();
        $db = Database::connection();
        $todayStmt = $db->prepare("
            SELECT * FROM members
            WHERE membership_status = 'active'
            AND MONTH(date_of_birth) = ?
            AND DAY(date_of_birth) = ?
            ORDER BY first_name
        ");
        $todayStmt->execute([(int) date('n'), (int) date('j')]);
        $today = $todayStmt->fetchAll();

        View::render('admin/communications/birthdays', [
            'title' => 'Birthdays Today',
            'members' => $today,
        ], 'layouts/admin');
    }
}
