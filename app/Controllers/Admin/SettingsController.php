<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\EnvService;
use App\Services\SettingsService;

class SettingsController
{
    /** @return array<string, mixed> */
    private function viewData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Settings',
            'churchName' => SettingsService::churchName(),
            'churchAddress' => SettingsService::churchAddress(),
            'churchPhone' => SettingsService::churchPhone(),
            'logoUrl' => SettingsService::get('church_logo_url', ''),
            'currentLogo' => SettingsService::logoUrl(),
            'success' => null,
            'error' => null,
            'smsProvider' => $_ENV['SMS_PROVIDER'] ?? 'log',
            'smsProviderLabel' => \App\Services\SmsService::providerLabel(),
            'smsUsername' => $_ENV['SMS_USERNAME'] ?? '',
            'smsApiKey' => $_ENV['SMS_API_KEY'] ?? '',
            'smsSenderId' => $_ENV['SMS_SENDER_ID'] ?? '',
            'twilioAccountSid' => $_ENV['TWILIO_ACCOUNT_SID'] ?? '',
            'twilioAuthToken' => $_ENV['TWILIO_AUTH_TOKEN'] ?? '',
            'twilioFromNumber' => $_ENV['TWILIO_FROM_NUMBER'] ?? '',
            'twilioWhatsappFrom' => $_ENV['TWILIO_WHATSAPP_FROM'] ?? '',
            'formsDbHost' => $_ENV['FORMS_DB_HOST'] ?? $_ENV['DB_HOST'] ?? '127.0.0.1',
            'formsDbPort' => $_ENV['FORMS_DB_PORT'] ?? $_ENV['DB_PORT'] ?? '3306',
            'formsDbName' => $_ENV['FORMS_DB_DATABASE_NAME'] ?? '',
            'formsDbUsername' => $_ENV['FORMS_DB_USERNAME'] ?? '',
            'formsDbStatus' => \App\Services\FormSubmissionService::formsDatabaseStatus(),
        ], $overrides);
    }

    public function index(): void
    {
        Auth::requireAdmin();

        View::render('admin/settings/index', $this->viewData([
            'success' => $_GET['saved'] ?? null,
        ]), 'layouts/admin');
    }

    public function update(): void
    {
        Auth::requireAdmin();

        SettingsService::set('church_name', trim($_POST['church_name'] ?? ''));
        SettingsService::set('church_address', trim($_POST['church_address'] ?? ''));
        SettingsService::set('church_phone', trim($_POST['church_phone'] ?? ''));

        $logoUrl = array_key_exists('church_logo_url', $_POST)
            ? trim($_POST['church_logo_url'])
            : (SettingsService::get('church_logo_url', '') ?? '');
        if ($logoUrl !== '' && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            View::render('admin/settings/index', $this->viewData([
                'churchName' => trim($_POST['church_name'] ?? ''),
                'churchAddress' => trim($_POST['church_address'] ?? ''),
                'churchPhone' => trim($_POST['church_phone'] ?? ''),
                'logoUrl' => $logoUrl,
                'error' => 'Please enter a valid logo URL.',
            ]), 'layouts/admin');
            return;
        }
        SettingsService::set('church_logo_url', $logoUrl);

        if (isset($_POST['remove_logo'])) {
            $this->removeUploadedLogo();
            SettingsService::set('church_logo_path', null);
        }

        $uploadError = $this->logoUploadError($_FILES['church_logo'] ?? null);
        if ($uploadError !== null) {
            View::render('admin/settings/index', $this->viewData([
                'churchName' => trim($_POST['church_name'] ?? ''),
                'churchAddress' => trim($_POST['church_address'] ?? ''),
                'churchPhone' => trim($_POST['church_phone'] ?? ''),
                'logoUrl' => $logoUrl,
                'error' => $uploadError,
            ]), 'layouts/admin');
            return;
        }

        try {
            if (!empty($_FILES['church_logo']['name']) && ($_FILES['church_logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $this->handleLogoUpload($_FILES['church_logo']);
            }
        } catch (\Throwable $e) {
            View::render('admin/settings/index', $this->viewData([
                'churchName' => trim($_POST['church_name'] ?? ''),
                'churchAddress' => trim($_POST['church_address'] ?? ''),
                'churchPhone' => trim($_POST['church_phone'] ?? ''),
                'logoUrl' => $logoUrl,
                'error' => $e->getMessage(),
            ]), 'layouts/admin');
            return;
        }

        try {
            $env = new EnvService();
            $envUpdates = [
                'CHURCH_NAME' => SettingsService::churchName(),
                'CHURCH_ADDRESS' => SettingsService::churchAddress(),
                'CHURCH_PHONE' => SettingsService::churchPhone(),
                'MAIL_FROM_NAME' => SettingsService::churchName(),
            ];

            $smsProvider = $_POST['sms_provider'] ?? 'log';
            if (!in_array($smsProvider, ['log', 'africas_talking', 'twilio'], true)) {
                $smsProvider = 'log';
            }
            $envUpdates['SMS_PROVIDER'] = $smsProvider;

            $smsUsername = trim($_POST['sms_username'] ?? '');
            if ($smsUsername !== '') {
                $envUpdates['SMS_USERNAME'] = $smsUsername;
            }

            $smsSenderId = trim($_POST['sms_sender_id'] ?? '');
            if ($smsSenderId !== '') {
                $envUpdates['SMS_SENDER_ID'] = $smsSenderId;
            }

            $smsApiKey = trim($_POST['sms_api_key'] ?? '');
            if ($smsApiKey !== '') {
                $envUpdates['SMS_API_KEY'] = $smsApiKey;
            }

            $twilioSid = trim($_POST['twilio_account_sid'] ?? '');
            if ($twilioSid !== '') {
                $envUpdates['TWILIO_ACCOUNT_SID'] = $twilioSid;
            }

            $twilioFrom = trim($_POST['twilio_from_number'] ?? '');
            if ($twilioFrom !== '') {
                $envUpdates['TWILIO_FROM_NUMBER'] = $twilioFrom;
            }

            $twilioWhatsappFrom = trim($_POST['twilio_whatsapp_from'] ?? '');
            if ($twilioWhatsappFrom !== '') {
                $envUpdates['TWILIO_WHATSAPP_FROM'] = $twilioWhatsappFrom;
            }

            $twilioToken = trim($_POST['twilio_auth_token'] ?? '');
            if ($twilioToken !== '') {
                $envUpdates['TWILIO_AUTH_TOKEN'] = $twilioToken;
            }

            $formsDbName = trim($_POST['forms_db_name'] ?? '');
            $envUpdates['FORMS_DB_HOST'] = trim($_POST['forms_db_host'] ?? $_ENV['DB_HOST'] ?? '127.0.0.1');
            $envUpdates['FORMS_DB_PORT'] = trim($_POST['forms_db_port'] ?? $_ENV['DB_PORT'] ?? '3306');
            $envUpdates['FORMS_DB_DATABASE_NAME'] = $formsDbName;
            $formsDbUser = trim($_POST['forms_db_username'] ?? '');
            if ($formsDbUser !== '') {
                $envUpdates['FORMS_DB_USERNAME'] = $formsDbUser;
            }
            $formsDbPassword = $_POST['forms_db_password'] ?? '';
            if ($formsDbPassword !== '') {
                $envUpdates['FORMS_DB_PASSWORD'] = $formsDbPassword;
            }

            $env->setMany($envUpdates);
            Database::reset();
        } catch (\Throwable) {
            // Settings are stored in the database; .env sync is optional.
        }

        View::redirect('/admin/settings?saved=1');
    }

    /** @param array<string, mixed>|null $file */
    private function logoUploadError(?array $file): ?string
    {
        if ($file === null || empty($file['name'])) {
            return null;
        }

        return match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Logo file is too large. Maximum size is 2 MB.',
            UPLOAD_ERR_PARTIAL => 'Logo upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => null,
            default => 'Logo upload failed. Please try again.',
        };
    }

    /** @param array<string, mixed> $file */
    private function handleLogoUpload(array $file): void
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : $file['type'];
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Logo must be JPG, PNG, WebP, GIF, or SVG.');
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new \RuntimeException('Logo must be under 2 MB.');
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => 'png',
        };

        $dir = dirname(__DIR__, 3) . '/public/uploads/branding';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the logo upload folder. Set public/uploads/branding to writable (0755).');
        }
        @chmod($dir, 0755);

        $this->removeUploadedLogo();

        $filename = 'logo.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Failed to save uploaded logo. Check that public/uploads/branding is writable.');
        }
        @chmod($dest, 0644);

        SettingsService::set('church_logo_path', 'uploads/branding/' . $filename);
        SettingsService::set('church_logo_url', '');
    }

    private function removeUploadedLogo(): void
    {
        $path = SettingsService::get('church_logo_path');
        if ($path) {
            $full = dirname(__DIR__, 3) . '/public/' . $path;
            if (is_file($full)) {
                unlink($full);
            }
        }
    }
}
