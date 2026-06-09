<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\SetupController;
use App\Controllers\MemberPortalController;
use App\Controllers\OnboardingController;
use App\Controllers\Admin\AttendanceController;
use App\Controllers\Admin\CommunicationController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\FinanceController;
use App\Controllers\Admin\HouseholdController;
use App\Controllers\Admin\MinistryController;
use App\Controllers\Admin\SettingsController;

/** @var \App\Core\Router $router */

// Setup (first-run wizard)
$router->get('/setup', [SetupController::class, 'show']);
$router->post('/setup', [SetupController::class, 'install']);

// Public
$router->get('/', [HomeController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/portal/access/{token}', [AuthController::class, 'magicAccess']);

// QR Onboarding
$router->get('/onboard/{token}', [OnboardingController::class, 'show']);
$router->post('/onboard/{token}', [OnboardingController::class, 'submit']);

// Member Portal
$router->get('/portal', [MemberPortalController::class, 'dashboard']);
$router->get('/portal/giving', [MemberPortalController::class, 'giving']);
$router->get('/portal/pledges', [MemberPortalController::class, 'pledges']);
$router->get('/portal/profile', [MemberPortalController::class, 'profile']);
$router->get('/portal/statement', [MemberPortalController::class, 'downloadStatement']);

// Admin
$router->get('/admin', [DashboardController::class, 'index']);
$router->get('/admin/households', [HouseholdController::class, 'index']);
$router->get('/admin/households/{id}', [HouseholdController::class, 'show']);
$router->get('/admin/attendance', [AttendanceController::class, 'index']);
$router->get('/admin/attendance/create', [AttendanceController::class, 'create']);
$router->post('/admin/attendance', [AttendanceController::class, 'store']);
$router->get('/admin/attendance/{id}', [AttendanceController::class, 'show']);
$router->get('/admin/finance', [FinanceController::class, 'index']);
$router->get('/admin/finance/create', [FinanceController::class, 'create']);
$router->post('/admin/finance', [FinanceController::class, 'store']);
$router->get('/admin/finance/mobile-money', [FinanceController::class, 'mobileMoney']);
$router->get('/admin/ministries', [MinistryController::class, 'index']);
$router->get('/admin/communications', [CommunicationController::class, 'index']);
$router->get('/admin/communications/create', [CommunicationController::class, 'create']);
$router->post('/admin/communications/send', [CommunicationController::class, 'send']);
$router->get('/admin/communications/birthdays', [CommunicationController::class, 'birthdays']);
$router->get('/admin/onboarding-qr', [OnboardingController::class, 'qrDisplay']);
$router->get('/admin/settings', [SettingsController::class, 'index']);
$router->post('/admin/settings', [SettingsController::class, 'update']);
