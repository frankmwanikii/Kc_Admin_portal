<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\AttendanceSession;
use App\Models\Member;

class AttendanceController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/attendance/index', [
            'title' => 'Attendance',
            'sessions' => AttendanceSession::recent(20),
            'missedMembers' => Member::missedAttendance(3),
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        Auth::requireAdmin();
        $session = AttendanceSession::find((int) $id);
        if (!$session) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not Found']);
            return;
        }

        $db = Database::connection();
        $records = $db->prepare("
            SELECT ar.*, m.first_name, m.last_name, m.phone
            FROM attendance_records ar
            JOIN members m ON m.id = ar.member_id
            WHERE ar.session_id = ?
            ORDER BY m.last_name
        ");
        $records->execute([$session->id]);

        View::render('admin/attendance/show', [
            'title' => $session->title,
            'session' => $session,
            'records' => $records->fetchAll(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();
        View::render('admin/attendance/create', [
            'title' => 'New Attendance Session',
            'members' => Member::all('last_name ASC'),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $db = Database::connection();

        $stmt = $db->prepare('INSERT INTO attendance_sessions (title, type, session_date, start_time, location, created_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['title'],
            $_POST['type'],
            $_POST['session_date'],
            $_POST['start_time'] ?? null,
            $_POST['location'] ?? null,
            Auth::user()?->id,
        ]);
        $sessionId = (int) $db->lastInsertId();

        $present = $_POST['present'] ?? [];
        foreach ($present as $memberId) {
            $db->prepare('INSERT INTO attendance_records (session_id, member_id, status) VALUES (?, ?, ?)')
                ->execute([$sessionId, (int) $memberId, 'present']);
        }

        View::redirect('/admin/attendance/' . $sessionId);
    }
}
