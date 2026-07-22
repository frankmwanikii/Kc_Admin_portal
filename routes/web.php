<?php



declare(strict_types=1);



use App\Controllers\ConnectFormController;

use App\Controllers\AuthController;

use App\Controllers\HomeController;

use App\Controllers\SetupController;

use App\Controllers\MemberPortalController;

use App\Controllers\OnboardingController;

use App\Controllers\VisitorController;

use App\Controllers\Admin\CommunicationController;

use App\Controllers\Admin\DashboardController;

use App\Controllers\Admin\FinanceController;

use App\Controllers\Admin\InventoryController;

use App\Controllers\Admin\MemberController;

use App\Controllers\Admin\MinistryController;

use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\StaffController;



/** @var \App\Core\Router $router */



// Setup (first-run wizard)

$router->get('/setup', [SetupController::class, 'show']);

$router->post('/setup', [SetupController::class, 'install']);



// Public

$router->get('/', [HomeController::class, 'index']);

$router->get('/visit', [VisitorController::class, 'show']);

$router->post('/visit', [VisitorController::class, 'submit']);

$router->post('/api/send-form', [ConnectFormController::class, 'submit']);

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



$router->get('/admin/members', [MemberController::class, 'index']);

$router->post('/admin/members', [MemberController::class, 'store']);

$router->get('/admin/members/{id}', [MemberController::class, 'show']);

$router->post('/admin/members/{id}/status', [MemberController::class, 'updateStatus']);

$router->post('/admin/members/{id}/delete', [MemberController::class, 'delete']);



$router->get('/admin/staff', [StaffController::class, 'index']);

$router->post('/admin/staff', [StaffController::class, 'store']);

$router->post('/admin/staff/{id}', [StaffController::class, 'update']);

$router->post('/admin/staff/{id}/delete', [StaffController::class, 'delete']);



$router->get('/admin/inventory', [InventoryController::class, 'index']);

$router->post('/admin/inventory', [InventoryController::class, 'store']);

$router->post('/admin/inventory/{id}', [InventoryController::class, 'update']);

$router->post('/admin/inventory/{id}/delete', [InventoryController::class, 'delete']);



$router->get('/admin/finance', [FinanceController::class, 'index']);

$router->get('/admin/finance/sunday', [FinanceController::class, 'sundayEntry']);
$router->post('/admin/finance/sunday', [FinanceController::class, 'storeSundayEntry']);

$router->post('/admin/finance/arrears', [FinanceController::class, 'storeArrear']);

$router->post('/admin/finance/arrears/{id}', [FinanceController::class, 'updateArrear']);

$router->post('/admin/finance/arrears/{id}/delete', [FinanceController::class, 'deleteArrear']);

$router->get('/admin/finance/weekly/entry', [FinanceController::class, 'weeklyEntry']);

$router->post('/admin/finance/weekly', [FinanceController::class, 'storeWeekly']);

$router->post('/admin/finance/weekly/categories', [FinanceController::class, 'storeWeeklyCategory']);

$router->post('/admin/finance/weekly/categories/{slug}', [FinanceController::class, 'updateWeeklyCategory']);

$router->post('/admin/finance/weekly/categories/{slug}/delete', [FinanceController::class, 'deleteWeeklyCategory']);

$router->get('/admin/finance/collections/weekly/entry', [FinanceController::class, 'weeklyCollectionsEntry']);

$router->post('/admin/finance/collections/weekly', [FinanceController::class, 'storeWeeklyCollections']);

$router->post('/admin/finance/collections', [FinanceController::class, 'storeCollection']);
$router->post('/admin/finance/collections/{id}/delete', [FinanceController::class, 'deleteCollection']);

$router->get('/admin/finance/statement/pdf', [FinanceController::class, 'downloadStatementPdf']);
$router->get('/admin/finance/statement/csv', [FinanceController::class, 'downloadStatementCsv']);



$router->get('/admin/ministries', [MinistryController::class, 'index']);



$router->get('/admin/communications', [CommunicationController::class, 'index']);

$router->get('/admin/communications/create', [CommunicationController::class, 'create']);

$router->post('/admin/communications/send', [CommunicationController::class, 'send']);

$router->get('/admin/communications/birthdays', [CommunicationController::class, 'birthdays']);

$router->get('/admin/onboarding-qr', [OnboardingController::class, 'qrDisplay']);

$router->get('/admin/settings', [SettingsController::class, 'index']);

$router->post('/admin/settings', [SettingsController::class, 'update']);

