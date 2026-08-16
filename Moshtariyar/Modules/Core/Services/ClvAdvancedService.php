<?php

namespace Modules\Core\Services;

use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Segment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * سرویس پیشرفته ارزش عمر مشتری، پیش‌بینی ریزش و بخش‌بندی خودکار
 * با بالاترین دقت و قدرت
 */
class ClvAdvancedService
{
    public const MARGIN = 0.30; // حاشیه سود پیش‌فرض ۳۰٪ برای محاسبه CLV خالص

    /**
     * تحلیل پیشرفته یک مشتری
     */
    public function analyzeCustomer(Customer $customer): array
    {
        $orders = $customer->orders()
            ->whereIn('status', ['processing', 'completed'])
            ->orderBy('placed_at')
            ->get();

        $count = $orders->count();
        if ($count === 0) {
            return [
                'customer_id' => $customer->id,
                'full_name' => $customer->full_name ?: 'بدون نام',
                'phone' => $customer->phone ?: '---',
                'total_spent' => 0,
                'order_count' => 0,
                'avg_order' => 0,
                'first_order' => null,
                'last_order' => null,
                'lifespan_days' => 0,
                'frequency_monthly' => 0,
                'avg_interval_days' => 45,
                'recency_days' => 9999,
                'churn_ratio' => 999,
                'churn_probability' => 95,
                'churn_label' => 'بدون خرید',
                'churn_color' => '#9ca3af',
                'predicted_clv_12m' => 0,
                'predicted_next_purchase' => null,
                'segment_key' => 'lost',
                'segment_label' => 'بدون خرید',
            ];
        }

        $total = (float) $orders->sum('total');
        $avg = $count > 0 ? $total / $count : 0;

        $first = Carbon::parse($orders->first()->placed_at);
        $last = Carbon::parse($orders->last()->placed_at);
        $lifespanDays = max(30, $first->diffInDays($last));
        $lifespanMonths = max(1, $lifespanDays / 30.44);

        $frequencyMonthly = $count / $lifespanMonths;
        $avgIntervalDays = $count > 1 ? $lifespanDays / ($count - 1) : 45;

        $recencyDays = now()->diffInDays($last);
        $churnRatio = $avgIntervalDays > 0 ? $recencyDays / $avgIntervalDays : 999;

        // محاسبه احتمال ریزش با فرمول هوشمند
        if ($churnRatio < 0.7) {
            $churnProb = max(5, min(20, $churnRatio * 18));
            $churnLabel = 'سالم و وفادار';
            $churnColor = '#10b981';
            $segmentKey = 'champions';
            $segmentLabel = 'قهرمانان وفادار';
        } elseif ($churnRatio < 1.1) {
            $churnProb = max(21, min(40, 20 + ($churnRatio - 0.7) * 50));
            $churnLabel = 'پایدار';
            $churnColor = '#3b82f6';
            $segmentKey = 'loyal';
            $segmentLabel = 'وفاداران';
        } elseif ($churnRatio < 1.6) {
            $churnProb = max(41, min(65, 40 + ($churnRatio - 1.1) * 50));
            $churnLabel = 'در معرض خطر';
            $churnColor = '#f59e0b';
            $segmentKey = 'at_risk';
            $segmentLabel = 'در معرض خطر';
        } elseif ($churnRatio < 2.5) {
            $churnProb = max(66, min(85, 65 + ($churnRatio - 1.6) * 22));
            $churnLabel = 'در حال ریزش';
            $churnColor = '#ef4444';
            $segmentKey = 'churning';
            $segmentLabel = 'در حال ریزش';
        } else {
            $churnProb = max(86, min(95, 85 + ($churnRatio - 2.5) * 5));
            $churnLabel = 'از دست رفته';
            $churnColor = '#991b1b';
            $segmentKey = 'lost';
            $segmentLabel = 'از دست رفته';
        }

        // اصلاح بر اساس ارزش: اگر CLV بالا باشد، ریسک را کمی کاهش بده اما برچسب ارزشمند در خطر بده
        $predictedClv12m = $avg * $frequencyMonthly * 12 * self::MARGIN;

        if ($predictedClv12m > 2000000 && $churnRatio >= 1.3 && $churnRatio < 2.5) {
            $segmentKey = 'valuable_at_risk';
            $segmentLabel = 'ارزشمند در معرض ریزش';
            $churnColor = '#d97706';
        }

        if ($count === 1 && $recencyDays <= 30) {
            $segmentKey = 'new_potential';
            $segmentLabel = 'جدید با پتانسیل';
            $churnLabel = 'تازه وارد';
            $churnColor = '#8b5cf6';
            $churnProb = 15;
        }

        $nextPurchase = $last->copy()->addDays(max(7, (int) round($avgIntervalDays)));

        return [
            'customer_id' => $customer->id,
            'full_name' => $customer->full_name ?: 'بدون نام',
            'phone' => $customer->phone ?: '---',
            'total_spent' => (int) $total,
            'order_count' => $count,
            'avg_order' => (int) $avg,
            'first_order' => $first,
            'last_order' => $last,
            'lifespan_days' => (int) $lifespanDays,
            'frequency_monthly' => round($frequencyMonthly, 2),
            'avg_interval_days' => (int) round($avgIntervalDays),
            'recency_days' => (int) $recencyDays,
            'churn_ratio' => round($churnRatio, 2),
            'churn_probability' => (int) round($churnProb),
            'churn_label' => $churnLabel,
            'churn_color' => $churnColor,
            'predicted_clv_12m' => (int) round($predictedClv12m),
            'predicted_next_purchase' => $nextPurchase,
            'segment_key' => $segmentKey,
            'segment_label' => $segmentLabel,
        ];
    }

