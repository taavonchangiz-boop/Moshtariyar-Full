<?php

namespace Modules\WooBridge\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Entities\Order;
use Modules\WooBridge\Services\OutboundSync;

class PushOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $orderId)
    {
    }

    public function handle(OutboundSync $outbound): void
    {
        $order = Order::find($this->orderId);
        if ($order) {
            $outbound->pushOrderStatus($order);
        }
    }
}
