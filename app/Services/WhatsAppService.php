<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class WhatsAppService
{
    public function send(string $phone, string $message, ?int $memberId = null, string $type = 'general'): bool
    {
        $phone = self::normalizePhone($phone);
        if ($phone === '') {
            return false;
        }

        $provider = $_ENV['WHATSAPP_PROVIDER'] ?? $_ENV['SMS_PROVIDER'] ?? 'log';
        $status = 'sent';
        $providerRef = null;

        if ($provider === 'twilio'
            && !empty($_ENV['TWILIO_ACCOUNT_SID'])
            && !empty($_ENV['TWILIO_AUTH_TOKEN'])
            && !empty($_ENV['TWILIO_WHATSAPP_FROM'])) {
            $result = $this->sendViaTwilio($phone, $message);
            $status = $result['success'] ? 'sent' : 'failed';
            $providerRef = $result['ref'] ?? null;
        } else {
            error_log("WhatsApp to {$phone}: {$message}");
            $providerRef = 'DEV-WA-' . uniqid();
        }

        Database::connection()->prepare(
            'INSERT INTO sms_logs (member_id, phone, message, type, status, provider_ref) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$memberId, $phone, $message, $type === 'general' ? 'whatsapp' : $type, $status, $providerRef]);

        return $status === 'sent';
    }

    /** @param list<array{phone: string, member_id?: int}> $recipients */
    public function sendBulk(array $recipients, string $message): int
    {
        $sent = 0;
        foreach ($recipients as $recipient) {
            if ($this->send($recipient['phone'], $message, $recipient['member_id'] ?? null, 'whatsapp_bulk')) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function providerLabel(): string
    {
        $provider = $_ENV['WHATSAPP_PROVIDER'] ?? $_ENV['SMS_PROVIDER'] ?? 'log';

        return match ($provider) {
            'twilio' => 'Twilio WhatsApp',
            default => 'Development (log only)',
        };
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';
        if ($phone === '') {
            return '';
        }
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '0')) {
                $phone = '+254' . substr($phone, 1);
            } elseif (str_starts_with($phone, '254')) {
                $phone = '+' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }

        return $phone;
    }

    /** @return array{success: bool, ref: ?string} */
    private function sendViaTwilio(string $phone, string $message): array
    {
        $sid = $_ENV['TWILIO_ACCOUNT_SID'] ?? '';
        $token = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
        $from = $_ENV['TWILIO_WHATSAPP_FROM'] ?? '';

        if ($sid === '' || $token === '' || $from === '') {
            return ['success' => false, 'ref' => null];
        }

        if (!str_starts_with($from, 'whatsapp:')) {
            $from = 'whatsapp:' . $from;
        }

        $to = str_starts_with($phone, 'whatsapp:') ? $phone : 'whatsapp:' . $phone;

        $ch = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $sid . ':' . $token,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'To' => $to,
                'From' => $from,
                'Body' => $message,
            ]),
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response ?: '{}', true);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300 && !empty($data['sid']),
            'ref' => $data['sid'] ?? null,
        ];
    }
}
