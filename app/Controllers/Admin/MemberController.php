<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Services\FormSubmissionService;

class MemberController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/members/index', [
            'title' => 'Members',
            'members' => FormSubmissionService::membersList(),
            'formsDbStatus' => FormSubmissionService::formsDatabaseStatus(),
            'formTypeLabels' => FormSubmissionService::formTypeLabels(),
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        Auth::requireAdmin();
        $member = FormSubmissionService::find((int) $id);
        if (!$member || !in_array($member['form_type'], FormSubmissionService::MEMBER_FORM_TYPES, true)) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not Found']);
            return;
        }

        View::render('admin/members/show', [
            'title' => $member['submitter_name'] ?? 'Member',
            'member' => $member,
        ], 'layouts/admin');
    }

    public function updateStatus(string $id): void
    {
        Auth::requireAdmin();
        $status = $_POST['status'] ?? 'reviewed';
        if (!in_array($status, ['new', 'reviewed', 'archived'], true)) {
            $status = 'reviewed';
        }
        FormSubmissionService::updateStatus((int) $id, $status, trim($_POST['portal_notes'] ?? '') ?: null);
        View::redirect('/admin/members/' . (int) $id);
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        FormSubmissionService::delete((int) $id);
        View::redirect('/admin/members');
    }
}
