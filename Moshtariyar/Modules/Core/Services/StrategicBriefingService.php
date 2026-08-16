<?php

namespace Modules\Core\Services;

use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Modules\Core\Support\Num;
use Modules\Core\Support\Money;

class StrategicBriefingService
{
    protected Customer360Service $customer360;

    public function __construct(Customer360Service $customer360)
    {
        $this->customer360 = $customer360;
    }

    /**
     * تولید گزارش استراتژیک روزانه
     */
    public function generateDailyBriefing(): array
    {
        $date = Carbon::now()->format('Y-m-d');
        
        $churnRiskCustomers = $this->analyzeChurnRisk();
        $growthOpportunities = $this->analyzeGrowthOpportunities();
        $businessHealth = $this->analyzeBusinessHealth();

        $briefing = "📅 **گزارش استراتژیک هوشمند - {$date}**\n\n";
        
        $briefing .= "🚨 **هشدار ریزش مشتریان (Churn Risk):**\n";
        if ($churnRiskCustomers['count'] > 0) {
            $briefing .= "• " . Num::fa($churnRiskCustomers['count']) . " مشتری VIP در وضعیت بحرانی هستند و احتمال ریزش دارند.\n";
            $briefing .= "• پیشنهاد: ارسال کد تخفیف ویژه 'بازگشت' برای این گروه.\n";
        } else {
            $briefing .= "✅ خوشبختانه هیچ مشتری VIP در وضعیت بحرانی نیست.\n";
        }
        $briefing .= "\n";

        $briefing .= "🚀 **فرصت‌های رشد (Growth Opportunities):**\n";
        if ($growthOpportunities['count'] > 0) {
            $briefing .= "• " . Num::fa($growthOpportunities['count']) . " مشتری پتانسیل تبدیل شدن به 'قهرمان' را دارند.\n";
            $briefing .= "• پیشنهاد: ارسال پیام تشویقی برای رسیدن به سطح بعدی وفاداری.\n";
        } else {
            $briefing .= "• در حال حاضر فرصت خاصی برای ارتقای سریع شناسایی نشد.\n";
        }
        $briefing .= "\n";

        $briefing .= "🏥 **سلامت کسب‌وکار (Business Health):**\n";
        $briefing .= "• وضعیت کلی: {$businessHealth['status']}\n";
        $briefing .= "• روند درآمدی: {$businessHealth['trend']}\n";
        $briefing .= "• شاخص رضایت تخمینی: {$businessHealth['score']}%" . "\n";

        return [
            'date' => $date,
            'content' => $briefing,
            'metrics' => [
                'churn_count' => $churnRiskCustomers['count'],
                'growth_count' => $growthOpportunities['count'],
                'health_score' => $businessHealth['score'],
            ]
        ];
    }

    protected function analyzeChurnRisk(): array
    {
        // مشتریانی که در 60 روز اخیر خرید نکردند اما قبلاً پرخرید بودند
        $count = Customer::whereHas('orders', function($q) {
            $q->where('status', 'completed');
        })->where('id', '!=', 0) // dummy
        ->get()
        ->filter(fn($c) => $c->getDaysSinceLastPurchase() > 60 && $c->completed_orders_count >= 3)
        ->count();

        return ['count' => $count];
    }

    protected function analyzeGrowthOpportunities(): array
    {
        // مشتریانی که در 30 روز اخیر خرید کردند و در آستانه تبدیل به VIP هستند
        $count = Customer::whereHas('orders', function($q) {
            $q->where('status', 'completed');
        })->get()
        ->filter(fn($c) => $c->getDaysSinceLastPurchase() <= 30 && $c->completed_orders_count >= 2 && $c->completed_orders_count < 5)
        ->count();

        return ['count' => $count];
    }

    protected function analyzeBusinessHealth(): array
    {
        $todayRevenue = Order::whereDate('placed_at', Carbon::today())->where('status', 'completed')->sum('total');
        $yesterdayRevenue = Order::whereDate('placed_at', Carbon::yesterday())->where('status', 'completed')->sum('total');

        $status = 'پایدار';
        $trend = 'ثابت';
        $score = 70;

        if ($todayRevenue > $yesterdayRevenue * 1.2) {
            $status = 'رو به رشد';
            $trend = 'صعودی 📈';
            $score = 90;
        } elseif ($todayRevenue < $yesterdayRevenue * 0.8) {
            $status = 'نیازمند توجه';
            $trend = 'نزولی 📉';
            $score = 50;
        }

        return [
            'status' => $status,
            'trend' => $trend,
            'score' => $score,
        ];
    }
}