<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Modules\Core\Entities\Setting;

class RecoveryReportCheck extends Command
{
    protected $signature = 'recovery:check-schedule';
    protected $description = 'بررسی زمان‌بندی گزارش نجات بر اساس تنظیمات مدیر - هر ساعت اجرا شود';

    public function handle(): int
    {
        try {
            $enabled = Setting::get('recovery_report_enabled', '1');
            if ($enabled !== '1') {
                $this->info('گزارش نجات غیرفعال است.');
                return 0;
            }

            $time = Setting::get('recovery_report_time', '09:00');
            $eveningEnabled = Setting::get('recovery_report_evening_enabled', '0');
            $eveningTime = Setting::get('recovery_report_evening_time', '21:00');
            $daysJson = Setting::get('recovery_report_days', '["6"]');
            $days = json_decode($daysJson, true);
            if (!is_array($days)) $days = [6];

            try {
                $now = Carbon::now('Asia/Tehran');
            } catch (\Throwable $e) {
                $now = Carbon::now();
            }

            $currentHourMinute = $now->format('H:i');
            $currentDayOfWeek = (int) $now->format('w');

            $shouldSend = false;
            $reason = '';

            if (!in_array($currentDayOfWeek, $days)) {
                $this->info('امروز (' . $currentDayOfWeek . ') در روزهای انتخابی نیست. روزهای انتخابی: ' . implode(',', $days));
                return 0;
            }

            $checkTimes = [$time];
            if ($eveningEnabled === '1' && $eveningTime) {
                $checkTimes[] = $eveningTime;
            }

            foreach ($checkTimes as $configuredTime) {
                $configuredTime = trim($configuredTime);
                if (!$configuredTime) continue;

                try {
                    $cfg = Carbon::createFromFormat('H:i', $configuredTime, 'Asia/Tehran');
                    $diffMinutes = abs($now->diffInMinutes($cfg, false));
                    if ($diffMinutes <= 30 && $now->format('H') === $cfg->format('H')) {
                        $cacheKey = 'recovery_report_sent_' . $now->format('Ymd') . '_' . str_replace(':', '', $configuredTime);
                        try {
                            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                                $this->info('گزارش برای امروز و ساعت ' . $configuredTime . ' قبلاً ارسال شده - کش موجود.');
                                continue;
                            }
                        } catch (\Throwable $e) {}

                        $shouldSend = true;
                        $reason = "ساعت تنظیم شده {$configuredTime} با ساعت فعلی {$currentHourMinute} مطابقت دارد - روز هفته {$currentDayOfWeek}";
                        break;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            if (!$shouldSend) {
                $this->info('الان زمان ارسال نیست. ساعت فعلی: ' . $currentHourMinute . ' - ساعت‌های تنظیم: ' . implode(', ', $checkTimes));
                return 0;
            }

            $this->info('زمان ارسال فرا رسیده: ' . $reason . ' - در حال اجرای گزارش...');

            $this->call('recovery:weekly-report');

            try {
                $cacheKey = 'recovery_report_sent_' . $now->format('Ymd') . '_' . str_replace(':', '', $configuredTime);
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, 60*60*25);
            } catch (\Throwable $e) {}

            $this->info('بررسی زمان‌بندی با موفقیت انجام شد و گزارش ارسال شد.');
            return 0;

        } catch (\Throwable $e) {
            $this->error('خطا در بررسی زمان‌بندی: ' . $e->getMessage());
            return 1;
        }
    }
}