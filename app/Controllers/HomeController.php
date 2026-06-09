<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\QrService;

class HomeController
{
    public function index(): void
    {
        if (Auth::check()) {
            View::redirect(Auth::isAdmin() ? '/admin' : '/portal');
        }

        $token = 'church-onboard-2026';
        $qr = new QrService();
        $onboardingUrl = $qr->onboardingUrl($token);

        View::render('home', [
            'title' => 'Welcome',
            'onboardingUrl' => $onboardingUrl,
            'qrDataUri' => $qr->generateDataUri($onboardingUrl),
        ]);
    }
}
