<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        // Webhookهای ووکامرس و نصاب از CSRF معاف‌اند
        $middleware->validateCsrfTokens(except: [
            'api/v1/woobridge/webhook/*',
            'install/*',
        ]);
        // اگر نصب نشده، به نصاب هدایت کن
        $middleware->web(prepend: [
            \App\Http\Middleware\RedirectIfNotInstalled::class,
        ]);
        $middleware->alias([
            'can.do' => \App\Http\Middleware\EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();