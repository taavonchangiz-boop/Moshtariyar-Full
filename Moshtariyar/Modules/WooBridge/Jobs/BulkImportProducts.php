<?php

namespace Modules\WooBridge\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Services\ProductSync;
use Modules\WooBridge\Support\WooClient;

/**
 * دریافت دسته‌ای کالاها از ووکامرس.
 */
class BulkImportProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 50;

    public function __construct(
        public int $connectionId,
        public int $page = 1,
        public int $perPage = 50,
    ) {
    }

    public function handle(ProductSync $productSync): void
    {
        $connection = WooConnection::find($this->connectionId);
        if (! $connection || ! $connection->is_active || ! $connection->hasApiCredentials()) {
            return;
        }

        $client = new WooClient($connection);
        $rows = $client->getProducts($this->page, $this->perPage);

        if (empty($rows)) {
            $connection->update(['last_sync_at' => now()]);
            return;
        }

        foreach ($rows as $woo) {
            $productSync->upsert($connection->id, $woo);
        }

        if (count($rows) === $this->perPage) {
            self::dispatch($this->connectionId, $this->page + 1, $this->perPage);
        } else {
            $connection->update(['last_sync_at' => now()]);
        }
    }
}
