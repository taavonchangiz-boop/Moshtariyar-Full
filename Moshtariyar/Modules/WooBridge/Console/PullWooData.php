<?php

namespace Modules\WooBridge\Console;

use Illuminate\Console\Command;
use Modules\WooBridge\Entities\SyncLog;
use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Services\CustomerSync;
use Modules\WooBridge\Services\OrderSync;
use Modules\WooBridge\Services\ProductSync;
use Modules\WooBridge\Support\WooClient;
use Throwable;

class PullWooData extends Command
{
    protected $signature = 'woobridge:pull {--connection= : شناسه اتصال خاص} {--per-page=50 : تعداد دریافت در هر اجرا} {--minutes=20 : بازه اطمینان برای خواندن تغییرات اخیر}';

    protected $description = 'خواندن دوره‌ای اطلاعات ووکامرس و ورود تغییرات به مشتری‌یار';

    public function handle(CustomerSync $customerSync, ProductSync $productSync, OrderSync $orderSync): int
    {
        $perPage = max(1, min(100, (int) $this->option('per-page')));
        $minutes = max(5, min(1440, (int) $this->option('minutes')));
        $connectionId = $this->option('connection');

        $connections = WooConnection::query()
            ->where('is_active', true)
            ->when($connectionId, fn ($query) => $query->where('id', (int) $connectionId))
            ->get();

        if ($connections->isEmpty()) {
            $this->info('اتصال فعالی برای خواندن اطلاعات ووکامرس وجود ندارد.');
            return self::SUCCESS;
        }

        foreach ($connections as $connection) {
            if (! $connection->hasApiCredentials()) {
                $this->warn('اتصال «' . $connection->name . '» کلید خواندن مستقیم ندارد و رد شد.');
                continue;
            }

            $since = $connection->last_sync_at
                ? $connection->last_sync_at->copy()->subMinutes($minutes)
                : now()->subDays(2);

            try {
                $client = new WooClient($connection);

                $customers = $client->getCustomers(1, $perPage, $since);
                $customerCount = 0;
                foreach ($customers as $row) {
                    if (is_array($row)) {
                        $customerSync->upsert($connection->id, $row);
                        $customerCount++;
                    }
                }

                $products = $client->getProducts(1, $perPage, $since);
                $productCount = 0;
                foreach ($products as $row) {
                    if (is_array($row)) {
                        $productSync->upsert($connection->id, $row);
                        $productCount++;
                    }
                }

                $orders = $client->getOrders(1, $perPage, $since);
                $orderCount = 0;
                foreach ($orders as $row) {
                    if (is_array($row)) {
                        $orderSync->upsert($connection->id, $row);
                        $orderCount++;
                    }
                }

                $connection->update(['last_sync_at' => now()]);

                SyncLog::create([
                    'connection_id' => $connection->id,
                    'entity' => 'order',
                    'direction' => 'in',
                    'status' => 'success',
                    'payload' => [
                        'message' => 'همگام‌سازی خودکار ووکامرس انجام شد.',
                        'customers_count' => $customerCount,
                        'products_count' => $productCount,
                        'orders_count' => $orderCount,
                        'since' => $since->toDateTimeString(),
                    ],
                ]);

                $this->info('اتصال «' . $connection->name . '»: ' . $customerCount . ' مشتری، ' . $productCount . ' کالا، ' . $orderCount . ' سفارش خوانده شد.');
            } catch (Throwable $exception) {
                SyncLog::create([
                    'connection_id' => $connection->id,
                    'entity' => 'order',
                    'direction' => 'in',
                    'status' => 'failed',
                    'payload' => [
                        'message' => 'همگام‌سازی خودکار ووکامرس ناموفق بود.',
                        'since' => $since->toDateTimeString(),
                    ],
                    'error' => $exception->getMessage(),
                ]);

                $this->error('اتصال «' . $connection->name . '» ناموفق بود: ' . $exception->getMessage());
            }
        }

        return self::SUCCESS;
    }
}