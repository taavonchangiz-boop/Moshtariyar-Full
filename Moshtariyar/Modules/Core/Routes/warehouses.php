<?php

/**
 * روت‌های مرکز انبار چندگانه
 *
 * برای فعال‌سازی، در انتهای routes/web.php این خط را اضافه کن:
 *   require base_path('Modules/Core/Routes/warehouses.php');
 */

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\WarehouseController;

Route::middleware(['web', 'auth'])->prefix('app')->group(function () {

    // بورد اصلی انبارها
    Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index')->middleware('can.do:dashboard.view');
    Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store')->middleware('can.do:orders.manage');
    Route::delete('warehouses/{warehouse}/delete', [WarehouseController::class, 'destroy'])->name('warehouses.destroy')->middleware('can.do:orders.manage')->whereNumber('warehouse');

    // رسید خرید از تامین‌کننده - افزایش موجودی
    Route::post('warehouses/supplies', [WarehouseController::class, 'storeSupply'])->name('warehouses.supplies.store')->middleware('can.do:orders.manage');

    // انتقال بین انبارها
    Route::post('warehouses/transfers', [WarehouseController::class, 'storeTransfer'])->name('warehouses.transfers.store')->middleware('can.do:orders.manage');

    // اسقاط کالا
    Route::post('warehouses/write-offs', [WarehouseController::class, 'storeWriteOff'])->name('warehouses.writeoffs.store')->middleware('can.do:orders.manage');

    // تامین‌کنندگان
    Route::post('warehouses/suppliers', [WarehouseController::class, 'storeSupplier'])->name('warehouses.suppliers.store')->middleware('can.do:orders.manage');
});