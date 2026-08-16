<?php

namespace Modules\IranPack\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\IranPack\Contracts\PaymentGateway;
use Modules\IranPack\Contracts\SmsProvider;
use Modules\IranPack\Managers\PaymentManager;
use Modules\IranPack\Managers\SmsManager;

class IranPackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // مدیریت درگاه‌ها و پنل‌های پیامک (singleton)
        $this->app->singleton(PaymentManager::class);
        $this->app->singleton(SmsManager::class);

        // درگاه/پنل پیش‌فرض از طریق Manager قابل دریافت است:
        //   app(PaymentGateway::class)  → درگاه پیش‌فرض (زرین‌پال)
        //   app(SmsProvider::class)     → پنل پیش‌فرض (SMS.IR)
        $this->app->bind(PaymentGateway::class, fn ($app) => $app->make(PaymentManager::class)->driver());
        $this->app->bind(SmsProvider::class, fn ($app) => $app->make(SmsManager::class)->driver());
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // مسیرهای وب ماژول (پرداخت) — میدلورها داخل خود فایل تعریف شده‌اند
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\IranPack\Console\InquireTaxInvoices::class,
            ]);
        }
    }
}
