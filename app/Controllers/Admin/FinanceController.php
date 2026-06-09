<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Contribution;
use App\Models\Fund;
use App\Models\Member;
use App\Services\SmsService;

class FinanceController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $db = Database::connection();
        $monthStart = date('Y-m-01');

        $monthStmt = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM contributions WHERE contribution_date >= ?');
        $monthStmt->execute([$monthStart]);
        $monthTotal = (float) $monthStmt->fetchColumn();

        $byFundStmt = $db->prepare('
            SELECT f.name, f.code, COALESCE(SUM(c.amount),0) as total
            FROM funds f LEFT JOIN contributions c ON c.fund_id = f.id AND c.contribution_date >= ?
            GROUP BY f.id, f.name, f.code ORDER BY total DESC
        ');
        $byFundStmt->execute([$monthStart]);
        $byFund = $byFundStmt->fetchAll();

        View::render('admin/finance/index', [
            'title' => 'Finance & Giving',
            'monthTotal' => $monthTotal,
            'byFund' => $byFund,
            'recent' => Contribution::recent(15),
            'funds' => Fund::active(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();
        View::render('admin/finance/create', [
            'title' => 'Record Contribution',
            'members' => Member::all('last_name ASC'),
            'funds' => Fund::active(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $db = Database::connection();
        $memberId = (int) $_POST['member_id'];
        $member = Member::find($memberId);
        $amount = (float) $_POST['amount'];
        $fundId = (int) $_POST['fund_id'];
        $ref = trim($_POST['transaction_ref'] ?? '') ?: 'TXN-' . strtoupper(substr(uniqid(), -8));

        $fund = Fund::find($fundId);

        $stmt = $db->prepare('
            INSERT INTO contributions (member_id, household_id, fund_id, amount, payment_method, transaction_ref, contribution_date, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $memberId,
            $member?->household_id,
            $fundId,
            $amount,
            $_POST['payment_method'] ?? 'cash',
            $ref,
            $_POST['contribution_date'] ?? date('Y-m-d'),
            Auth::user()?->id,
        ]);

        if (isset($_POST['send_sms']) && $member?->phone) {
            $sms = new SmsService();
            $sms->sendGivingAcknowledgment($memberId, $member->phone, $member->first_name, $amount, $fund?->name ?? 'Offering', $ref);
            $db->prepare('UPDATE contributions SET sms_sent = 1 WHERE id = ?')->execute([(int) $db->lastInsertId()]);
        }

        View::redirect('/admin/finance');
    }

    public function mobileMoney(): void
    {
        Auth::requireAdmin();
        $db = Database::connection();
        $statements = $db->query('SELECT * FROM mobile_money_statements ORDER BY created_at DESC LIMIT 50')->fetchAll();
        View::render('admin/finance/mobile-money', [
            'title' => 'Mobile Money Statements',
            'statements' => $statements,
        ], 'layouts/admin');
    }
}
