<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Member;
use App\Services\MailService;
use App\Services\SmsService;

class CommunicationController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $db = Database::connection();
        $communications = $db->query('SELECT * FROM communications ORDER BY created_at DESC LIMIT 20')->fetchAll();
        View::render('admin/communications/index', [
            'title' => 'Communication Hub',
            'communications' => $communications,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();
        View::render('admin/communications/create', [
            'title' => 'Send Message',
        ], 'layouts/admin');
    }

    public function send(): void
    {
        Auth::requireAdmin();
        $channel = $_POST['channel'] ?? 'sms';
        $message = trim($_POST['message'] ?? '');
        $title = trim($_POST['title'] ?? 'Announcement');

        if (!$message) {
            View::redirect('/admin/communications/create');
            return;
        }

        $members = Member::all('last_name ASC');
        $sent = 0;

        if ($channel === 'sms' || $channel === 'both') {
            $sms = new SmsService();
            $recipients = array_filter(array_map(fn($m) => $m->phone ? ['phone' => $m->phone, 'member_id' => $m->id] : null, $members));
            $sent += $sms->sendBulk($recipients, $message);
        }

        if ($channel === 'email' || $channel === 'both') {
            $mail = new MailService();
            foreach ($members as $member) {
                if ($member->email && $mail->sendAnnouncement($member->email, $title, $message)) {
                    $sent++;
                }
            }
        }

        Database::connection()->prepare(
            'INSERT INTO communications (title, message, channel, audience, status, sent_at, sent_count, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$title, $message, $channel, 'all', 'sent', date('Y-m-d H:i:s'), $sent, Auth::user()?->id]);

        View::redirect('/admin/communications');
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
