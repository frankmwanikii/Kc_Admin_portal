<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Services\WebsiteContentService;

class MinistryController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $content = WebsiteContentService::bootstrap();
        $ministries = $content['ministries_list'] ?? [];

        $enriched = array_map(static function (array $m): array {
            $slug = $m['slug'] ?? '';

            return array_merge($m, [
                'website_url' => $slug !== '' ? WebsiteContentService::ministryUrl($slug) : '',
            ]);
        }, $ministries);

        View::render('admin/ministries/index', [
            'title' => 'Ministries',
            'ministries' => $enriched,
            'websiteUrl' => $content['public_website_url'] ?? '',
        ], 'layouts/admin');
    }
}
