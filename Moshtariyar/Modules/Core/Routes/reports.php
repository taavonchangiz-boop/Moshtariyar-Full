<?php

/**
 * روت‌های مرکز گزارشات حرفه‌ای
 *
 * این فایل مسیرهای مدرن گزارشات را اضافه می‌کند
 * مسیرهای قدیم در routes/web.php باقی مانده تا سازگاری حفظ شود
 */

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\ReportController;
use Modules\Core\Http\Controllers\RecoveryHistoryController;

Route::middleware(['web', 'auth'])->prefix('app')->group(function () {

    // مرکز گزارشات حرفه‌ای با ۹ تب
    Route::get('reports/hub', [ReportController::class, 'hub'])->name('reports.hub');
    Route::get('reports/modern-export', [ReportController::class, 'modernExport'])->name('reports.modern.export');

    // سازگاری با پارامتر تب
    Route::get('reports/modern', function (\Illuminate\Http\Request $request) {
        return redirect('/app/reports?tab=' . $request->get('tab', 'overview') . '&range=' . $request->get('range', '30'));
    })->name('reports.modern');

    // تاریخچه گزارش‌های هفتگی بازگشت و حفظ — نسخه فوق حرفه‌ای ۱۴۰۴
    Route::get('reports/recovery/history', [RecoveryHistoryController::class, 'index'])->name('reports.recovery.history');
    Route::get('reports/recovery/history/export', [RecoveryHistoryController::class, 'export'])->name('reports.recovery.history.export');
    Route::post('reports/recovery/history/generate', [RecoveryHistoryController::class, 'generateNow'])->name('reports.recovery.history.generate');
    Route::get('reports/recovery/history/{id}', [RecoveryHistoryController::class, 'show'])->name('reports.recovery.history.show');
    Route::post('reports/recovery/history/{id}/resend', [RecoveryHistoryController::class, 'resend'])->name('reports.recovery.history.resend');
});