<?php

use Illuminate\Support\Facades\Route;
use Modules\WooBridge\Http\Controllers\WebhookController;
use Modules\WooBridge\Http\Controllers\ConnectionController;
use Modules\WooBridge\Http\Controllers\WalletController;

Route::prefix('v1/woobridge')->group(function () {

    // Webhook عمومی (با HMAC محافظت می‌شود، نیازی به auth ندارد)
    Route::post('webhook/{connection}', [WebhookController::class, 'handle']);
    Route::get('test/{connection}', [WebhookController::class, 'ping']);
    Route::post('test/{connection}', [WebhookController::class, 'test']);

    // کیف پول باشگاه برای ووکامرس (با همان HMAC اتصال محافظت می‌شود)
    Route::post('wallet/{connection}/balance', [WalletController::class, 'balance']);
    Route::post('wallet/{connection}/debit', [WalletController::class, 'debit']);
    Route::post('wallet/{connection}/refund', [WalletController::class, 'refund']);

    // مدیریت اتصال‌ها (نیازمند احراز هویت)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('connections', [ConnectionController::class, 'index']);
        Route::post('connections', [ConnectionController::class, 'store']);
        Route::post('connections/{connection}/import', [ConnectionController::class, 'import']);
        Route::post('sync-logs/{log}/resend', [ConnectionController::class, 'resend']);
    });
});
