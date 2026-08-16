<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\User;

class OnboardingService
{
    public function __construct(
        private MailService $mail = new MailService(),
    ) {}

    public function createMember(array $data, string $qrToken): array
    {
        User::ensureProfileColumns();

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $householdName = $data['household_name'] ?: trim($data['last_name'] . ' Family');
            $householdNotes = $this->buildHouseholdNotes($data);

            $stmt = $db->prepare('INSERT INTO households (name, address, city, phone, notes) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $householdName,
                $data['residence'] ?? $data['address'] ?? null,
                $data['city'] ?? null,
                $data['phone'] ?? null,
                $householdNotes ?: null,
            ]);
            $householdId = (int) $db->lastInsertId();

            $memberToken = bin2hex(random_bytes(32));
            $joinedDate = date('Y-m-d');
            $stmt = $db->prepare('
                INSERT INTO members (
                    household_id, first_name, last_name, email, phone, gender, date_of_birth,
                    marital_status, spouse_name, residence, county, occupation, employer,
                    emergency_contact_name, emergency_contact_phone, how_heard_about_us,
                    previous_church, baptized, baptism_date, wish_to_be_baptized, ministry_interests, skills_talents,
                    member_notes, is_head_of_household, membership_status, joined_date,
                    onboarding_token, onboarding_completed
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ');
            $stmt->execute([
                $householdId,
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['gender'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['marital_status'] ?? null,
                $data['spouse_name'] ?: null,
                $data['residence'] ?? null,
                $data['county'] ?? null,
                $data['occupation'] ?? null,
                $data['employer'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['how_heard_about_us'] ?? null,
                $data['previous_church'] ?? null,
                !empty($data['baptized']) ? 1 : 0,
                $data['baptism_date'] ?? null,
                !empty($data['wish_to_be_baptized']) ? 1 : 0,
                $data['ministry_interests'] ?? null,
                $data['skills_talents'] ?? null,
                $data['member_notes'] ?? null,
                !empty($data['is_head']) ? 1 : 0,
                'active',
                $joinedDate,
                $memberToken,
            ]);
            $memberId = (int) $db->lastInsertId();

            if (!empty($data['is_head'])) {
                $db->prepare('UPDATE households SET head_member_id = ? WHERE id = ?')->execute([$memberId, $householdId]);
            }

            foreach ($data['children'] ?? [] as $child) {
                if (empty($child['name'])) {
                    continue;
                }
                $db->prepare('INSERT INTO household_children (household_id, name, age) VALUES (?, ?, ?)')
                    ->execute([$householdId, $child['name'], $child['age'] ?? null]);
            }

            $magicToken = bin2hex(random_bytes(32));
            $tempPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $magicExpires = date('Y-m-d H:i:s', strtotime('+48 hours'));
            $userName = preg_replace('/[^A-Za-z0-9._-]+/', '', (string) strstr((string) $data['email'], '@', true)) ?: ('member' . $memberId);
            $stmt = $db->prepare('
                INSERT INTO users (member_id, username, email, password, role, magic_link_token, magic_link_expires)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$memberId, $userName, $data['email'], $tempPassword, 'member', $magicToken, $magicExpires]);

            $db->prepare('UPDATE onboarding_qr_codes SET scan_count = scan_count + 1 WHERE token = ?')->execute([$qrToken]);

            $db->commit();

            $portalLink = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/') . '/portal/access/' . $magicToken;
            $this->mail->sendPortalLink(
                $data['email'],
                trim($data['first_name'] . ' ' . $data['last_name']),
                $portalLink
            );

            return ['member_id' => $memberId, 'portal_link' => $portalLink];
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function buildHouseholdNotes(array $data): string
    {
        $parts = [];
        if (!empty($data['county'])) {
            $parts[] = 'County: ' . $data['county'];
        }

        return implode("\n", $parts);
    }
}
