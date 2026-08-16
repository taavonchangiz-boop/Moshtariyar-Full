<?php

define('LARAVEL_START', microtime(true));

$appPath = '/home/ayarproi/moshtariyar'; // حتما نام کاربری خود را جایگزین کنید

// --- بخش موقت برای رفع خطای 404 و پاکسازی کش ---
if (file_exists($appPath . '/vendor/autoload.php')) {
    require $appPath . '/vendor/autoload.php';
    // پاکسازی کش مسیرها و تنظیمات به صورت دستی
    if (file_exists($appPath . '/bootstrap/cache/routes-cache.php')) {
        unlink($appPath . '/bootstrap/cache/routes-cache.php');
    }
    if (file_exists($appPath . '/bootstrap/cache/config.php')) {
        unlink($appPath . '/bootstrap/cache/config.php');
    }
}
// ------------------------------------------------

$app = require_once $appPath . '/bootstrap/app.php';

$app->handleRequest(\Illuminate\Http\Request::capture());