<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Setting;
use Modules\Automation\Services\WorkflowEngine;
use Modules\WooBridge\Entities\AbandonedCart;
use Carbon\Carbon;

class CheckAbandonedCarts extends Command
{
    protected $signature = 'automation:check-abandoned-carts';
    protected $description = 'بررسی سبدهای رها شده و ارسال اعلان خودکار با بالاترین دقت';

    public function handle(): int
    {
        if (Setting::get('notif_abandoned_cart', '0') === '0') {
            $this->info('اعلان سبد رها شده غیرفعال است');
            return self::SUCCESS;
        }

        $engine = app(WorkflowEngine::class);
        $count = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('wb_abandoned_carts')) {
                $carts = AbandonedCart::where('recovered', false)
                    ->where('notified', false)
                    ->where('created_at', '<=', now()->subHours(24))
                    ->limit(50)
                    ->get();

                foreach ($carts as $cart) {
                    try {
                        $customer = null;
                        if ($cart->phone) {
                            $customer = Customer::where('phone', $cart->phone)->first();
                        }
                        if (!$customer && $cart->email) {
                            $customer = Customer::where('email', $cart->email)->first();
                        }
                        if (!$customer && $cart->phone) {
                            $customer = new Customer([
                                'full_name' => 'مشتری سبد رها شده',
                                'phone' => $cart->phone,
                                'email' => $cart->email,
                            ]);
                            $customer->id = 0;
                        }

                        if ($customer) {
                            $engine->fire('cart.abandoned', $customer, [
                                'cart_value' => $cart->value,
                                'cart_items' => $cart->cart,
                            ]);
                            $cart->update(['notified' => true]);
                            $count++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('خطا در پردازش سبد رها شده: ' . $e->getMessage());
                    }
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('orders')) {
                $pendingOrders = \Modules\Core\Entities\Order::where('status', 'pending')
                    ->where('created_at', '<=', now()->subHours(24))
                    ->where('created_at', '>=', now()->subDays(7))
                    ->with('customer')
                    ->limit(50)
                    ->get();

                foreach ($pendingOrders as $order) {
                    if (!$order->customer) continue;
                    try {
                        $engine->fire('cart.abandoned', $order, [
                            'order_id' => $order->id,
                        ]);
                        $count++;
                    } catch (\Throwable $e) {
                        Log::warning('خطا در سبد محلی: ' . $e->getMessage());
                    }
                }
            }

            $this->info("تعداد {$count} سبد رها شده پردازش شد");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('خطا: ' . $e->getMessage());
            Log::error('CheckAbandonedCarts error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}