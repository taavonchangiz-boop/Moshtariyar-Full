<?php

namespace Modules\WooBridge\Observers;

use Modules\Core\Entities\Order;
use Modules\Core\Services\OrderLifecycleService;
use Modules\WooBridge\Jobs\PushOrderStatus;
use Modules\WooBridge\Support\SyncGuard;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (SyncGuard::isMuted()) {
            return;
        }

        if ($order->wasChanged('status')) {
            app(OrderLifecycleService::class)->sync($order);
            PushOrderStatus::dispatch($order->id);
        }
    }
}
