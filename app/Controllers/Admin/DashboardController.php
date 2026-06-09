<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\AttendanceSession;
use App\Models\Contribution;
use App\Models\Member;
use App\Models\Ministry;

class DashboardController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $db = Database::connection();
        $monthStart = date('Y-m-01');
        $today = date('Y-m-d');

        $monthStmt = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM contributions WHERE contribution_date >= ?');
        $monthStmt->execute([$monthStart]);

        $stats = [
            'members' => (int) $db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'active'")->fetchColumn(),
            'households' => (int) $db->query('SELECT COUNT(*) FROM households')->fetchColumn(),
            'giving_month' => (float) $monthStmt->fetchColumn(),
            'attendance_today' => 0,
        ];

        $todayStmt = $db->prepare('SELECT id FROM attendance_sessions WHERE session_date = ? LIMIT 1');
        $todayStmt->execute([$today]);
        $todaySession = $todayStmt->fetch();
        if ($todaySession) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM attendance_records WHERE session_id = ? AND status = 'present'");
            $stmt->execute([$todaySession['id']]);
            $stats['attendance_today'] = (int) $stmt->fetchColumn();
        }

        View::render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $stats,
            'recentGiving' => Contribution::recent(5),
            'recentSessions' => AttendanceSession::recent(5),
            'missedMembers' => array_slice(Member::missedAttendance(3), 0, 5),
            'ministries' => Ministry::withDetails(),
        ], 'layouts/admin');
    }
}
