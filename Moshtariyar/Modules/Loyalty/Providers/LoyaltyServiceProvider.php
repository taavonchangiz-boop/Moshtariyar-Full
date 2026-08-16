<?php

namespace Modules\Loyalty\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\WooBridge\Events\OrderSynced;
use Modules\Loyalty\Services\LoyaltyService;

class LoyaltyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'loyalty');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // هنگام نهایی شدن سفارش، باشگاه مشتریان امتیاز/کش‌بک می‌دهد
        Event::listen(OrderSynced::class, function (OrderSynced $event) {
            if (in_array($event->order->status, ['completed', 'processing'], true)) {
                try {
                    app(LoyaltyService::class)->onPurchase($event->order);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
    }
}
