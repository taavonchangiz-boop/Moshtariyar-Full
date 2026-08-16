<?php

namespace Modules\WooBridge\Console;

use Illuminate\Console\Command;
use Modules\WooBridge\Entities\AbandonedCart;

class RecoverCarts extends Command
{
    protected $signature = 'woobridge:recover-carts';
    protected $description = 'پیگیری سبدهای خرید رهاشده و ارسال پیامک/ایمیل';

    public function handle(): int
    {
        // سبدهایی که حداقل ۱ ساعت از رهاشدنشان گذشته و هنوز اطلاع‌رسانی نشده‌اند
        $carts = AbandonedCart::where('recovered', false)
            ->where('notified', false)
            ->where('created_at', '<=', now()->subHour())
            ->limit(100)
            ->get();

        foreach ($carts as $cart) {
            // در گام Automation: اینجا پیامک/ایمیل واقعی ارسال می‌شود
            // app(\Modules\IranPack\Contracts\SmsProvider::class)->send($cart->phone, '...');
            $cart->update(['notified' => true]);
        }

        $this->info("تعداد {$carts->count()} سبد رهاشده پیگیری شد.");
        return self::SUCCESS;
    }
}
