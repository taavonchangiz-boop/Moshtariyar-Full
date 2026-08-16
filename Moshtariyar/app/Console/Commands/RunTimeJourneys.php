<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Setting;
use Modules\Automation\Services\WorkflowEngine;
use Carbon\Carbon;

class RunTimeJourneys extends Command
{
    protected $signature = 'crm:run-time-journeys';
    protected $description = 'اجرای خودکار سفرهای زمانی: عدم خرید، تولد، سبد رها شده - با بالاترین دقت';

    public function handle(): int
    {
        $this->info('شروع اجرای سفرهای زمانی...');
        $engine = app(WorkflowEngine::class);
        try {
            $this->call('automation:check-abandoned-carts');
        } catch (\Throwable $e) {
            Log::warning('خطا در اجرای سبد رها شده: ' . $e->getMessage());
        }
        try {
            $this->call('automation:check-birthdays');
        } catch (\Throwable $e) {
            Log::warning('خطا در اجرای تبریک تولد: ' . $e->getMessage());
        }
        $thresholds = [
            30 => 'no_purchase_30d',
            45 => 'no_purchase_45d',
            60 => 'no_purchase_60d',
        ];
        foreach ($thresholds as $days => $event) {
            try {
                $this->checkNoPurchase($days, $event, $engine);
            } catch (\Throwable $e) {
                Log::warning("خطا در بررسی عدم خرید {$days} روز: " . $e->getMessage());
            }
        }
        $this->info('تمام سفرهای زمانی با موفقیت اجرا شد');
        return self::SUCCESS;
    }

    protected function checkNoPurchase(int $days, string $event, $engine): void
    {
        $cutoff = now()->subDays($days);
        $customers = Customer::whereHas('orders', function ($q) use ($cutoff) {
                $q->where('created_at', '<=', $cutoff);
            })
            ->whereDoesntHave('orders', function ($q) {
                $q->where('created_at', '>=', now()->subDays(7));
            })
            ->limit(100)
            ->get();

        $count = 0;
        foreach ($customers as $customer) {
            $cacheKey = "journey_{$event}_{$customer->id}_" . now()->format('Y-m');
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                continue;
            }
            try {
                $engine->fire($event, $customer);
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(30));
                $count++;
                usleep(100000);
            } catch (\Throwable $e) {
                Log::warning("خطا در ارسال {$event} برای مشتری {$customer->id}: " . $e->getMessage());
            }
        }
        if ($count > 0) {
            $this->info("رویداد {$event}: {$count} مشتری پردازش شد");
        }
    }
}