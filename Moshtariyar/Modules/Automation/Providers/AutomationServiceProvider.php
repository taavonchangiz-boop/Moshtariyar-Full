<?php

namespace Modules\Automation\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\WooBridge\Events\OrderSynced;
use Modules\Automation\Services\WorkflowEngine;

class AutomationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // اتصال موتور Automation به رویداد همگام‌سازی سفارش
        Event::listen(OrderSynced::class, function (OrderSynced $event) {
            app(WorkflowEngine::class)->onOrderSynced($event->order, $event->wooPayload);
        });
    }
}
