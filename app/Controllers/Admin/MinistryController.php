<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Models\Ministry;

class MinistryController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/ministries/index', [
            'title' => 'Groups & Ministries',
            'ministries' => Ministry::withDetails(),
        ], 'layouts/admin');
    }
}
