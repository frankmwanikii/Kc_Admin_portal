<?php

declare(strict_types=1);

namespace App\Models;

class Contribution extends Model
{
    protected static string $table = 'contributions';

    public int $id;
    public int $member_id;
    public ?int $household_id;
    public int $fund_id;
    public float $amount;
    public string $payment_method;
    public ?string $transaction_ref;
    public string $contribution_date;
    public ?string $notes;
    public int $sms_sent;
    public ?int $recorded_by;
    public ?string $created_at;

    public function fund(): ?Fund
    {
        return Fund::find($this->fund_id);
    }

    public function member(): ?Member
    {
        return Member::find($this->member_id);
    }

    /** @return self[] */
    public static function byMember(int $memberId, ?string $from = null, ?string $to = null): array
    {
        $sql = 'SELECT c.*, f.name as fund_name, f.code as fund_code
                FROM contributions c
                JOIN funds f ON f.id = c.fund_id
                WHERE c.member_id = ?';
        $params = [$memberId];

        if ($from) {
            $sql .= ' AND c.contribution_date >= ?';
            $params[] = $from;
        }
        if ($to) {
            $sql .= ' AND c.contribution_date <= ?';
            $params[] = $to;
        }
        $sql .= ' ORDER BY c.contribution_date DESC, c.id DESC';

        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function totalByMember(int $memberId, ?string $from = null, ?string $to = null): float
    {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE member_id = ?';
        $params = [$memberId];
        if ($from) {
            $sql .= ' AND contribution_date >= ?';
            $params[] = $from;
        }
        if ($to) {
            $sql .= ' AND contribution_date <= ?';
            $params[] = $to;
        }
        $stmt = self::query($sql, $params);
        return (float) $stmt->fetchColumn();
    }

    public static function recent(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = self::query("
            SELECT c.*, m.first_name, m.last_name, f.name as fund_name
            FROM contributions c
            JOIN members m ON m.id = c.member_id
            JOIN funds f ON f.id = c.fund_id
            ORDER BY c.created_at DESC LIMIT {$limit}
        ");
        return $stmt->fetchAll();
    }
}
