<?php

namespace Modules\WooBridge\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Product;
use Modules\WooBridge\Console\PullWooData;
use Modules\WooBridge\Console\RecoverCarts;
use Modules\WooBridge\Console\RetryFailedSync;
use Modules\WooBridge\Observers\OrderObserver;
use Modules\WooBridge\Observers\ProductObserver;

class WooBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // migrationها
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // مسیرهای API ماژول با پیشوند /api و گروه میدلور api
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../Routes/api.php');

        // observerها برای همگام‌سازی خروجی (CRM → ووکامرس)
        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);

        // دستورهای کنسول
        if ($this->app->runningInConsole()) {
            $this->commands([
                PullWooData::class,
                RetryFailedSync::class,
                RecoverCarts::class,
            ]);
        }
    }
}