<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Loads footer content from local config/church-site.php.
 */
class WebsiteFooterService
{
    /** @return array<string, mixed> */
    public static function data(): array
    {
        return WebsiteContentService::bootstrap();
    }

    public static function pageUrl(string $file): string
    {
        return WebsiteContentService::pageUrl($file);
    }

    public static function ministryUrl(string $slug): string
    {
        return WebsiteContentService::ministryUrl($slug);
    }
}
