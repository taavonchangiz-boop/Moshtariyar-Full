<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // بایند کردن رویدادهای اصلی سیستم به Workflow Engine
        
        // 1. وقتی مشتری جدید ایجاد می‌شود
        Event::listen('eloquent.created: Modules\Core\Entities\Customer', function ($customer) {
            try {
                if (class_exists(\Modules\Core\Services\WorkflowEngine::class)) {
                    app(\Modules\Core\Services\WorkflowEngine::class)->fireEvent('customer.created', $customer);
                }
            } catch (\Exception $e) {}
        });

        // 2. تغییرات وضعیت سفارش (تکمیل شده)
        Event::listen('eloquent.updated: Modules\Core\Entities\Order', function ($order) {
            if ($order->isDirty('status') && $order->status === 'completed') {
                try {
                    if (class_exists(\Modules\Core\Services\WorkflowEngine::class)) {
                        app(\Modules\Core\Services\WorkflowEngine::class)->fireEvent('order.completed', $order);
                    }
                } catch (\Exception $e) {}
            }
        });

        // 3. باز شدن تیکت جدید
        Event::listen('eloquent.created: Modules\Core\Entities\Ticket', function ($ticket) {
            try {
                if (class_exists(\Modules\Core\Services\WorkflowEngine::class)) {
                    app(\Modules\Core\Services\WorkflowEngine::class)->fireEvent('ticket.opened', $ticket);
                }
            } catch (\Exception $e) {}
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}