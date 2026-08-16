<?php

use Illuminate\Support\Facades\Route;
use Modules\IranPack\Http\Controllers\PaymentController;

Route::middleware(['web', 'auth'])->prefix('app')->group(function () {
    Route::get('/payments', [PaymentController::class, 'index'])->middleware('can.do:orders.view');
    Route::post('/orders/{order}/pay', [PaymentController::class, 'start'])->middleware('can.do:orders.manage');
});

// callback از درگاه: GET، نیاز به auth ندارد چون از سمت بانک می‌آید
// (با شناسهٔ پرداخت در query محافظت می‌شود)
Route::middleware('web')->get('/app/payments/callback', [PaymentController::class, 'callback']);
