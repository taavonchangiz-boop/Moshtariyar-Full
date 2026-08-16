<?php

namespace Modules\WooBridge\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Services\OrderSync;
use Modules\WooBridge\Support\WooClient;

/**
 * import تاریخی سفارش‌ها به‌صورت صفحه‌به‌صفحه.
 * هر صفحه یک Job مستقل است تا روی cPanel از max_execution_time عبور نکنیم.
 */
class BulkImportOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 50; // کمتر از حد متعارف cPanel

    public function __construct(
        public int $connectionId,
        public int $page = 1,
        public int $perPage = 50,
    ) {
    }

    public function handle(OrderSync $orderSync): void
    {
        $connection = WooConnection::find($this->connectionId);
        if (! $connection || ! $connection->is_active) {
            return;
        }

        $client = new WooClient($connection);
        $orders = $client->getOrders($this->page, $this->perPage);

        if (empty($orders)) {
            $connection->update(['last_sync_at' => now()]);
            return; // پایان import
        }

        foreach ($orders as $woo) {
            $orderSync->upsert($connection->id, $woo);
        }

        // اگر صفحه پر بود، صفحهٔ بعد را به صف بسپار
        if (count($orders) === $this->perPage) {
            self::dispatch($this->connectionId, $this->page + 1, $this->perPage);
        } else {
            $connection->update(['last_sync_at' => now()]);
        }
    }
}
