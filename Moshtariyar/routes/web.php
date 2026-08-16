<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Modules\Core\Http\Controllers\DashboardController;
use Modules\Core\Http\Controllers\SettingsController;
use Modules\Core\Http\Controllers\ExportController;
use Modules\Core\Http\Controllers\TicketController;
use Modules\Core\Http\Controllers\SearchController;
use Modules\Core\Http\Controllers\BackupController;
use Modules\Core\Http\Controllers\KnowledgeBaseController;
use Modules\Core\Http\Controllers\RfmController;
use Modules\Core\Http\Controllers\SegmentController;
use Modules\Core\Http\Controllers\JourneyController;

// صفحهٔ خوش‌آمد عمومی — فقط توضیح دقیق برنامه، بدون لینک به بخش خاص داخلی
Route::get('/', fn () => view('welcome'));
// راهنمای کامل، جامع و دقیق — بدون اسم برند خارجی
Route::get('/docs', fn () => view('docs.index'))->name('docs.index');
Route::get('/docs/{section}', function($section){
    $allowed = ['quickstart','concepts','architecture','dashboard','clv','rfm','reports','retention','recovery-revenue','recovery-history','pos','orders','products','loyalty','missions','club-auth','referrals','campaigns','workflows','automation-recovery','messaging','sms','tickets','assistant','woocommerce','api','settings','security','backup','buttons','roles','recovery-widget'];
    if(!in_array($section, $allowed)) abort(404);
    return view('docs.index', ['scrollTo'=>$section]);
})->name('docs.section');
// آموزش صفر تا صد — جزء به جزء با تنظیمات دقیق — بر اساس تمام فایل‌های برنامه — بدون نمایش مسیر پوشه‌ها به هکر
Route::get('/amoozesh', fn () => view('training.index'))->name('amoozesh.index');
Route::get('/training', fn () => view('training.index'))->name('training.index');
Route::get('/amozesh', fn () => view('training.index'))->name('amozesh.index');
Route::get('/learn', fn () => view('training.index'))->name('learn.index');
// آموزش باشگاه — صفحه مجزا — تمام قسمت‌های باشگاه جزء به جزء
Route::get('/amoozesh/bashgah', fn () => view('training.club'))->name('amoozesh.club');
Route::get('/training/club', fn () => view('training.club'))->name('training.club');
Route::get('/club/training', fn () => view('training.club'))->name('club.training');
Route::get('/bashgah/amoozesh', fn () => view('training.club'))->name('bashgah.amoozesh');
Route::get('/widget/assistant.js', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'widgetScript']);

