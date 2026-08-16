<?php

namespace Modules\WooBridge\Services;

use Modules\Core\Entities\Order;
use Modules\Core\Entities\Product;
use Modules\WooBridge\Entities\IdMap;
use Modules\WooBridge\Entities\SyncLog;
use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Support\WooClient;
use Modules\WooBridge\Support\SyncGuard;

/**
 * همگام‌سازی خروجی از مشتری‌یار به ووکامرس.
 */
class OutboundSync
{
    private function toWooStatus(string $crmStatus): string
    {
        return match ($crmStatus) {
            'completed' => 'completed',
            'processing' => 'processing',
            'on_hold' => 'on-hold',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed',
            default => 'pending',
        };
    }

    private function toWooProductStatus(?string $status, bool $isActive): string
    {
        if (! $isActive) {
            return 'draft';
        }

        return match ($status) {
            'publish' => 'publish',
            'draft' => 'draft',
            'pending' => 'pending',
            'private' => 'private',
            default => 'publish',
        };
    }

    private function toWooProductType(?string $type): string
    {
        return match ($type) {
            'simple' => 'simple',
            'variable' => 'variable',
            'grouped' => 'grouped',
            'external' => 'external',
            default => 'simple',
        };
    }

    private function cleanHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        return $html;
    }

    private function buildProductPayload(Product $product): array
    {
        $payload = [
            'name' => $product->name,
            'type' => $this->toWooProductType($product->product_type),
            'status' => $this->toWooProductStatus($product->status, (bool) $product->is_active),
            'description' => $this->cleanHtml($product->description),
            'short_description' => $this->cleanHtml($product->short_description),
            'sku' => (string) ($product->sku ?? ''),
            'regular_price' => (string) max(0, (float) ($product->regular_price ?: $product->price ?: 0)),
            'sale_price' => $product->sale_price !== null && (float) $product->sale_price > 0 ? (string) (float) $product->sale_price : '',
            'manage_stock' => (bool) $product->manage_stock,
            'stock_quantity' => $product->stock !== null ? (int) $product->stock : null,
            'stock_status' => $product->stock_status ?: 'instock',
        ];

        if ($product->slug) {
            $payload['slug'] = $product->slug;
        }

        if ($payload['type'] === 'external' && $product->external_url) {
            $payload['external_url'] = $product->external_url;
        }

        if (! $payload['manage_stock']) {
            unset($payload['stock_quantity']);
        }

        return $payload;
    }

    public function pushOrderStatus(Order $order): void
    {
        $maps = IdMap::where('entity', 'order')->where('crm_id', $order->id)->get();

        foreach ($maps as $map) {
            $connection = WooConnection::find($map->connection_id);
            if (! $connection || ! $connection->is_active || ! $connection->hasApiCredentials()) {
                continue;
            }

            $log = SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'order',
                'woo_id' => $map->woo_id,
                'direction' => 'out',
                'status' => 'pending',
                'payload' => [
                    'status' => $order->status,
                    'order_number' => $order->number,
                ],
            ]);

            try {
                (new WooClient($connection))->updateOrderStatus(
                    $map->woo_id,
                    $this->toWooStatus($order->status)
                );
                $log->update(['status' => 'success', 'error' => null]);
                $connection->update(['last_sync_at' => now()]);
            } catch (\Throwable $e) {
                $log->update(['status' => 'failed', 'error' => 'در برگرداندن وضعیت سفارش به ووکامرس خطا پیش آمد: ' . $e->getMessage()]);
            }
        }
    }

    public function pushProduct(Product $product): void
    {
        $maps = IdMap::where('entity', 'product')->where('crm_id', $product->id)->get();

        foreach ($maps as $map) {
            $connection = WooConnection::find($map->connection_id);
            if (! $connection || ! $connection->is_active || ! $connection->hasApiCredentials()) {
                continue;
            }

            $payload = $this->buildProductPayload($product);

            $log = SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'product',
                'woo_id' => $map->woo_id,
                'direction' => 'out',
                'status' => 'pending',
                'payload' => array_merge($payload, [
                    'message' => 'برگرداندن اطلاعات محصول از مشتری‌یار به فروشگاه',
                ]),
            ]);

            try {
                $response = (new WooClient($connection))->updateProduct($map->woo_id, $payload);

                SyncGuard::muted(function () use ($product, $response) {
                    $meta = is_array($product->meta) ? $product->meta : [];
                    $meta['woocommerce_last_push_at'] = now()->toDateTimeString();
                    $meta['woocommerce_last_push_status'] = 'success';
                    if (is_array($response) && $response) {
                        $meta['woocommerce']['raw'] = $response;
                    }
                    $product->forceFill(['meta' => $meta])->save();
                });

                $log->update(['status' => 'success', 'error' => null]);
                $connection->update(['last_sync_at' => now()]);
            } catch (\Throwable $e) {
                SyncGuard::muted(function () use ($product, $e) {
                    $meta = is_array($product->meta) ? $product->meta : [];
                    $meta['woocommerce_last_push_at'] = now()->toDateTimeString();
                    $meta['woocommerce_last_push_status'] = 'failed';
                    $meta['woocommerce_last_push_error'] = $e->getMessage();
                    $product->forceFill(['meta' => $meta])->save();
                });

                $log->update(['status' => 'failed', 'error' => 'در برگرداندن اطلاعات محصول به ووکامرس خطا پیش آمد: ' . $e->getMessage()]);
            }
        }
    }

    public function pushProductStock(Product $product): void
    {
        $maps = IdMap::where('entity', 'product')->where('crm_id', $product->id)->get();

        foreach ($maps as $map) {
            $connection = WooConnection::find($map->connection_id);
            if (! $connection || ! $connection->is_active || ! $connection->hasApiCredentials()) {
                continue;
            }

            $log = SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'product',
                'woo_id' => $map->woo_id,
                'direction' => 'out',
                'status' => 'pending',
                'payload' => [
                    'stock' => $product->stock,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ],
            ]);

            try {
                (new WooClient($connection))->updateProductStock($map->woo_id, (int) $product->stock);
                $log->update(['status' => 'success', 'error' => null]);
                $connection->update(['last_sync_at' => now()]);
            } catch (\Throwable $e) {
                $log->update(['status' => 'failed', 'error' => 'در برگرداندن موجودی کالا به ووکامرس خطا پیش آمد: ' . $e->getMessage()]);
            }
        }
    }
}