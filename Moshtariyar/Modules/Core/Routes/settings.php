<?php

/**
 * روت‌های مرکز تنظیمات
 *
 * روش استفاده: در routes/web.php این خط را اضافه کن:
 *   require base_path('Modules/Core/Routes/settings.php');
 *
 * یا محتوای این فایل را مستقیماً در routes/web.php کپی کن.
 */

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\SettingsController;

Route::middleware(['web', 'auth'])->prefix('app')->group(function () {

    // نمای اصلی تنظیمات
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');

    // ذخیره همه تنظیمات (هر ۹ تب یکجا از این آدرس استفاده می‌کنند)
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

    // حذف لوگوها - ۳ روت جدید
    Route::post('settings/logo/remove', [SettingsController::class, 'removeLogo'])->name('settings.logo.remove');
    Route::post('settings/site-logo/remove', [SettingsController::class, 'removeSiteLogo'])->name('settings.site-logo.remove');
    Route::post('settings/club-logo/remove', [SettingsController::class, 'removeClubLogo'])->name('settings.club-logo.remove');
    Route::post('settings/favicon/remove', [SettingsController::class, 'removeFavicon'])->name('settings.favicon.remove');

    // کاربران
    Route::post('settings/users',        [SettingsController::class, 'saveUser'])->name('settings.users.create');
    Route::post('settings/users/{id}',   [SettingsController::class, 'saveUser'])->name('settings.users.update')->whereNumber('id');
    Route::delete('settings/users/{id}', [SettingsController::class, 'deleteUser'])->name('settings.users.delete')->whereNumber('id');
});