<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Services\ClvAdvancedService;
use Modules\Core\Services\RetentionAutomationService;
use Modules\Core\Support\Jalali;
use Carbon\Carbon;

class RetentionController extends Controller
{
    /**
     * نمایش مرکز هوشمند نجات مشتری
     * اصول حرفه‌ای: تمام آمار بر اساس CLV و ریزش واقعی، شمسی، بدون انگلیسی در UI
     */
    public function index(Request $request)
    {
        $cacheKey = 'retention_center_' . (auth()->user()->business_id ?? 0);
        
        $data = Cache::remember($cacheKey, 300, function () {
            try {
                $clvService = app(ClvAdvancedService::class);
                $analysis = $clvService->analyzeAll(null, 500);
            } catch (\Throwable $e) {
                $analysis = [
                    'total_customers' => 0,
                    'avg_clv_12m' => 0,
                    'avg_churn_probability' => 0,
                    'total_at_risk' => 0,
                    'total_churned' => 0,
                    'total_predicted_revenue_12m' => 0,
                    'churn_distribution' => [],
                    'segment_distribution' => [],
                    'top_clv' => [],
                    'top_churn_risk' => [],
                    'all' => [],
                ];
            }

            try {
                $retentionService = app(RetentionAutomationService::class);
                $retentionResult = $retentionService->runAfterSegmentation($analysis);
                $recoveryMetrics = $retentionResult['recovery_metrics'] ?? $retentionService->getRecoveryMetrics($analysis);
            } catch (\Throwable $e) {
                $retentionResult = [
                    'workflows_created' => 0,
                    'campaigns_created' => 0,
                    'total_recoverable' => 0,
                    'recovery_metrics' => [
                        'recovered_30d' => 0,
                        'recovered_7d' => 0,
                        'recovered_revenue_30d' => 0,
                        'recovery_rate' => 0,
                        'recovered_list' => [],
                    ],
                ];
                $recoveryMetrics = $retentionResult['recovery_metrics'];
            }

            // جداسازی بخش‌ها
            $valuableAtRisk = [];
            $atRisk = [];
            $churning = [];
            $lost = [];
            $champions = [];

            foreach ($analysis['all'] ?? [] as $cust) {
                $key = $cust['segment_key'] ?? 'unknown';
                if ($key === 'valuable_at_risk') $valuableAtRisk[] = $cust;
                elseif ($key === 'at_risk') $atRisk[] = $cust;
                elseif ($key === 'churning') $churning[] = $cust;
                elseif ($key === 'lost') $lost[] = $cust;
                elseif ($key === 'champions') $champions[] = $cust;
            }

            // مرتب‌سازی بر اساس ارزش پیش‌بینی
            usort($valuableAtRisk, fn($a,$b) => ($b['predicted_clv_12m'] ?? 0) <=> ($a['predicted_clv_12m'] ?? 0));
            usort($atRisk, fn($a,$b) => ($b['churn_probability'] ?? 0) <=> ($a['churn_probability'] ?? 0));
            usort($churning, fn($a,$b) => ($b['predicted_clv_12m'] ?? 0) <=> ($a['predicted_clv_12m'] ?? 0));

            return [
                'analysis' => $analysis,
                'retention_result' => $retentionResult,
                'recovery' => $recoveryMetrics,
                'valuable_at_risk' => array_slice($valuableAtRisk, 0, 50),
                'at_risk' => array_slice($atRisk, 0, 50),
                'churning' => array_slice($churning, 0, 50),
                'lost' => array_slice($lost, 0, 30),
                'champions' => array_slice($champions, 0, 10),
                'top_churn_risk' => $analysis['top_churn_risk'] ?? [],
                'segment_distribution' => $analysis['segment_distribution'] ?? [],
                'churn_distribution' => $analysis['churn_distribution'] ?? [],
                'total_recoverable' => $retentionResult['total_recoverable'] ?? 0,
            ];
        });

        // فیلترهای درخواست کاربر
        $filter = $request->get('filter', 'valuable'); // valuable, at_risk, churning, lost, recovered, all
        $search = $request->get('q', '');

        return view('app.retention', array_merge($data, [
            'filter' => $filter,
            'search' => $search,
        ]));
    }

    /**
     * ارسال گروهی کمپین بازگشت
     */
    public function bulkCampaign(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array|min:1|max:50',
            'customer_ids.*' => 'integer|exists:customers,id',
            'message_type' => 'nullable|string|in:return_45,return_60,wake_sleeping',
        ]);

        $ids = $request->input('customer_ids', []);
        $type = $request->input('message_type', 'return_45');

        $eventMap = [
            'return_45' => 'no_purchase_45d',
            'return_60' => 'no_purchase_60d',
            'wake_sleeping' => 'no_purchase_60d',
        ];
        $event = $eventMap[$type] ?? 'no_purchase_45d';

        $sent = 0;
        $failed = 0;

        foreach ($ids as $cid) {
            try {
                $customer = Customer::find($cid);
                if (!$customer) {
                    $failed++;
                    continue;
                }

                // آتش زدن گردش‌کار بازگشت
                if (class_exists(\Modules\Core\Services\WorkflowEngine::class)) {
                    app(\Modules\Core\Services\WorkflowEngine::class)->fire($event, $customer);
                }

                // ایجاد فعالیت پیگیری ۲ ساعته
                if (Schema::hasTable('activities')) {
                    $customer->activities()->create([
                        'type' => 'task',
                        'title' => 'پیگیری کمپین بازگشت گروهی - ' . ($type === 'return_45' ? '۴۵ روزه' : '۶۰ روزه'),
                        'body' => 'کمپین بازگشت گروهی برای این مشتری ارسال شد. لطفاً طی ۲ ساعت نتیجه تماس را ثبت کنید. رویداد: ' . $event,
                        'due_at' => now()->addHours(2),
                        'done' => false,
                    ]);
                }

                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        // پاک کردن کش مرکز نجات
        try {
            Cache::forget('retention_center_' . (auth()->user()->business_id ?? 0));
            Cache::forget('reports_hub_' . (auth()->user()->business_id ?? 0) . '_clv_all__');
        } catch (\Throwable $e) {}

        $msg = 'ارسال گروهی انجام شد: ' . $sent . ' موفق';
        if ($failed > 0) $msg .= '، ' . $failed . ' ناموفق';

        return redirect()->back()->with('status', $msg);
    }

    /**
     * تبدیل تاریخ میلادی به شمسی با خروجی فارسی
     */
    private function toFaDate($carbonDate): string
    {
        try {
            if (!$carbonDate) return '---';
            $c = Carbon::parse($carbonDate);
            $j = Jalali::fromCarbon($c);
            return $j[0] . '/' . str_pad($j[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($j[2], 2, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            try {
                return Carbon::parse($carbonDate)->format('Y/m/d');
            } catch (\Throwable $e2) {
                return '---';
            }
        }
    }
}