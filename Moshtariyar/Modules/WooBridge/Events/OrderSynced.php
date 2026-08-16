<?php

namespace Modules\WooBridge\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Core\Entities\Order;

class OrderSynced
{
    use Dispatchable;

    public function __construct(public Order $order, public array $wooPayload = [])
    {
    }
}
