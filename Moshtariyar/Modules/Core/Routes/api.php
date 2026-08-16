<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CustomerController;
use Modules\Core\Http\Controllers\LeadController;
use Modules\Core\Http\Controllers\Api\AuthApiController;
use Modules\Core\Http\Controllers\Api\MobileApiController;
use Modules\Core\Http\Controllers\KbChatbotController;
use Modules\Loyalty\Http\Controllers\CustomerPortalApiController;

Route::prefix('v1')->group(function () {
    // ثبت سرنخ از فرم سایت (عمومی)
    Route::post('leads', [LeadController::class, 'store']);
    Route::post('assistant/ask', [KbChatbotController::class, 'publicAsk']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('customers', [CustomerController::class, 'index']);
        Route::post('customers', [CustomerController::class, 'store']);
        Route::get('customers/{customer}/360', [CustomerController::class, 'show360']);
    });
});

// ---------- API اپ موبایل (Sanctum) ----------
Route::prefix('v1/club')->group(function () {
    Route::post('login', [CustomerPortalApiController::class, 'sendCode']);
    Route::post('verify', [CustomerPortalApiController::class, 'verify']);
    Route::middleware('api')->group(function () {
        Route::get('me', [CustomerPortalApiController::class, 'me']);
        Route::get('transactions', [CustomerPortalApiController::class, 'transactions']);
        Route::get('referrals', [CustomerPortalApiController::class, 'referrals']);
    });
});

Route::prefix('v1/mobile')->group(function () {
    Route::post('login', [AuthApiController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthApiController::class, 'me']);
        Route::post('logout', [AuthApiController::class, 'logout']);
        Route::get('dashboard', [MobileApiController::class, 'dashboard']);
        Route::get('customers', [MobileApiController::class, 'customers']);
        Route::get('customers/{customer}', [MobileApiController::class, 'customer']);
        Route::get('orders', [MobileApiController::class, 'orders']);
        Route::post('leads', [MobileApiController::class, 'createLead']);
    });
});
