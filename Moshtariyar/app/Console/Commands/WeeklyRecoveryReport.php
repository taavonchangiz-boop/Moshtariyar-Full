<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Modules\Core\Support\Jalali;
use Modules\Core\Support\Num;
use App\Mail\RecoveryWeeklyMail;

class WeeklyRecoveryReport extends Command
{
    protected $signature = 'recovery:weekly-report {--email= : ارسال به ایمیل خاص برای تست}';
    protected $description = 'گزارش هفتگی خودکار درآمد بازگشتی و بازگشت مشتریان با گراف ماهانه شمسی داخل ایمیل - هر شنبه ساعت ۹ صبح';

    public function handle(): int
    {
        try {
            $this->info('شروع تولید گزارش هفتگی بازگشت و حفظ با گراف ماهانه شمسی...');

            $businessId = null;
            try {
                $firstBusiness = DB::table('businesses')->first();
                $businessId = $firstBusiness?->id;
            } catch (\Throwable $e) {}

            $analysis = [];
            $recovery = [
                'recovered_30d' => 0,
                'recovered_7d' => 0,
                'recovered_revenue_30d' => 0,
                'recovery_rate' => 0,
                'recovered_list' => [],
            ];

            try {
                if (class_exists(\Modules\Core\Services\ClvAdvancedService::class)) {
                    $clvService = app(\Modules\Core\Services\ClvAdvancedService::class);
                    $analysis = $clvService->analyzeAll($businessId, 300);
                }
                if (class_exists(\Modules\Core\Services\RetentionAutomationService::class)) {
                    $retService = app(\Modules\Core\Services\RetentionAutomationService::class);
                    $recovery = $retService->getRecoveryMetrics($analysis);
                }
            } catch (\Throwable $e) {
                $this->warn('تحلیل CLV با خطا: ' . $e->getMessage());
            }

            $from = now()->subDays(7)->startOfDay();
            $to = now()->endOfDay();

            $weeklyRecovered = [];
            $weeklyRevenue = 0;

            try {
                $ordersLastWeek = DB::table('orders')
                    ->whereIn('status', ['completed','processing'])
                    ->whereBetween('placed_at', [$from, $to])
                    ->whereNotNull('customer_id')
                    ->get();

                foreach ($ordersLastWeek as $order) {
                    try {
                        $prev = DB::table('orders')
                            ->where('customer_id', $order->customer_id)
                            ->whereIn('status', ['completed','processing'])
                            ->where('id','!=',$order->id)
                            ->where('placed_at','<',$order->placed_at)
                            ->orderByDesc('placed_at')
                            ->first();
                        if (!$prev) continue;
                        $gap = Carbon::parse($order->placed_at)->diffInDays(Carbon::parse($prev->placed_at));
                        if ($gap >= 45) {
                            $weeklyRecovered[] = $order;
                            $weeklyRevenue += (float)$order->total;
                        }
                    } catch (\Throwable $e) { continue; }
                }
            } catch (\Throwable $e) {}

            // تاریخ شمسی
            try {
                $jToday = Jalali::date(Carbon::now());
                $jFrom = Jalali::date($from);
                $jTo = Jalali::date($to);
            } catch (\Throwable $e) {
                $jToday = Carbon::now()->format('Y/m/d');
                $jFrom = $from->format('Y/m/d');
                $jTo = $to->format('Y/m/d');
            }

            // محاسبه ماهانه شمسی یونیک برای ایمیل - ۱۲ ماه اخیر
            $monthlyRecovered = []; $monthlyLabels = []; $monthlyCount = [];
            $monthNames = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];

            try {
                $nowJalali = Jalali::toJalali((int)now()->format('Y'), (int)now()->format('n'), (int)now()->format('j'));
                $curJy = $nowJalali[0]; $curJm = $nowJalali[1];
            } catch (\Throwable $e) { $curJy = 1404; $curJm = 5; }

            $jalaliMonths = [];
            for ($k = 11; $k >= 0; $k--) {
                $jy = $curJy; $jm = $curJm - $k;
                while ($jm <= 0) { $jm += 12; $jy--; }
                while ($jm > 12) { $jm -= 12; $jy++; }
                $jalaliMonths[] = ['jy' => $jy, 'jm' => $jm];
            }

            foreach ($jalaliMonths as $jmInfo) {
                $jy = $jmInfo['jy']; $jm = $jmInfo['jm'];
                try {
                    $label = $monthNames[$jm-1] . ' ' . Num::fa($jy);
                } catch (\Throwable $e) {
                    $label = $monthNames[$jm-1] ?? 'ماه';
                }
                $monthlyLabels[] = $label;

                try {
                    [$gy1, $gm1, $gd1] = Jalali::toGregorian($jy, $jm, 1);
                    $start = Carbon::createFromDate($gy1, $gm1, $gd1)->startOfDay();
                    $nextJy = $jy; $nextJm = $jm + 1;
                    if ($nextJm > 12) { $nextJm = 1; $nextJy++; }
                    [$gy2, $gm2, $gd2] = Jalali::toGregorian($nextJy, $nextJm, 1);
                    $end = Carbon::createFromDate($gy2, $gm2, $gd2)->subDay()->endOfDay();

                    $ordersThisMonth = DB::table('orders')
                        ->whereIn('status', ['completed','processing'])
                        ->whereBetween('placed_at', [$start, $end])
                        ->whereNotNull('customer_id')
                        ->when($businessId && Schema::hasColumn('orders','business_id'), fn($q) => $q->where('business_id', $businessId))
                        ->get();

                    $recoveredCount = 0; $recoveredSum = 0;
                    foreach ($ordersThisMonth as $order) {
                        try {
                            $prev = DB::table('orders')
                                ->where('customer_id', $order->customer_id)
                                ->whereIn('status', ['completed','processing'])
                                ->where('id','!=',$order->id)
                                ->where('placed_at','<',$order->placed_at)
                                ->orderByDesc('placed_at')
                                ->first();
                            if (!$prev) continue;
                            $gap = Carbon::parse($order->placed_at)->diffInDays(Carbon::parse($prev->placed_at));
                            if ($gap >= 45) { $recoveredCount++; $recoveredSum += (float)$order->total; }
                        } catch (\Throwable $e) { continue; }
                    }
                    $monthlyCount[] = $recoveredCount;
                    $monthlyRecovered[] = (int)$recoveredSum;
                } catch (\Throwable $e) {
                    $monthlyCount[] = 0;
                    $monthlyRecovered[] = 0;
                }
            }

            $totalRecovered12m = array_sum($monthlyRecovered);

            $countWeekly = count($weeklyRecovered);
            $count30d = $recovery['recovered_30d'] ?? 0;
            $revenue30d = $recovery['recovered_revenue_30d'] ?? 0;
            $rate = $recovery['recovery_rate'] ?? 0;
            $totalAtRisk = $analysis['total_at_risk'] ?? 0;

            // داده کامل برای ایمیل
            $reportData = [
                'recovered_7d' => $countWeekly,
                'recovered_revenue_7d' => $weeklyRevenue,
                'recovered_30d' => $count30d,
                'recovered_revenue_30d' => $revenue30d,
                'recovery_rate' => $rate,
                'total_at_risk' => $totalAtRisk,
                'recovered_list' => $recovery['recovered_list'] ?? [],
                'monthly_labels' => $monthlyLabels,
                'monthly_recovered' => $monthlyRecovered,
                'monthly_count' => $monthlyCount,
                'total_recovered_12m' => $totalRecovered12m,
                'total_count_12m' => array_sum($monthlyCount),
            ];

            $summary = "📊 گزارش هفتگی بازگشت و حفظ - {$jToday}\n\n";
            $summary .= "بازه: {$jFrom} تا {$jTo}\n";
            $summary .= "━━━━━━━━━━━━━━━━━━━━\n";
            $summary .= "🎉 بازگشته‌های این هفته: {$countWeekly} نفر - " . number_format($weeklyRevenue) . " تومان\n";
            $summary .= "📈 ۳۰ روز اخیر: {$count30d} نفر - " . number_format($revenue30d) . " تومان\n";
            $summary .= "💎 نرخ بازگشت: {$rate}٪\n";
            $summary .= "⚠️ در معرض خطر: {$totalAtRisk} نفر\n";
            $summary .= "📅 ۱۲ ماه بازگشتی: " . number_format($totalRecovered12m) . " تومان\n";

            // ثبت در strategic_briefings (سازگاری قدیم)
            try {
                if (Schema::hasTable('strategic_briefings')) {
                    DB::table('strategic_briefings')->insert([
                        'report_date' => now()->toDateString(),
                        'title' => 'گزارش هفتگی بازگشت و حفظ - ' . $jToday,
                        'summary' => $summary,
                        'insights' => json_encode($reportData, JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $this->warn('ثبت briefing ناموفق: ' . $e->getMessage());
            }

            // ثبت در آرشیو جدید حرفه‌ای recovery_report_histories
            $historyId = null;
            try {
                if (Schema::hasTable('recovery_report_histories')) {
                    $historyId = DB::table('recovery_report_histories')->insertGetId([
                        'report_date' => now()->toDateString(),
                        'j_today' => $jToday,
                        'j_from' => $jFrom,
                        'j_to' => $jTo,
                        'business_id' => $businessId,
                        'recovered_7d' => $countWeekly,
                        'recovered_revenue_7d' => (int)$weeklyRevenue,
                        'recovered_30d' => $count30d,
                        'recovered_revenue_30d' => (int)$revenue30d,
                        'recovery_rate' => (int)$rate,
                        'total_at_risk' => (int)$totalAtRisk,
                        'total_recovered_12m' => (int)$totalRecovered12m,
                        'total_count_12m' => (int)array_sum($monthlyCount),
                        'monthly_labels' => json_encode($monthlyLabels, JSON_UNESCAPED_UNICODE),
                        'monthly_recovered' => json_encode($monthlyRecovered, JSON_UNESCAPED_UNICODE),
                        'monthly_count' => json_encode($monthlyCount, JSON_UNESCAPED_UNICODE),
                        'recovered_list' => json_encode($recovery['recovered_list'] ?? [], JSON_UNESCAPED_UNICODE),
                        'emails_sent' => json_encode([], JSON_UNESCAPED_UNICODE),
                        'summary' => $summary,
                        'insights_json' => json_encode($reportData, JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->info('آرشیو گزارش با شناسه ' . $historyId . ' ثبت شد.');
                }
            } catch (\Throwable $e) {
                $this->warn('ثبت در recovery_report_histories ناموفق: ' . $e->getMessage());
            }

            // ایجاد فعالیت برای مدیران
            try {
                if (Schema::hasTable('activities') && Schema::hasTable('users')) {
                    $admins = DB::table('users')->whereIn('role', ['admin','owner'])->orWhere('is_superadmin', 1)->limit(5)->get();
                    foreach ($admins as $admin) {
                        try {
                            DB::table('activities')->insert([
                                'customer_id' => null,
                                'user_id' => $admin->id,
                                'type' => 'note',
                                'title' => '📊 گزارش هفتگی بازگشت و حفظ - ' . $jToday,
                                'body' => $summary,
                                'is_internal' => true,
                                'done' => false,
                                'due_at' => now()->addHours(4),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } catch (\Throwable $e) { continue; }
                    }
                }
            } catch (\Throwable $e) {}

            // ارسال ایمیل به مدیران - با گراف ماهانه شمسی داخل ایمیل
            $sentEmails = [];
            try {
                $emails = [];
                $testEmail = $this->option('email');

                if ($testEmail) {
                    $emails = [$testEmail];
                } else {
                    if (Schema::hasTable('users')) {
                        $adminEmails = DB::table('users')
                            ->where(function($q){
                                $q->whereIn('role', ['admin','owner'])
                                  ->orWhere('is_superadmin', 1);
                            })
                            ->whereNotNull('email')
                            ->where('email', '!=', '')
                            ->pluck('email')
                            ->filter()
                            ->unique()
                            ->take(10)
                            ->toArray();
                        $emails = $adminEmails;
                    }

                    if (empty($emails)) {
                        try {
                            $settingEmail = \Modules\Core\Entities\Setting::get('admin_email', '');
                            if ($settingEmail) $emails = [$settingEmail];
                        } catch (\Throwable $e) {}
                    }

                    // ایمیل اضافی از تنظیمات
                    try {
                        $extra = \Modules\Core\Entities\Setting::get('recovery_report_email', '');
                        if ($extra && filter_var($extra, FILTER_VALIDATE_EMAIL)) {
                            $emails[] = $extra;
                            $emails = array_unique($emails);
                        }
                    } catch (\Throwable $e) {}
                }

                if (!empty($emails)) {
                    foreach ($emails as $email) {
                        try {
                            Mail::to($email)->send(new RecoveryWeeklyMail($reportData, $jToday, $jFrom, $jTo));
                            $sentEmails[] = $email;
                            $this->info('ایمیل گزارش به ' . $email . ' ارسال شد.');
                        } catch (\Throwable $e) {
                            $this->warn('ارسال ایمیل به ' . $email . ' ناموفق: ' . $e->getMessage());
                        }
                    }

                    // بروزرسانی لیست ایمیل‌های ارسالی در آرشیو
                    if ($historyId && !empty($sentEmails) && Schema::hasTable('recovery_report_histories')) {
                        try {
                            DB::table('recovery_report_histories')->where('id', $historyId)->update([
                                'emails_sent' => json_encode($sentEmails, JSON_UNESCAPED_UNICODE),
                                'updated_at' => now(),
                            ]);
                        } catch (\Throwable $e) {}
                    }

                } else {
                    $this->warn('هیچ ایمیل مدیری برای ارسال یافت نشد - گزارش فقط در داشبورد و آرشیو ثبت شد.');
                }

            } catch (\Throwable $e) {
                $this->warn('خطا در ارسال ایمیل: ' . $e->getMessage());
            }

            $this->info($summary);
            $this->info('گزارش هفتگی بازگشت و حفظ با گراف ماهانه شمسی و ایمیل و آرشیو با موفقیت تولید شد.');
            return 0;

        } catch (\Throwable $e) {
            $this->error('خطا در تولید گزارش هفتگی بازگشت و حفظ: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}