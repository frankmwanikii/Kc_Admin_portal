<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;

class DocsController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $section = strtolower((string) ($_GET['section'] ?? 'dashboard'));
        if (!in_array($section, ['dashboard', 'finance'], true)) {
            $section = 'dashboard';
        }

        View::render('admin/docs/index', [
            'title' => 'Docs',
            'section' => $section,
        ], 'layouts/admin');
    }
}
