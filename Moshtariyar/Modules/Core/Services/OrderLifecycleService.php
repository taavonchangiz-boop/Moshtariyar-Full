<?php

namespace Modules\Core\Services;

use Modules\Core\Entities\Order;
use Modules\Core\Entities\Product;
use Modules\Loyalty\Services\LoyaltyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class OrderLifecycleService
{
    public function sync(Order $order): void
    {
        $order->loadMissing(['items', 'customer']);

        $shouldDeduct = in_array($order->status, ['processing', 'completed'], true);
        $shouldRestore = in_array($order->status, ['cancelled', 'refunded', 'failed'], true);

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $product = Product::find($item->product_id);
            if (! $product) {
                continue;
            }

            $meta = is_array($item->meta) ? $item->meta : [];
            $inventoryState = (string) ($meta['وضعیت_موجودی'] ?? 'ثبت_نشده');
            $qty = max(1, (int) ($item->qty ?? 1));

            if ($shouldDeduct && $inventoryState !== 'کم_شده') {
                $product->applyMovement(
                    'out',
                    $qty,
                    'کسر خودکار بابت سفارش شماره ' . ($order->number ?: $order->id),
                    null
                );

                $meta['وضعیت_موجودی'] = 'کم_شده';
                $meta['زمان_کسر_موجودی'] = now()->toDateTimeString();
                unset($meta['زمان_برگشت_موجودی']);
                $item->update(['meta' => $meta]);
                continue;
            }

            if ($shouldRestore && $inventoryState === 'کم_شده') {
                $product->applyMovement(
                    'in',
                    $qty,
                    'برگشت خودکار بابت سفارش شماره ' . ($order->number ?: $order->id),
                    null
                );

                $meta['وضعیت_موجودی'] = 'برگشت_داده_شد';
                $meta['زمان_برگشت_موجودی'] = now()->toDateTimeString();
                $item->update(['meta' => $meta]);
            }
        }

        if ($order->customer) {
            $loyalty = app(LoyaltyService::class);
            if ($shouldDeduct) {
                $loyalty->onPurchase($order);

                // بررسی بازگشت پس از غیبت طولانی - امتیاز دو برابر
                try {
                    $this->handleReturnReward($order, $loyalty);
                } catch (\Throwable $e) {
                    // لاگ آرام - مانع سفارش نشود
                    \Illuminate\Support\Facades\Log::info('بررسی پاداش بازگشت ناموفق: ' . $e->getMessage());
                }

            } elseif ($shouldRestore) {
                $loyalty->rollbackPurchase($order);
            }
            $order->customer->recalcLifetimeValue();
        }
    }

    /**
     * اگر مشتری بعد از ۴۵ روز غیبت برگشته، امتیاز دو برابر بده
     */
    private function handleReturnReward(Order $order, LoyaltyService $loyalty): void
    {
        if (!$order->customer_id) return;

        // سفارش قبلی همین مشتری قبل از این سفارش
        $prevOrder = Order::where('customer_id', $order->customer_id)
            ->whereIn('status', ['completed', 'processing'])
            ->where('id', '!=', $order->id)
            ->where('placed_at', '<', $order->placed_at ?? now())
            ->orderByDesc('placed_at')
            ->first();

        if (!$prevOrder || !$prevOrder->placed_at) return;

        try {
            $gapDays = Carbon::parse($order->placed_at ?? now())->diffInDays(Carbon::parse($prevOrder->placed_at));
        } catch (\Throwable $e) {
            return;
        }

        if ($gapDays < 45) return;

        // جلوگیری از دادن چندباره برای یک سفارش
        try {
            if (Schema::hasTable('loyalty_transactions')) {
                $exists = DB::table('loyalty_transactions')
                    ->join('loyalty_members', 'loyalty_members.id', '=', 'loyalty_transactions.member_id')
                    ->where('loyalty_members.customer_id', $order->customer_id)
                    ->where('loyalty_transactions.ref_type', 'return_double')
                    ->where('loyalty_transactions.ref_id', $order->id)
                    ->exists();
                if ($exists) return;
            }
        } catch (\Throwable $e) {}

        // امتیاز پایه همین سفارش
        $basePoints = (int) round(((float) $order->total) / 1000);
        if ($basePoints <= 0) $basePoints = 100;

        // برای ۴۵ تا ۶۰ روز: دو برابر (یک برابر اضافه)
        // برای بالای ۶۰ روز: سه برابر (دو برابر اضافه) به عنوان تشویق بیشتر
        $extraPoints = $gapDays >= 60 ? $basePoints * 2 : $basePoints;

        try {
            $member = $loyalty->ensureMember($order->customer);
            $loyalty->addPoints(
                $member,
                $extraPoints,
                'پاداش بازگشت - امتیاز دو برابر بابت بازگشت پس از ' . $gapDays . ' روز غیبت - سفارش شماره ' . ($order->number ?: $order->id),
                'return_double',
                $order->id
            );

            // کیف پول هدیه کوچک برای بازگشت‌های طولانی
            if ($gapDays >= 60 && $extraPoints > 0) {
                $walletGift = $gapDays >= 90 ? 50000 : 20000;
                $loyalty->adjustWallet(
                    $member,
                    $walletGift,
                    'هدیه کیف پول بابت بازگشت پس از ' . $gapDays . ' روز - سفارش ' . ($order->number ?: $order->id),
                    'return_double_wallet',
                    $order->id
                );
            }

            // ایجاد یادداشت پیگیری برای تیم فروش
            try {
                if (Schema::hasTable('activities')) {
                    $order->customer->activities()->create([
                        'type' => 'note',
                        'title' => 'بازگشت خودکار مشتری پس از ' . $gapDays . ' روز',
                        'body' => 'مشتری پس از ' . $gapDays . ' روز غیبت با سفارش شماره ' . ($order->number ?: $order->id) . ' به مبلغ ' . number_format((int)$order->total) . ' تومان برگشت. ' . $extraPoints . ' امتیاز دو برابر به صورت خودکار اضافه شد.',
                        'is_internal' => true,
                    ]);
                }
            } catch (\Throwable $e) {}

            // آتش زدن جریان کاری بازگشت
            try {
                if (class_exists(\Modules\Core\Services\WorkflowEngine::class)) {
                    app(\Modules\Core\Services\WorkflowEngine::class)->fire('customer_returned', $order->customer);
                }
            } catch (\Throwable $e) {}

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('خطا در اعطای پاداش بازگشت: ' . $e->getMessage());
        }
    }
}