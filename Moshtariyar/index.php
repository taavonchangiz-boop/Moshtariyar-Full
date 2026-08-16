<?php

/**
 * مشتری‌یار — Entry Point
 * Laravel 11 - cPanel Shared Hosting
 * DocumentRoot: public_html
 * App Location: public_html/crm
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$root = __DIR__;

// حذف منطق قدیمی چک کردن نصب از اینجا
// اجازه می‌دهیم لاراول بوت شود و Middleware.RedirectIfNotInstalled مدیریت را به عهده بگیرد

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $root . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $root . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $root . '/bootstrap/app.php';

$app->handleRequest(Request::capture());