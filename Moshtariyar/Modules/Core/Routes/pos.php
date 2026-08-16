<?php

/**
 * روت‌های صندوق فروش سریع - حرفه‌ای
 *
 * برای فعال‌سازی، در انتهای routes/web.php این خط را اضافه کن:
 *   require base_path('Modules/Core/Routes/pos.php');
 */

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\PosController;

Route::middleware(['web', 'auth'])->prefix('app')->group(function () {

    // صندوق اصلی - باید قبل از پارامتر {order} باشد
    Route::get('pos', [PosController::class, 'index'])->name('pos.index')->middleware('can.do:orders.view');
    Route::get('pos/cashier', [PosController::class, 'cashierReport'])->name('pos.cashier')->middleware('can.do:orders.view');
    Route::post('pos/shifts/open', [PosController::class, 'openShift'])->name('pos.shifts.open')->middleware('can.do:orders.manage');
    Route::post('pos/shifts/{shift}/close', [PosController::class, 'closeShift'])->name('pos.shifts.close')->middleware('can.do:orders.manage')->whereNumber('shift');

    // جستجوها
    Route::get('pos/search-products', [PosController::class, 'searchProducts'])->name('pos.search.products')->middleware('can.do:orders.view');
    Route::get('pos/search-customers', [PosController::class, 'searchCustomers'])->name('pos.search.customers')->middleware('can.do:orders.view');
    Route::get('pos/customer/{customer}/retention', [PosController::class, 'customerRetentionDetail'])->name('pos.customer.retention')->middleware('can.do:customers.view')->whereNumber('customer');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout')->middleware('can.do:orders.manage');

    // چاپ حرارتی - باید آخر باشد و فقط عدد قبول کند
    Route::get('pos/{order}/thermal', [PosController::class, 'thermal'])->name('pos.thermal')->middleware('can.do:orders.view')->whereNumber('order');
});