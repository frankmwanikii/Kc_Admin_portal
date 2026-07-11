<?php

declare(strict_types=1);

namespace App\Services;

class ConnectFormService
{
    private const ALLOWED = ['newsletter', 'new-beginning', 'new-here', 'kingdom-groups', 'join'];

    /** @param array<string, mixed> $data */
    public static function handle(string $formType, array $data): array
    {
        $formType = strtolower(trim($formType));

        if (!in_array($formType, self::ALLOWED, true)) {
            return ['ok' => false, 'message' => 'Unknown form type.'];
        }

        if (!empty($data['_hp'])) {
            return ['ok' => true, 'message' => 'Thank you!'];
        }

        // Normalise array fields from progressive forms
        if (!empty($data['signup']) && is_array($data['signup'])) {
            $data['signup[]'] = $data['signup'];
        }
        if (!empty($data['ministry_serve']) && is_array($data['ministry_serve'])) {
            $data['ministry_serve[]'] = $data['ministry_serve'];
        }

        $error = self::validate($formType, $data);
        if ($error !== null) {
            return ['ok' => false, 'message' => $error];
        }

        FormSubmissionService::record($formType, $data);
        self::notifyAdmin($formType, $data);

        return [
            'ok' => true,
            'message' => self::successMessage($formType),
        ];
    }

    /** @param array<string, mixed> $data */
    private static function validate(string $formType, array $data): ?string
    {
        if ($formType === 'newsletter') {
            if (trim((string) ($data['first_name'] ?? '')) === ''
                || trim((string) ($data['last_name'] ?? '')) === ''
                || trim((string) ($data['email'] ?? '')) === '') {
                return 'Please fill in all required fields.';
            }

            return null;
        }

        if (trim((string) ($data['name'] ?? '')) === ''
            || trim((string) ($data['phone'] ?? '')) === ''
            || trim((string) ($data['email'] ?? '')) === '') {
            return 'Please fill in name, phone, and email.';
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private static function notifyAdmin(string $formType, array $data): void
    {
        $to = $_ENV['MAIL_FROM_ADDRESS'] ?? SettingsService::get('contact_email', '');
        if ($to === '') {
            return;
        }

        $church = SettingsService::churchName();
        $labels = [
            'newsletter' => 'Newsletter',
            'new-beginning' => 'New Beginning',
            'new-here' => 'New Here',
            'kingdom-groups' => 'Kingdom Groups',
            'join' => 'Join / Membership',
        ];
        $label = $labels[$formType] ?? $formType;
        $name = trim((string) ($data['name'] ?? ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')));

        $lines = ["Form: {$label}", ''];
        foreach ($data as $key => $value) {
            if ($key === '_hp' || $value === '' || $value === null) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $lines[] = ucfirst(str_replace('_', ' ', (string) $key)) . ': ' . $value;
        }

        $body = implode("\n", $lines);
        $subject = "[{$label}] " . ($name !== '' ? $name : 'New submission') . ' — ' . $church;

        try {
            (new MailService())->send($to, $subject, '<pre style="font-family:sans-serif">' . htmlspecialchars($body) . '</pre>', true);
        } catch (\Throwable $e) {
            error_log('ConnectFormService mail: ' . $e->getMessage());
        }
    }

    private static function successMessage(string $formType): string
    {
        return match ($formType) {
            'newsletter' => 'Thank you for subscribing!',
            'new-here' => 'Welcome! Our team will connect with you shortly.',
            'join' => 'Your membership application has been received.',
            default => 'Thank you! Our team will be in touch soon.',
        };
    }
}