    /**
     * تحلیل کل کسب‌وکار
     */
    public function analyzeAll(?int $businessId = null, int $limit = 500): array
    {
        $query = Customer::query()
            ->with(['orders' => function ($q) {
                $q->whereIn('status', ['processing', 'completed'])->orderBy('placed_at');
            }]);

        if ($businessId && Schema::hasColumn('customers', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        $query->whereHas('orders', function ($q) {
            $q->whereIn('status', ['processing', 'completed']);
        });

        $customers = $query->limit($limit)->get();

        $analyses = [];
        $totalClv = 0;
        $totalChurnProb = 0;
        $totalAtRisk = 0;
        $totalChurned = 0;
        $totalPredictedRevenue = 0;
        $churnDistribution = [
            'healthy' => 0,
            'stable' => 0,
            'at_risk' => 0,
            'churning' => 0,
            'lost' => 0,
        ];
        $segmentDistribution = [];

        foreach ($customers as $customer) {
            $a = $this->analyzeCustomer($customer);
            $analyses[] = $a;

            $totalClv += $a['predicted_clv_12m'];
            $totalChurnProb += $a['churn_probability'];
            $totalPredictedRevenue += $a['predicted_clv_12m'];

            if ($a['churn_probability'] >= 66) $totalChurned++;
            if ($a['churn_probability'] >= 41 && $a['churn_probability'] < 66) $totalAtRisk++;

            // توزیع ریزش
            if ($a['churn_probability'] <= 20) $churnDistribution['healthy']++;
            elseif ($a['churn_probability'] <= 40) $churnDistribution['stable']++;
            elseif ($a['churn_probability'] <= 65) $churnDistribution['at_risk']++;
            elseif ($a['churn_probability'] <= 85) $churnDistribution['churning']++;
            else $churnDistribution['lost']++;

            $segKey = $a['segment_label'];
            $segmentDistribution[$segKey] = ($segmentDistribution[$segKey] ?? 0) + 1;
        }

        // مرتب‌سازی بر اساس CLV پیش‌بینی شده
        usort($analyses, fn($a, $b) => $b['predicted_clv_12m'] <=> $a['predicted_clv_12m']);

        $avgClv = count($analyses) > 0 ? (int) round($totalClv / count($analyses)) : 0;
        $avgChurn = count($analyses) > 0 ? (int) round($totalChurnProb / count($analyses)) : 0;

        return [
            'total_customers' => count($analyses),
            'avg_clv_12m' => $avgClv,
            'avg_churn_probability' => $avgChurn,
            'total_at_risk' => $totalAtRisk,
            'total_churned' => $totalChurned,
            'total_predicted_revenue_12m' => $totalPredictedRevenue,
            'churn_distribution' => $churnDistribution,
            'segment_distribution' => $segmentDistribution,
            'top_clv' => array_slice($analyses, 0, 50),
            'top_churn_risk' => array_slice(
                collect($analyses)->sortByDesc('churn_probability')->filter(fn($a) => $a['predicted_clv_12m'] > 0)->values()->toArray(),
                0,
                20
            ),
            'all' => $analyses,
        ];
    }

    /**
     * بخش‌بندی خودکار و ذخیره در جدول segments
     */
    public function autoSegment(?int $businessId = null): array
    {
        if (!Schema::hasTable('segments') || !Schema::hasTable('segment_member')) {
            return ['created' => 0, 'assigned' => 0];
        }

        $analysis = $this->analyzeAll($businessId, 1000);

        $autoSegments = [
            'champions_valuable' => [
                'name' => '🏆 قهرمانان وفادار - ارزشمند',
                'description' => 'مشتریان با بیشترین ارزش پیش‌بینی ۱۲ ماهه و وفاداری بالا - بیشترین تمرکز برای نگهداری',
                'logic' => 'churn_probability <= 20 AND predicted_clv_12m > avg',
            ],
            'valuable_at_risk' => [
                'name' => '⚠️ ارزشمند در معرض ریزش',
                'description' => 'مشتریان با ارزش بالا اما ۴۰٪ تا ۸۵٪ احتمال ریزش - نیاز به کمپین فوری بازگشت',
                'logic' => 'churn_probability 41-85 AND predicted_clv_12m > avg',
            ],
            'new_potential' => [
                'name' => '🌱 جدید با پتانسیل',
                'description' => 'تازه وارد، یک بار خرید - فرصت طلایی برای تبدیل به وفادار',
                'logic' => 'order_count = 1 AND recency <= 30',
            ],
            'loyal_regular' => [
                'name' => '💙 وفاداران معمولی',
                'description' => 'خرید منظم، ریسک کم - حفظ با باشگاه و پیشنهادات شخصی',
                'logic' => 'churn_probability 21-40',
            ],
            'sleeping' => [
                'name' => '😴 مشتریان خوابیده',
                'description' => '۳۰ تا ۶۰ روز بدون خرید - بیدار کردن با تخفیف بازگشت',
                'logic' => 'churn_probability 66-85',
            ],
            'lost' => [
                'name' => '💀 از دست رفته',
                'description' => 'بیش از ۸۵٪ احتمال ریزش - آخرین تلاش با پیشنهاد ویژه',
                'logic' => 'churn_probability > 85',
            ],
        ];

        $created = 0;
        $assigned = 0;

        DB::beginTransaction();
        try {
            foreach ($autoSegments as $key => $def) {
                $segment = Segment::firstOrCreate(
                    ['name' => $def['name']],
                    [
                        'type' => 'auto_clv',
                        'logic' => $def['logic'],
                        'description' => $def['description'],
                        'is_active' => true,
                        'members_count' => 0,
                    ]
                );

                if ($segment->wasRecentlyCreated) $created++;

                // پاک کردن اعضای قبلی این بخش (برای به‌روزرسانی)
                DB::table('segment_member')->where('segment_id', $segment->id)->delete();

                // انتخاب مشتریان بر اساس کلید
                $memberIds = [];
                foreach ($analysis['all'] as $a) {
                    $match = match ($key) {
                        'champions_valuable' => $a['churn_probability'] <= 20 && $a['predicted_clv_12m'] >= $analysis['avg_clv_12m'],
                        'valuable_at_risk' => $a['churn_probability'] >= 41 && $a['churn_probability'] <= 85 && $a['predicted_clv_12m'] >= $analysis['avg_clv_12m'],
                        'new_potential' => $a['order_count'] === 1 && $a['recency_days'] <= 30,
                        'loyal_regular' => $a['churn_probability'] >= 21 && $a['churn_probability'] <= 40,
                        'sleeping' => $a['churn_probability'] >= 66 && $a['churn_probability'] <= 85,
                        'lost' => $a['churn_probability'] > 85,
                        default => false,
                    };
                    if ($match) $memberIds[] = $a['customer_id'];
                }

                if (!empty($memberIds)) {
                    $rows = [];
                    $now = now();
                    foreach (array_unique($memberIds) as $cid) {
                        $rows[] = [
                            'segment_id' => $segment->id,
                            'customer_id' => $cid,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    foreach (array_chunk($rows, 500) as $chunk) {
                        DB::table('segment_member')->insert($chunk);
                    }
                    $segment->update([
                        'members_count' => count($memberIds),
                        'evaluated_at' => now(),
                    ]);
                    $assigned += count($memberIds);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('AutoSegment failed: ' . $e->getMessage());
        }

        return [
            'created' => $created,
            'assigned' => $assigned,
            'analysis' => $analysis,
        ];
    }
}
