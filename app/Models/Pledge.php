<?php

declare(strict_types=1);

namespace App\Models;

class Pledge extends Model
{
    protected static string $table = 'pledges';

    public int $id;
    public int $campaign_id;
    public int $member_id;
    public float $pledged_amount;
    public float $amount_paid;
    public ?string $pledge_date;
    public ?string $notes;
    public ?string $created_at;

    public function progressPercent(): float
    {
        if ($this->pledged_amount <= 0) return 0;
        return min(100, ($this->amount_paid / $this->pledged_amount) * 100);
    }

    public function remaining(): float
    {
        return max(0, $this->pledged_amount - $this->amount_paid);
    }

    /** @return array */
    public static function byMember(int $memberId): array
    {
        $stmt = self::query("
            SELECT p.*, pc.title as campaign_title, pc.end_date, pc.description as campaign_description
            FROM pledges p
            JOIN pledge_campaigns pc ON pc.id = p.campaign_id
            WHERE p.member_id = ? AND pc.is_active = 1
            ORDER BY pc.end_date ASC
        ", [$memberId]);
        return $stmt->fetchAll();
    }
}