// احراز هویت (عمومی)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/send-code', [AuthController::class, 'sendLoginCode'])->name('login.send-code');
Route::get('/login/verify-code', [AuthController::class, 'showVerifyCodeForm'])->name('login.verify-form');
Route::post('/login/verify-code', [AuthController::class, 'verifyLoginCode'])->name('login.verify-code');
Route::get('/forgot-password', [AuthController::class, 'forgotForm']);
Route::post('/forgot-password', [AuthController::class, 'sendResetCode']);
Route::get('/reset-password', [AuthController::class, 'resetForm']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::match(['GET','POST'], '/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('app')->group(function () {
    Route::get('/', [DashboardController::class, 'dashboard']);
    Route::get('/search', [SearchController::class, 'index'])->middleware('can.do:dashboard.view');
    Route::get('/search/advanced', [SearchController::class, 'advanced'])->middleware('can.do:dashboard.view');
    Route::get('/assistant', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'adminPage'])->middleware('can.do:dashboard.view');
    Route::get('/assistant/import', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'importLegion'])->middleware('can.do:dashboard.view');
    Route::post('/assistant/ask', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'adminAsk'])->middleware('can.do:dashboard.view');
    Route::get('/assistant/settings', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'settingsPage'])->middleware('can.do:dashboard.view');
    Route::get('/assistant/models', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'getModels'])->middleware('can.do:dashboard.view');
    Route::post('/assistant/test-connection', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'testConnection'])->middleware('can.do:dashboard.view');
    Route::post('/assistant/settings/save', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'saveSettings'])->middleware('can.do:dashboard.view')->name('assistant.settings.save');
    Route::get('/assistant/widget-settings', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'widgetSettingsPage'])->middleware('can.do:dashboard.view');
    Route::post('/assistant/widget-settings/save', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'saveWidgetSettings'])->middleware('can.do:dashboard.view')->name('assistant.widget.save');

    Route::middleware('can.do:tickets.view')->group(function () {
        Route::get('/kb', [KnowledgeBaseController::class, 'index']);
        Route::get('/kb/create', [KnowledgeBaseController::class, 'create']);
        Route::post('/kb', [KnowledgeBaseController::class, 'store']);
        Route::get('/kb/chat-logs', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'logs']);
        Route::get('/kb/chat-logs/{session}', [\Modules\Core\Http\Controllers\KbChatbotController::class, 'showLog']);
        Route::get('/kb/categories', [KnowledgeBaseController::class, 'categories']);
        Route::post('/kb/categories', [KnowledgeBaseController::class, 'storeCategory']);
        Route::post('/kb/categories/{category}/toggle', [KnowledgeBaseController::class, 'toggleCategory']);
        Route::get('/kb/{article}/edit', [KnowledgeBaseController::class, 'edit']);
        Route::put('/kb/{article}', [KnowledgeBaseController::class, 'update']);
        Route::delete('/kb/{article}', [KnowledgeBaseController::class, 'destroy']);
    });

    Route::middleware('can.do:customers.view')->group(function () {
        Route::get('/customers', [DashboardController::class, 'customers']);
        Route::get('/customers/{customer}', [DashboardController::class, 'customer360']);
        Route::get('/customers/{customer}/edit', [\Modules\Core\Http\Controllers\CustomerController::class, 'edit']);
        Route::put('/customers/{customer}', [\Modules\Core\Http\Controllers\CustomerController::class, 'update']);
        Route::post('/customers/{customer}/return-campaign', [\Modules\Core\Http\Controllers\CustomerController::class, 'sendReturnCampaign'])->middleware('can.do:customers.manage');
    });
    Route::get('/customers/export', [ExportController::class, 'customers'])->middleware('can.do:customers.export');

    Route::middleware('can.do:customers.view')->group(function () {
        Route::get('/retention', [\Modules\Core\Http\Controllers\RetentionController::class, 'index'])->name('retention.index');
        Route::post('/retention/bulk-campaign', [\Modules\Core\Http\Controllers\RetentionController::class, 'bulkCampaign'])->middleware('can.do:customers.manage')->name('retention.bulk');
    });

    Route::get('/orders', [DashboardController::class, 'orders'])->middleware('can.do:orders.view');
    Route::get('/orders/export', [ExportController::class, 'orders'])->middleware('can.do:orders.view');
    Route::get('/orders/{order}', [DashboardController::class, 'showOrder'])->middleware('can.do:orders.view');
    Route::get('/orders/{order}/invoice', [DashboardController::class, 'showInvoice'])->middleware('can.do:orders.view');
    Route::middleware('can.do:orders.manage')->group(function () {
        Route::post('/orders/{order}/status', [DashboardController::class, 'updateOrderStatus']);
        Route::post('/orders', [DashboardController::class, 'storeOrder']);
    });
    Route::post('/orders/{order}/tax-invoice', [DashboardController::class, 'issueTaxInvoice'])->middleware('can.do:orders.invoice');

    Route::middleware('can.do:leads.view')->group(function () {
        Route::get('/pipeline', [\Modules\Core\Http\Controllers\LeadPipelineController::class, 'board']);
        Route::get('/pipeline/{lead}', [\Modules\Core\Http\Controllers\LeadPipelineController::class, 'show']);
    });
    Route::middleware('can.do:leads.manage')->group(function () {
        Route::post('/pipeline', [\Modules\Core\Http\Controllers\LeadPipelineController::class, 'store']);
        Route::post('/pipeline/{lead}/move', [\Modules\Core\Http\Controllers\LeadPipelineController::class, 'move']);
        Route::post('/pipeline/{lead}/convert', [\Modules\Core\Http\Controllers\LeadPipelineController::class, 'convert']);
    });

    Route::middleware('can.do:customers.view')->group(function () {
        Route::post('/activities', [\Modules\Core\Http\Controllers\ActivityController::class, 'store']);
        Route::post('/activities/{activity}/toggle', [\Modules\Core\Http\Controllers\ActivityController::class, 'toggle']);
        Route::get('/reminders', [\Modules\Core\Http\Controllers\ActivityController::class, 'reminders']);
        Route::post('/messages/send', [\Modules\Core\Http\Controllers\MessageController::class, 'send'])->name('messages.send');
        Route::get('/messages', [\Modules\Core\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
        Route::post('/messages/send-bulk', [\Modules\Core\Http\Controllers\MessageController::class, 'sendBulk'])->name('messages.send-bulk');
        Route::get('/messages/search-customers', [\Modules\Core\Http\Controllers\MessageController::class, 'searchCustomers'])->name('messages.search-customers');
    });

    Route::middleware('can.do:customers.view')->group(function () {
        Route::get('/products', [\Modules\Core\Http\Controllers\ProductController::class, 'index']);
        Route::get('/products/{product}/edit', [\Modules\Core\Http\Controllers\ProductController::class, 'edit']);
        Route::get('/products/{product}', [\Modules\Core\Http\Controllers\ProductController::class, 'show']);
        Route::post('/products/{product}/calculate', [\Modules\Core\Http\Controllers\ProductController::class, 'calculateAjax']);
    });
    Route::middleware('can.do:orders.manage')->group(function () {
        Route::post('/products', [\Modules\Core\Http\Controllers\ProductController::class, 'store']);
        Route::put('/products/{product}', [\Modules\Core\Http\Controllers\ProductController::class, 'update']);
        Route::post('/products/{product}/push-woocommerce', [\Modules\Core\Http\Controllers\ProductController::class, 'pushToWooCommerce']);
        Route::post('/products/{product}/move', [\Modules\Core\Http\Controllers\ProductController::class, 'move']);
        Route::post('/products/{product}/sell', [\Modules\Core\Http\Controllers\ProductController::class, 'createQuickOrder']);
    });
    
    Route::get('/products/{product}/sell', function() {
        return "برای ثبت سفارش، لطفاً از دکمه‌های موجود در صفحه محصول استفاده کنید.";
    });

    Route::get('/reports', [\Modules\Core\Http\Controllers\ReportController::class, 'index'])->middleware('can.do:dashboard.view');
    Route::get('/reports/export', [\Modules\Core\Http\Controllers\ReportController::class, 'export'])->middleware('can.do:dashboard.view');
    Route::get('/reports/rfm', [RfmController::class, 'index'])->middleware('can.do:dashboard.view');
    Route::get('/reports/rfm/export', [RfmController::class, 'export'])->middleware('can.do:dashboard.view');
    Route::get('/rfm', [RfmController::class, 'index'])->middleware('can.do:dashboard.view');
    Route::get('/app/rfm', [RfmController::class, 'index'])->middleware('can.do:dashboard.view');

    Route::get('/insights', [\Modules\Core\Http\Controllers\InsightsController::class, 'index'])->middleware('can.do:dashboard.view');

    Route::middleware('can.do:dashboard.view')->group(function () {
        Route::get('/segments', [SegmentController::class, 'index']);
        Route::get('/segments/create', [SegmentController::class, 'create']);
        Route::post('/segments', [SegmentController::class, 'store']);
        Route::match(['GET','POST'], '/segments/auto', function(\Modules\Core\Services\ClvAdvancedService $service, \Modules\Core\Services\RetentionAutomationService $retention){
            try {
                \Illuminate\Support\Facades\Cache::flush();
                if (class_exists(\Modules\Core\Entities\Setting::class)) {
                    \Modules\Core\Entities\Setting::flush();
                }
            } catch (\Throwable $e) {}
            $result = $service->autoSegment();
            $retentionResult = $retention->runAfterSegmentation($result['analysis'] ?? []);
            $msg = "بخش‌بندی خودکار انجام شد: {$result['created']} بخش جدید، {$result['assigned']} مشتری";
            return redirect('/app/reports?tab=clv')->with('status', $msg);
        });
        Route::get('/segments/evaluate-all', [SegmentController::class, 'evaluateAll']);
        Route::get('/segments/{segment}', [SegmentController::class, 'show']);
        Route::get('/segments/{segment}/edit', [SegmentController::class, 'edit']);
        Route::put('/segments/{segment}', [SegmentController::class, 'update']);
        Route::delete('/segments/{segment}', [SegmentController::class, 'destroy']);
        Route::get('/segments/{segment}/evaluate', [SegmentController::class, 'evaluate']);
    });

    Route::middleware('can.do:dashboard.view')->group(function () {
        Route::get('/journeys', [JourneyController::class, 'index']);
        Route::get('/journeys/create', [JourneyController::class, 'create']);
        Route::post('/journeys', [JourneyController::class, 'store']);
        Route::get('/journeys/{workflow}', [JourneyController::class, 'show']);
        Route::get('/journeys/{workflow}/edit', [JourneyController::class, 'edit']);
        Route::put('/journeys/{workflow}', [JourneyController::class, 'update']);
        Route::delete('/journeys/{workflow}', [JourneyController::class, 'destroy']);
        Route::get('/journeys/{workflow}/toggle', [JourneyController::class, 'toggle']);
    });

    Route::middleware('can.do:dashboard.view')->group(function () {
        Route::get('/workflows', [\Modules\Automation\Http\Controllers\WorkflowController::class, 'index']);
        Route::get('/workflows/create', [\Modules\Automation\Http\Controllers\WorkflowController::class, 'create']);
        Route::post('/workflows', [\Modules\Automation\Http\Controllers\WorkflowController::class, 'store']);
        Route::post('/workflows/{workflow}/toggle', [\Modules\Automation\Http\Controllers\WorkflowController::class, 'toggle']);
        Route::delete('/workflows/{workflow}', [\Modules\Automation\Http\Controllers\WorkflowController::class, 'destroy']);
    });

    Route::middleware('can.do:dashboard.view')->group(function () {
        Route::get('/loyalty/campaigns', [\Modules\Loyalty\Http\Controllers\LoyaltyCampaignController::class, 'index']);
        Route::post('/loyalty/campaigns', [\Modules\Loyalty\Http\Controllers\LoyaltyCampaignController::class, 'store']);
        Route::post('/loyalty/campaigns/{campaign}/toggle', [\Modules\Loyalty\Http\Controllers\LoyaltyCampaignController::class, 'toggle']);
        Route::delete('/loyalty/campaigns/{campaign}', [\Modules\Loyalty\Http\Controllers\LoyaltyCampaignController::class, 'destroy']);
        Route::post('/loyalty/campaigns/{campaign}/reward-rules', [\Modules\Loyalty\Http\Controllers\LoyaltyCampaignController::class, 'storeRewardRule']);
        Route::post('/loyalty/campaign-reward-rules/{rule}/toggle', [\Modules\Loyalty\Http\Controllers\LoyaltyCampaignController::class, 'toggleRewardRule']);
        Route::post('/loyalty/campaign-rewards/release-due', [\Modules\Loyalty\Http\Controllers\LoyaltyCampaignController::class, 'releaseDueRewards']);
    });

    Route::get('/tax-invoices', [DashboardController::class, 'taxInvoices'])->middleware('can.do:tax.view');
    Route::get('/tax-invoices/export', [DashboardController::class, 'exportTaxInvoices'])->middleware('can.do:tax.view');
    Route::post('/tax-invoices/proforma', [DashboardController::class, 'storeProformaInvoice'])->middleware('can.do:tax.view');
    Route::post('/tax-invoices/settings', [DashboardController::class, 'saveProformaSettings'])->middleware('can.do:tax.view');
    Route::get('/tax-invoices/{invoice}', [DashboardController::class, 'showTaxInvoice'])->middleware('can.do:tax.view');

    Route::get('/woocommerce', [DashboardController::class, 'woocommerce'])->middleware('can.do:woocommerce.view');
    Route::middleware('can.do:woocommerce.manage')->group(function () {
        Route::post('/woocommerce', [DashboardController::class, 'storeConnection']);
        Route::put('/woocommerce/{connection}', [DashboardController::class, 'updateConnection']);
        Route::delete('/woocommerce/{connection}', [DashboardController::class, 'destroyConnection']);
        Route::post('/woocommerce/bulk-delete', [DashboardController::class, 'bulkDestroyConnections']);
        Route::post('/woocommerce/{connection}/import', [DashboardController::class, 'importConnection']);
        Route::post('/woocommerce/{connection}/pull-test', [DashboardController::class, 'testWooPull']);
        Route::post('/woocommerce/{connection}/pull-now', [DashboardController::class, 'pullImportNow']);
        Route::post('/woocommerce/{connection}/pull-customers-now', [DashboardController::class, 'pullCustomersNow']);
        Route::post('/woocommerce/{connection}/pull-products-now', [DashboardController::class, 'pullProductsNow']);
        Route::post('/woocommerce/{connection}/pull-all-now', [DashboardController::class, 'pullAllNow']);
        Route::post('/woocommerce/{connection}/import-file', [DashboardController::class, 'importWooFile']);
    });

    Route::middleware('can.do:woocommerce.view')->group(function () {
        Route::get('/sync-logs', [DashboardController::class, 'syncLogs']);
        Route::get('/sync-logs/export', [ExportController::class, 'syncLogs']);
        Route::get('/sync-logs/{log}', [DashboardController::class, 'showSyncLog']);
        Route::post('/sync-logs/{log}/resend', [DashboardController::class, 'resendLog']);
    });

    Route::middleware('can.do:tickets.view')->group(function () {
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/tickets/create', [TicketController::class, 'create']);
        Route::get('/tickets/responses', [TicketController::class, 'responses']);
        Route::get('/tickets/sla', [TicketController::class, 'slaReport']);
        Route::get('/tickets/replies/{reply}/attachment', [TicketController::class, 'downloadAttachment']);
        Route::get('/tickets/{ticket}/modal', [TicketController::class, 'showModal']);
        Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    });
    Route::middleware('can.do:tickets.manage')->group(function () {
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::post('/tickets/responses', [TicketController::class, 'storeResponse']);
        Route::post('/tickets/responses/{response}/toggle', [TicketController::class, 'toggleResponse']);
        Route::delete('/tickets/responses/{response}', [TicketController::class, 'deleteResponse']);
        Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply']);
        Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
        Route::post('/tickets/replies/{reply}/attachment/delete', [TicketController::class, 'deleteAttachment']);
    });

    Route::middleware('can.do:settings.manage')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::post('/settings', [SettingsController::class, 'save']);
        Route::get('/settings/export', [SettingsController::class, 'export']);
        Route::get('/backups', [BackupController::class, 'index']);
        Route::get('/backups/database', [BackupController::class, 'database']);
        Route::get('/backups/settings', [BackupController::class, 'settings']);
    });

    Route::middleware('can.do:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::post('/roles', [UserController::class, 'storeRole']);
        Route::put('/roles/{role}', [UserController::class, 'updateRole']);
        Route::delete('/roles/{role}', [UserController::class, 'destroyRole']);
    });

    Route::view('/support', 'app.support');
    Route::post('/support', [TicketController::class, 'store']);
});

Route::middleware('auth')->prefix('superadmin')->group(function () {
    Route::get('/businesses', [\App\Http\Controllers\SuperAdmin\BusinessController::class, 'index']);
    Route::post('/businesses', [\App\Http\Controllers\SuperAdmin\BusinessController::class, 'store']);
    Route::post('/businesses/{business}/toggle', [\App\Http\Controllers\SuperAdmin\BusinessController::class, 'toggle']);
    Route::get('/users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'index']);
    Route::post('/users/{user}/toggle', [\App\Http\Controllers\SuperAdmin\UserController::class, 'toggleStatus']);
    Route::post('/users/{user}/reset-password', [\App\Http\Controllers\SuperAdmin\UserController::class, 'resetPassword']);
    Route::post('/users/{user}/login-as', [\App\Http\Controllers\SuperAdmin\UserController::class, 'loginAs']);
});

require base_path('Modules/Core/Routes/settings.php');
require base_path('Modules/Core/Routes/reports.php');
require base_path('Modules/Core/Routes/warehouses.php');
require base_path('Modules/Core/Routes/pos.php');