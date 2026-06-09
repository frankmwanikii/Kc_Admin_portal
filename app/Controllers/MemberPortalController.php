<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Contribution;
use App\Models\Member;
use App\Models\Pledge;
use App\Services\PdfService;

class MemberPortalController
{
    private function member(): Member
    {
        Auth::requireAuth();

        if (Auth::isAdmin()) {
            View::redirect('/admin');
        }

        $user = Auth::user();
        $member = $user?->member();
        if (!$member) {
            http_response_code(403);
            View::render('errors/403', [
                'title' => 'Member Portal',
                'message' => 'Your account is not linked to a member profile. Please contact the church office.',
                'showAdminLink' => false,
            ]);
            exit;
        }
        return $member;
    }

    public function dashboard(): void
    {
        $member = $this->member();
        $contributions = Contribution::byMember($member->id);
        $totalGiving = Contribution::totalByMember($member->id, date('Y-01-01'));
        $pledges = Pledge::byMember($member->id);
        $household = $member->household();

        View::render('member/dashboard', [
            'title' => 'My Dashboard',
            'member' => $member,
            'household' => $household,
            'recentGiving' => array_slice($contributions, 0, 5),
            'totalGiving' => $totalGiving,
            'pledges' => $pledges,
        ], 'layouts/member');
    }

    public function giving(): void
    {
        $member = $this->member();
        $from = $_GET['from'] ?? date('Y-01-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $contributions = Contribution::byMember($member->id, $from, $to);
        $total = Contribution::totalByMember($member->id, $from, $to);

        View::render('member/giving', [
            'title' => 'Giving History',
            'member' => $member,
            'contributions' => $contributions,
            'total' => $total,
            'from' => $from,
            'to' => $to,
        ], 'layouts/member');
    }

    public function pledges(): void
    {
        $member = $this->member();
        View::render('member/pledges', [
            'title' => 'My Pledges',
            'member' => $member,
            'pledges' => Pledge::byMember($member->id),
        ], 'layouts/member');
    }

    public function profile(): void
    {
        $member = $this->member();
        View::render('member/profile', [
            'title' => 'My Profile',
            'member' => $member,
            'household' => $member->household(),
            'family' => $member->household_id ? Member::byHousehold($member->household_id) : [],
        ], 'layouts/member');
    }

    public function downloadStatement(): void
    {
        $member = $this->member();
        $from = $_GET['from'] ?? date('Y-01-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $pdf = new PdfService();
        $content = $pdf->generateGivingStatement($member, $from, $to);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="giving-statement-' . $from . '-to-' . $to . '.pdf"');
        echo $content;
        exit;
    }
}
