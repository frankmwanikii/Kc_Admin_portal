<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class SmsService
{
    public function send(string $phone, string $message, ?int $memberId = null, string $type = 'general'): bool
    {
        $provider = $_ENV['SMS_PROVIDER'] ?? 'log';
        $status = 'sent';
        $providerRef = null;

        if ($provider === 'twilio' && !empty($_ENV['TWILIO_ACCOUNT_SID']) && !empty($_ENV['TWILIO_AUTH_TOKEN'])) {
            $result = $this->sendViaTwilio($phone, $message);
            $status = $result['success'] ? 'sent' : 'failed';
            $providerRef = $result['ref'] ?? null;
        } elseif ($provider === 'africas_talking' && !empty($_ENV['SMS_API_KEY'])) {
            $result = $this->sendViaAfricasTalking($phone, $message);
            $status = $result['success'] ? 'sent' : 'failed';
            $providerRef = $result['ref'] ?? null;
        } else {
            error_log("SMS to {$phone}: {$message}");
            $providerRef = 'DEV-' . uniqid();
        }

        Database::connection()->prepare(
            'INSERT INTO sms_logs (member_id, phone, message, type, status, provider_ref) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$memberId, $phone, $message, $type, $status, $providerRef]);

        return $status === 'sent';
    }

    public function sendGivingAcknowledgment(int $memberId, string $phone, string $name, float $amount, string $fund, string $ref): bool
    {
        $church = $_ENV['CHURCH_NAME'] ?? 'Grace Church';
        $formatted = number_format($amount, 2);
        $message = "Dear {$name}, thank you for your {$fund} of KES {$formatted} to {$church}. Ref: {$ref}. God bless you!";

        return $this->send($phone, $message, $memberId, 'giving_ack');
    }

    public function sendBulk(array $recipients, string $message): int
    {
        $sent = 0;
        foreach ($recipients as $recipient) {
            if ($this->send($recipient['phone'], $message, $recipient['member_id'] ?? null, 'bulk')) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function providerLabel(): string
    {
        $provider = $_ENV['SMS_PROVIDER'] ?? 'log';

        return match ($provider) {
            'twilio' => 'Twilio',
            'africas_talking' => "Africa's Talking",
            default => 'Development (log only)',
        };
    }

    /** @return array{success: bool, ref: ?string} */
    private function sendViaTwilio(string $phone, string $message): array
    {
        $sid = $_ENV['TWILIO_ACCOUNT_SID'] ?? '';
        $token = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
        $from = $_ENV['TWILIO_FROM_NUMBER'] ?? '';

        if ($sid === '' || $token === '' || $from === '') {
            return ['success' => false, 'ref' => null];
        }

        $ch = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $sid . ':' . $token,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'To' => $phone,
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

    /** @return array{success: bool, ref: ?string} */
    private function sendViaAfricasTalking(string $phone, string $message): array
    {
        $username = $_ENV['SMS_USERNAME'] ?? 'sandbox';
        $apiKey = $_ENV['SMS_API_KEY'];
        $senderId = $_ENV['SMS_SENDER_ID'] ?? 'CHURCH';

        $ch = curl_init('https://api.africastalking.com/version1/messaging');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apiKey: ' . $apiKey,
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => $username,
                'to' => $phone,
                'message' => $message,
                'from' => $senderId,
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response ?: '{}', true);

        return [
            'success' => ($data['SMSMessageData']['Recipients'][0]['status'] ?? '') === 'Success',
            'ref' => $data['SMSMessageData']['Recipients'][0]['messageId'] ?? null,
        ];
    }
}
