<?php

use Illuminate\Support\Facades\Route;

/*
| مسیرهای API محصول. مسیرهای اختصاصی هر ماژول داخل خود ماژول تعریف
| می‌شوند (routes/api.php هر ماژول توسط ServiceProvider بارگذاری می‌شود).
| این فایل فقط نقطهٔ سلامت و نسخه را نگه می‌دارد.
*/

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'time' => now()->toIso8601String(),
    ]));
});
