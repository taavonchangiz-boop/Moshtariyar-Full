<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * تا زمانی که برنامه نصب نشده، همهٔ درخواست‌های وب را به نصاب هدایت می‌کند.
 * نصاب خودش (/install) و فایل‌های استاتیک مستثنا هستند.
 */
class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // بررسی وجود فایل قفل نصب در پوشه storage
        $installed = file_exists(storage_path('installed.lock'));

        // اگر نصب نشده و کاربر در صفحات نصاب نیست -> هدایت به صفحه اول نصاب
        if (! $installed && ! $request->is('install', 'install/*')) {
            return redirect('/install');
        }

        // اگر نصب شده ولی کاربر سعی کرد دوباره به نصاب برود -> هدایت به لاگین
        if ($installed && $request->is('install', 'install/*')) {
            return redirect('/login');
        }

        return $next($request);
    }
}