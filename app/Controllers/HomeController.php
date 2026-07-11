<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class HomeController
{
    public function index(): void
    {
        if (Auth::check()) {
            View::redirect(Auth::isAdmin() ? '/admin' : '/portal');
        }

        View::redirect('/login');
    }
}
