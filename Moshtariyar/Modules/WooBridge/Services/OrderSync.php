<?php

namespace Modules\WooBridge\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Product;
use Modules\Core\Services\OrderLifecycleService;
use Modules\Core\Support\Jalali;
use Modules\Core\Entities\OrderItem;
use Modules\WooBridge\Entities\IdMap;
use Modules\WooBridge\Events\OrderSynced;
use Modules\WooBridge\Support\SyncGuard;

class OrderSync
{
    public function __construct(private CustomerSync $customerSync)
    {
    }

    /**
     * upsert سفارش از payload ووکامرس (idempotent).
     */
    public function upsert(int $connectionId, array $woo): Order
    {
        $wooId = (int) ($woo['id'] ?? 0);

        // مهم: کل نوشتن ورودی را mute می‌کنیم تا observer خروجی فعال نشود (جلوگیری از حلقه)
        return SyncGuard::muted(fn () => DB::transaction(function () use ($connectionId, $woo, $wooId) {
            // مشتری را تضمین می‌کنیم (از روی داده‌های سفارش)
            $customer = $this->customerSync->upsert($connectionId, [
                'id'         => $woo['customer_id'] ?? 0,
                'billing'    => $woo['billing'] ?? [],
                'shipping'   => $woo['shipping'] ?? [],
                'first_name' => $woo['billing']['first_name'] ?? '',
                'last_name'  => $woo['billing']['last_name'] ?? '',
            ]);

            // سفارش موجود؟
            $order = null;
            if ($wooId) {
                $crmId = IdMap::resolveCrmId($connectionId, 'order', $wooId);
                if ($crmId) {
                    $order = Order::find($crmId);
                }
            }
            $order ??= new Order();

            $order->customer_id = $customer->id;
            $order->number   = $woo['number'] ?? ($woo['id'] ?? null);
            $order->status   = $this->mapStatus($woo['status'] ?? 'pending');
            $order->total    = (float) ($woo['total'] ?? 0);
            $order->tax_total = (float) ($woo['total_tax'] ?? 0);
            $order->currency = $woo['currency'] ?? 'IRT';
            $order->source   = 'woocommerce';
            $order->placed_at = $this->parseWooDate($woo['date_created'] ?? ($woo['date_created_gmt'] ?? null));
            $order->save();

            // اقلام (پاک و دوباره‌سازی برای سادگی و سازگاری)
            $order->items()->delete();
            foreach (($woo['line_items'] ?? []) as $item) {
                $product = $this->upsertProductFromLineItem($connectionId, $item);
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product?->id,
                    'name'       => $item['name'] ?? null,
                    'sku'        => $item['sku'] ?? null,
                    'qty'        => (int) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['price'] ?? 0),
                    'line_total' => (float) ($item['total'] ?? 0),
                    'meta'       => $item['meta_data'] ?? null,
                ]);
            }

            if ($wooId) {
                IdMap::link($connectionId, 'order', $wooId, $order->id);
            }

            // به‌روزرسانی انبار و باشگاه مشتریان بر اساس وضعیت سفارش
            app(OrderLifecycleService::class)->sync($order);

            // رویداد داخلی برای موتور Automation
            event(new OrderSynced($order, $woo));

            return $order;
        }));
    }

    private function upsertProductFromLineItem(int $connectionId, array $item): ?Product
    {
        $wooProductId = (int) ($item['product_id'] ?? 0);
        $sku = trim((string) ($item['sku'] ?? ''));
        $name = trim((string) ($item['name'] ?? ''));
        if ($wooProductId <= 0 && $sku === '' && $name === '') return null;

        $product = null;
        if ($wooProductId > 0) {
            $crmId = IdMap::resolveCrmId($connectionId, 'product', $wooProductId);
            if ($crmId) $product = Product::find($crmId);
        }
        if (! $product && $sku !== '') $product = Product::where('sku', $sku)->first();
        if (! $product && $sku === '' && $name !== '') {
            $sameNameCount = Product::where('name', $name)->count();
            if ($sameNameCount === 1) $product = Product::where('name', $name)->first();
        }
        $product ??= new Product();
        $product->name = $name ?: ($product->name ?: 'محصول ووکامرس');
        if ($sku !== '') $product->sku = $sku;
        $product->price = (float) ($item['price'] ?? ($item['total'] ?? 0));
        $product->is_active = true;
        $product->save();
        if ($wooProductId > 0) IdMap::link($connectionId, 'product', $wooProductId, $product->id);
        return $product;
    }

    public function upsertProduct(int $connectionId, array $woo): Product
    {
        $wooId = (int) ($woo['id'] ?? 0);
        $sku = trim((string) ($woo['sku'] ?? ''));
        $name = trim((string) ($woo['name'] ?? 'محصول ووکامرس'));
        $product = null;
        if ($wooId > 0) {
            $crmId = IdMap::resolveCrmId($connectionId, 'product', $wooId);
            if ($crmId) $product = Product::find($crmId);
        }
        if (! $product && $sku !== '') $product = Product::where('sku', $sku)->first();
        if (! $product && $sku === '' && $name !== '') {
            $sameNameCount = Product::where('name', $name)->count();
            if ($sameNameCount === 1) $product = Product::where('name', $name)->first();
        }
        $product ??= new Product();
        $product->name = $name;
        if ($sku !== '') $product->sku = $sku;
        $product->category = $woo['categories'][0]['name'] ?? ($product->category ?? null);
        $product->price = (float) ($woo['price'] ?? $woo['regular_price'] ?? $product->price ?? 0);
        $product->stock = isset($woo['stock_quantity']) ? (int) $woo['stock_quantity'] : $product->stock;
        $product->is_active = (($woo['status'] ?? 'publish') === 'publish');
        $product->save();
        if ($wooId > 0) IdMap::link($connectionId, 'product', $wooId, $product->id);
        return $product;
    }

    /**
     * تاریخ ووکامرس را امن parse می‌کند.
     * بعضی فروشگاه‌های فارسی‌سازی‌شده تاریخ را به‌جای میلادی، شمسی برمی‌گردانند؛
     * مثل 1405-03-21 14:03:15. MySQL این را به‌عنوان میلادی نامعتبر/نامطلوب می‌گیرد،
     * پس اگر سال بین 1200 تا 1600 بود، آن را شمسی فرض و به میلادی تبدیل می‌کنیم.
     */
    private function parseWooDate(?string $value): \Carbon\Carbon
    {
        if (! $value) return now();
        $value = trim($value);
        try {
            if (preg_match('/^(\d{4})\D(\d{1,2})\D(\d{1,2})(?:\D+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/', $value, $m)) {
                $year = (int) $m[1];
                if ($year >= 1200 && $year <= 1600) {
                    $date = Jalali::parse($m[1].'/'.$m[2].'/'.$m[3]) ?? now();
                    return $date->setTime((int)($m[4] ?? 0), (int)($m[5] ?? 0), (int)($m[6] ?? 0));
                }
            }
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return now();
        }
    }

    /** نگاشت وضعیت‌های ووکامرس به وضعیت‌های CRM */
    private function mapStatus(string $wooStatus): string
    {
        return match ($wooStatus) {
            'completed'  => 'completed',
            'processing' => 'processing',
            'on-hold'    => 'on_hold',
            'cancelled'  => 'cancelled',
            'refunded'   => 'refunded',
            'failed'     => 'failed',
            default      => 'pending',
        };
    }
}
