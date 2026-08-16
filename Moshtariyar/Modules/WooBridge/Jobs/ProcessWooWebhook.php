<?php

namespace Modules\WooBridge\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WooBridge\Entities\SyncLog;
use Modules\WooBridge\Services\CustomerSync;
use Modules\WooBridge\Services\OrderSync;
use Modules\WooBridge\Services\ProductSync;
use Throwable;

class ProcessWooWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // ثانیه

    public function __construct(public int $syncLogId)
    {
    }

    public function handle(CustomerSync $customers, OrderSync $orders, ProductSync $products): void
    {
        $log = SyncLog::find($this->syncLogId);
        if (! $log || $log->status === 'success') {
            return;
        }

        try {
            $payload = $log->payload ?? [];

            match ($log->entity) {
                'customer' => $customers->upsert($log->connection_id, $payload),
                'product'  => $products->upsert($log->connection_id, $payload),
                default    => $orders->upsert($log->connection_id, $payload),
            };

            $log->update(['status' => 'success', 'error' => null]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e; // اجازهٔ retry به صف
        }
    }
}
