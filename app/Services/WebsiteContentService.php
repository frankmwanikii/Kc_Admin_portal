<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Loads church site content from local config/church-site.php (self-contained for deployment).
 */
class WebsiteContentService
{
    private static ?array $data = null;

    /** @return array<string, mixed> */
    public static function bootstrap(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $configFile = dirname(__DIR__, 2) . '/config/church-site.php';
        $config = is_file($configFile) ? require $configFile : [];

        $defaults = array_merge(self::fallbackDefaults(), is_array($config) ? $config : []);

        if (SettingsService::churchName()) {
            $defaults['site_name'] = SettingsService::churchName();
        }
        if ($addr = SettingsService::churchAddress()) {
            $defaults['site_address'] = $addr;
        }
        if ($phone = SettingsService::churchPhone()) {
            $defaults['site_phone'] = $phone;
            if (empty($defaults['footer_phones'])) {
                $defaults['footer_phones'] = [['label' => 'Office:', 'number' => $phone]];
            }
        }

        $defaults['form_api_url'] = '/api/send-form';

        if (!function_exists('render_social_icon')) {
            require __DIR__ . '/../../views/partials/website-footer-icons.php';
        }

        self::$data = $defaults;

        return self::$data;
    }

    /** @return array<string, mixed> */
    private static function fallbackDefaults(): array
    {
        return [
            'site_name' => 'Kingdomcity Church Nanyuki',
            'site_tagline' => 'Transformed Lives',
            'site_email' => 'welcome@kingdomcitychurchnanyuki.org',
            'site_phone' => '',
            'site_address' => 'Nanyuki, Laikipia County, Kenya',
            'public_website_url' => '',
            'site_logo' => ['image' => 'images/kc-logo.png', 'height_mobile' => 62, 'height_desktop' => 74],
            'campuses' => [
                ['id' => 'nanyuki', 'name' => 'Kingdomcity Church Nanyuki', 'short_label' => 'Nanyuki'],
            ],
            'nav_items' => [],
            'hero_slides' => [['image' => 'images/kingdomcity-church-hero.png', 'alt' => 'Kingdomcity Church']],
            'connect_cards' => [],
            'ministries_list' => [],
            'footer_phones' => [],
            'social_links' => [],
            'form_api_url' => '/api/send-form',
        ];
    }

    /** Local asset under public/ */
    public static function assetUrl(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        return '/' . $path;
    }

    /** External public church website page (footer quick links). */
    public static function pageUrl(string $file): string
    {
        $base = rtrim(self::bootstrap()['public_website_url'] ?? '', '/');
        $file = ltrim($file, '/');

        if ($base === '') {
            return self::assetUrl($file);
        }

        return $base . '/' . $file;
    }

    public static function ministryUrl(string $slug): string
    {
        return self::pageUrl('ministry.php?slug=' . rawurlencode($slug));
    }

    public static function logoUrl(): string
    {
        return self::assetUrl(self::bootstrap()['site_logo']['image'] ?? 'images/kc-logo.png');
    }

    /** @return array{height_mobile: int, height_desktop: int} */
    public static function logoHeights(): array
    {
        $logo = self::bootstrap()['site_logo'] ?? [];

        return [
            'height_mobile' => max(32, min(120, (int) ($logo['height_mobile'] ?? 62))),
            'height_desktop' => max(32, min(120, (int) ($logo['height_desktop'] ?? 68))),
        ];
    }

    /** @return array<string, string> */
    public static function connectModalClasses(): array
    {
        return [
            'new-beginning' => 'js-new-beginning-open',
            'new-here' => 'js-new-here-open',
            'kingdom-groups' => 'js-kingdom-groups-open',
            'join' => 'js-join-open',
        ];
    }
}
