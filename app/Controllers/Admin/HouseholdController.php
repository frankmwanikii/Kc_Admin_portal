<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Models\Household;
use App\Models\Member;

class HouseholdController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/households/index', [
            'title' => 'Households',
            'households' => Household::withMemberCounts(),
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        Auth::requireAdmin();
        $household = Household::find((int) $id);
        if (!$household) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not Found']);
            return;
        }
        View::render('admin/households/show', [
            'title' => $household->name,
            'household' => $household,
            'members' => Member::byHousehold($household->id),
        ], 'layouts/admin');
    }
}
