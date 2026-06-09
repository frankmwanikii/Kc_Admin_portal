<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private const BRAND_PRIMARY = '#35afe6';
    private const BRAND_DARK = '#0b486d';
    private const BRAND_LIGHT_BG = '#e8f6fc';
    private const BRAND_SLOGAN = 'Transformed lives';

    public function send(string $to, string $subject, string $htmlBody, bool $branded = false): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'localhost';
            $mail->SMTPAuth = !empty($_ENV['MAIL_USERNAME']);
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $port = (int) ($_ENV['MAIL_PORT'] ?? 587);
            $mail->Port = $port;
            $encryption = strtolower((string) ($_ENV['MAIL_ENCRYPTION'] ?? ''));
            $mail->SMTPSecure = match (true) {
                in_array($encryption, ['ssl', 'smtps'], true) => PHPMailer::ENCRYPTION_SMTPS,
                in_array($encryption, ['tls', 'starttls'], true) => PHPMailer::ENCRYPTION_STARTTLS,
                $port === 465 => PHPMailer::ENCRYPTION_SMTPS,
                default => PHPMailer::ENCRYPTION_STARTTLS,
            };

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_BASE64;

            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@church.local',
                $_ENV['MAIL_FROM_NAME'] ?? 'Church MIS'
            );
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;

            if ($branded) {
                $htmlBody = $this->wrapTemplate($htmlBody, $mail);
            }

            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            return $mail->send();
        } catch (Exception $e) {
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                error_log('Mail error: ' . $mail->ErrorInfo);
            }
            return false;
        }
    }

    public function sendPortalLink(string $to, string $name, string $link): bool
    {
        $churchName = SettingsService::churchName();
        $church = htmlspecialchars($churchName, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $slogan = htmlspecialchars(self::BRAND_SLOGAN, ENT_QUOTES, 'UTF-8');
        $subject = "Welcome to {$churchName} - Your Member Portal";
        $body = "
            <p style='margin:0 0 8px;font-size:28px;line-height:1.3;text-align:center'>&#127881; &#127882; &#127881;</p>
            <h2 style='color:" . self::BRAND_DARK . ";margin:0 0 16px;font-size:24px;font-weight:700;text-align:center'>Congratulations, {$safeName}!</h2>
            <p style='color:#475569;font-size:16px;line-height:1.7;margin:0 0 16px'>We are <strong>thrilled</strong> to welcome you to the <strong>{$church}</strong> family! &#127881; Your member profile is ready, and we cannot wait to walk this journey with you as we live out <em>{$slogan}</em> together.</p>
            <p style='color:#475569;font-size:16px;line-height:1.7;margin:0 0 20px'>Your personal member portal is now open. From here you can:</p>
            <ul style='color:#475569;font-size:16px;line-height:1.8;margin:0 0 24px;padding-left:20px'>
                <li>View and update your profile &amp; household details</li>
                <li>Track your giving history and pledges</li>
                <li>Stay connected with church life at Kingdomcity</li>
            </ul>
            <p style='text-align:center;margin:32px 0'>
                <a href='{$safeLink}' style='" . $this->buttonStyle() . "'>&#127775; Access Your Member Portal</a>
            </p>
            <p style='color:#64748b;font-size:14px;line-height:1.6;margin:0 0 28px;padding:14px 16px;background:" . self::BRAND_LIGHT_BG . ";border-radius:8px;border-left:4px solid " . self::BRAND_PRIMARY . "'>" . $this->mutedNote('This secure link expires in 48 hours. If you did not register, please contact the church office.') . "</p>
            <p style='color:#475569;font-size:16px;line-height:1.7;margin:0'>We are so glad you are here. See you soon!</p>
            <p style='color:" . self::BRAND_DARK . ";font-size:16px;line-height:1.7;margin:20px 0 0'>
                Regards,<br>
                <strong>{$church}</strong>
            </p>";

        return $this->send($to, $subject, $body, true);
    }

    public function sendBirthdayWish(string $to, string $name): bool
    {
        $church = htmlspecialchars(SettingsService::churchName(), ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $subject = "Happy Birthday from {$church}!";
        $body = "
            <h2 style='color:" . self::BRAND_DARK . ";margin:0 0 12px;font-size:24px;font-weight:700'>Happy Birthday, {$safeName}!</h2>
            <p style='color:#475569;font-size:16px;line-height:1.6;margin:0 0 16px'>On this special day, we celebrate you and thank God for your life. May this new year be filled with His blessings, joy, and purpose.</p>
            <p style='color:#475569;font-size:16px;line-height:1.6;margin:0'>With love,<br><strong style='color:" . self::BRAND_DARK . "'>{$church}</strong></p>";

        return $this->send($to, $subject, $body, true);
    }

    public function sendAnnouncement(string $to, string $subject, string $message): bool
    {
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $body = "
            <h2 style='color:" . self::BRAND_DARK . ";margin:0 0 16px;font-size:22px;font-weight:700'>{$safeSubject}</h2>
            <div style='color:#475569;font-size:16px;line-height:1.7'>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</div>';

        return $this->send($to, $subject, $body, true);
    }

    private function wrapTemplate(string $content, PHPMailer $mail): string
    {
        $church = htmlspecialchars(SettingsService::churchName(), ENT_QUOTES, 'UTF-8');
        $slogan = htmlspecialchars(self::BRAND_SLOGAN, ENT_QUOTES, 'UTF-8');
        $address = htmlspecialchars(SettingsService::churchAddress(), ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars(SettingsService::churchPhone(), ENT_QUOTES, 'UTF-8');
        $appUrl = htmlspecialchars(rtrim($_ENV['APP_URL'] ?? '', '/'), ENT_QUOTES, 'UTF-8');
        $headerHtml = $this->emailHeaderHtml($mail, $church, $slogan);

        $footerContact = '';
        if ($address !== '') {
            $footerContact .= "<p style='margin:0 0 4px;color:#64748b;font-size:13px'>{$address}</p>";
        }
        if ($phone !== '') {
            $tel = htmlspecialchars(preg_replace('/\s+/', '', SettingsService::churchPhone()), ENT_QUOTES, 'UTF-8');
            $footerContact .= "<p style='margin:0;color:#64748b;font-size:13px'><a href='tel:{$tel}' style='color:" . self::BRAND_DARK . ";text-decoration:none'>{$phone}</a></p>";
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>' . $church . '</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Inter,Segoe UI,Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(11,72,109,0.10)">
                    <tr>
                        <td style="background:linear-gradient(135deg,' . self::BRAND_DARK . ' 0%,' . self::BRAND_PRIMARY . ' 100%);padding:32px 28px;text-align:center">
                            ' . $headerHtml . '
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 28px">
                            ' . $content . '
                        </td>
                    </tr>
                    <tr>
                        <td style="background:' . self::BRAND_LIGHT_BG . ';padding:24px 32px;text-align:center;border-top:3px solid ' . self::BRAND_PRIMARY . '">
                            <p style="margin:0 0 6px;color:' . self::BRAND_DARK . ';font-size:15px;font-weight:700">' . $church . '</p>
                            ' . $footerContact . '
                            <p style="margin:14px 0 0;font-size:12px;color:#94a3b8">
                                <a href="' . $appUrl . '" style="color:' . self::BRAND_DARK . ';text-decoration:none;font-weight:600">' . $appUrl . '</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    private function emailHeaderHtml(PHPMailer $mail, string $church, string $slogan): string
    {
        $html = '';
        $logoSrc = $this->resolveLogoSrc($mail);

        if ($logoSrc !== null) {
            $html .= '<img src="' . htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') . '" alt="' . $church . '" width="72" style="display:block;margin:0 auto 16px;max-width:72px;max-height:72px;width:auto;height:auto;border:0">';
        }

        $html .= '<p style="margin:0;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:-0.3px;line-height:1.3">' . $church . '</p>';
        $html .= '<p style="margin:10px 0 0;color:rgba(255,255,255,0.92);font-size:12px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase">' . $slogan . '</p>';

        return $html;
    }

    private function resolveLogoSrc(PHPMailer $mail): ?string
    {
        $uploaded = SettingsService::get('church_logo_path');
        $basePath = dirname(__DIR__, 2) . '/public/';

        if ($uploaded && is_file($basePath . $uploaded)) {
            $fullPath = $basePath . $uploaded;
            $mail->addEmbeddedImage($fullPath, 'church-logo', basename($fullPath));

            return 'cid:church-logo';
        }

        return SettingsService::logoUrl();
    }

    private function buttonStyle(): string
    {
        return 'background:linear-gradient(135deg,' . self::BRAND_PRIMARY . ' 0%,' . self::BRAND_DARK . ' 100%);color:#ffffff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:16px;display:inline-block;box-shadow:0 4px 14px rgba(53,175,230,0.35)';
    }

    private function mutedNote(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
