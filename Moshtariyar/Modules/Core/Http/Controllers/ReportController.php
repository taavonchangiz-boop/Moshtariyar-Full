<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Modules\Core\Support\Jalali;
use Modules\Core\Support\Num;
use Modules\Core\Support\Money;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\IranPack\Entities\Payment;
use Modules\IranPack\Entities\TaxInvoice;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\LoyaltyReferral;
use Modules\Loyalty\Entities\LoyaltyTransaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const REPORTS = [
        'sales_by_status' => 'فروش به تفکیک وضعیت سفارش',
        'sales_by_source' => 'فروش به تفکیک منبع',
        'top_customers' => 'پرفروش‌ترین مشتریان',
        'customers_by_src' => 'مشتریان به تفکیک منبع',
        'monthly_sales' => 'فروش ماهانه',
        'payments_by_gateway' => 'پرداخت‌ها به تفکیک درگاه',
        'tax_invoices_status' => 'فاکتورهای رسمی به تفکیک وضعیت',
        'loyalty_from_sales' => 'اثر فروش روی باشگاه مشتریان',
        'referral_sales' => 'فروش ناشی از معرفی دوستان',
        'orders_loyalty_effect' => 'سفارش‌های دارای اثر باشگاهی',
    ];

    public const TABS = [
        'overview' => 'نمای کلی',
        'sales' => 'گزارش فروش',
        'rfm' => 'گروه‌بندی مشتریان',
        'clv' => 'ارزش عمر مشتری',
        'recovery' => 'درآمد بازگشتی',
        'top' => 'پرفروش‌ترین‌ها',
        'channels' => 'کانال‌های فروش',
        'payments' => 'پرداخت‌ها',
        'support' => 'عملکرد پشتیبانی',
        'export' => 'خروجی و ارسال',
    ];

    public const ORDER_STATUSES = [
        'pending' => 'در انتظار پرداخت',
        'processing' => 'در حال انجام',
        'on_hold' => 'در انتظار بررسی',
        'on-hold' => 'در انتظار بررسی',
        'completed' => 'تکمیل‌شده',
        'cancelled' => 'لغوشده',
        'refunded' => 'مرجوع‌شده',
        'failed' => 'ناموفق',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'در انتظار',
        'paid' => 'موفق',
        'failed' => 'ناموفق',
        'canceled' => 'لغوشده',
    ];

    public const GATEWAYS = [
        'zarinpal' => 'زرین‌پال',
        'zibal' => 'زیبال',
        'idpay' => 'آیدی‌پی',
        'nextpay' => 'نکست‌پی',
        'payping' => 'پی‌پینگ',
        'sadad' => 'سداد',
        'behpardakht' => 'به‌پرداخت',
        'saman' => 'سامان',
        'parsian' => 'پارسیان',
    ];

    public const TAX_STATUSES = [
        'draft' => 'پیش‌نویس',
        'queued' => 'در صف',
        'sent' => 'ارسال‌شده',
        'confirmed' => 'تأییدشده',
        'rejected' => 'رد شده',
        'failed' => 'ناموفق',
    ];

    public function index(Request $request)
    {
        if ($request->filled('type') && array_key_exists($request->get('type'), self::REPORTS)) {
            $type = $request->get('type', 'sales_by_status');
            $from = Jalali::parse($request->get('from'));
            $to = Jalali::parse($request->get('to'))?->endOfDay();
            $rows = $this->buildOld($type, $from, $to);

            return view('app.reports', [
                'reports' => self::REPORTS,
                'type' => $type,
                'rows' => $rows,
                'from' => $request->get('from'),
                'to' => $request->get('to'),
                'is_old_mode' => true,
            ]);
        }

        $tab = $request->get('tab', 'overview');
        if (!array_key_exists($tab, self::TABS)) $tab = 'overview';

        $range = $request->get('range', '30');
        [$from, $to, $prevFrom, $prevTo] = $this->resolveRange($request, $range);

        $businessId = $this->currentBusinessId();

        $cacheKey = "reports_hub_{$businessId}_{$tab}_{$range}_{$from->format('Ymd')}_{$to->format('Ymd')}";

        $payload = Cache::remember($cacheKey, 300, function () use ($from, $to, $prevFrom, $prevTo, $tab, $range, $businessId) {
            $overview = $this->buildOverview($from, $to, $prevFrom, $prevTo, $businessId);
            $sales = $this->buildAdvancedSalesReport($from, $to, $businessId);
            $rfm = $this->buildRfmAnalysis($from, $to, $businessId);
            $clv = $this->buildClvAnalysis($businessId);
            $recovery = $this->buildRecoveryAnalysis($from, $to, $businessId);
            $topProducts = $this->getTopProducts($from, $to, $businessId, 20);
            $topCategories = $this->getTopCategories($from, $to, $businessId, 10);
            $topCustomers = $this->getTopCustomers($from, $to, $businessId, 20);
            $channels = $this->getChannels($from, $to, $businessId);
            $payments = $this->getPayments($from, $to, $businessId);
            $support = $this->getSupport($from, $to, $businessId);

            return compact('overview', 'sales', 'rfm', 'clv', 'recovery', 'topProducts', 'topCategories', 'topCustomers', 'channels', 'payments', 'support');
        });

        if ($request->wantsJson()) {
            return response()->json([
                'موفق' => true,
                'تب' => $tab,
                'بازه' => $range,
                'داده' => $payload[$tab] ?? $payload['overview'],
            ]);
        }

        return view('app.reports_hub', [
            'tab' => $tab,
            'range' => $range,
            'from' => $request->get('from'),
            'to' => $request->get('to'),
            'from_jalali' => Jalali::date($from),
            'to_jalali' => Jalali::date($to),
            'tabs' => self::TABS,
            'overview' => $payload['overview'],
            'sales' => $payload['sales'],
            'rfm' => $payload['rfm'],
            'clv' => $payload['clv'],
            'recovery' => $payload['recovery'],
            'topProducts' => $payload['topProducts'],
            'topCategories' => $payload['topCategories'],
            'topCustomers' => $payload['topCustomers'],
            'channels' => $payload['channels'],
            'payments' => $payload['payments'],
            'support' => $payload['support'],
            'is_old_mode' => false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        if ($request->has('modern_export') || $request->has('tab')) {
            return $this->modernExport($request);
        }

        $type = $request->get('type', 'sales_by_status');
        $rows = $this->buildOld($type, Jalali::parse($request->get('from')), Jalali::parse($request->get('to'))?->endOfDay());
        $name = $type . '.csv';

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            if (!empty($rows)) {
                fputcsv($out, array_keys($rows[0]));
                foreach ($rows as $row) fputcsv($out, $row);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    public function hub(Request $request)
    {
        $request->merge(['tab' => $request->get('tab', 'overview')]);
        return $this->index($request);
    }

    protected function resolveRange(Request $request, ?string $forcedRange = null): array
    {
        $range = $forcedRange ?: $request->get('range', '30');

        if ($request->filled('from') && $request->filled('to')) {
            $from = Jalali::parse($request->get('from')) ?: Carbon::now()->subDays(30)->startOfDay();
            $to = Jalali::parse($request->get('to'))?->endOfDay() ?: Carbon::now()->endOfDay();
        } else {
            $to = Carbon::now()->endOfDay();
            switch ($range) {
                case '7': $from = Carbon::now()->subDays(7)->startOfDay(); break;
                case '90': $from = Carbon::now()->subDays(90)->startOfDay(); break;
                case '365': $from = Carbon::now()->subDays(365)->startOfDay(); break;
                case 'all': $from = Carbon::create(2000, 1, 1)->startOfDay(); break;
                case '30': default: $from = Carbon::now()->subDays(30)->startOfDay(); break;
            }
        }

        $diff = max(1, $from->diffInDays($to));
        $prevFrom = (clone $from)->subDays($diff)->startOfDay();
        $prevTo = (clone $from)->subSecond();

        return [$from, $to, $prevFrom, $prevTo];
    }

    protected function buildOverview(Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo, ?int $businessId = null): array
    {
        $current = $this->periodMetrics($from, $to, $businessId);
        $prev = $this->periodMetrics($prevFrom, $prevTo, $businessId);
        $trend = $this->salesTrend($from, $to, $businessId);

        return [
            'total_sales' => $current['sales'],
            'total_sales_prev' => $prev['sales'],
            'sales_change' => $this->percentChange($prev['sales'], $current['sales']),
            'order_count' => $current['orders'],
            'order_count_prev' => $prev['orders'],
            'orders_change' => $this->percentChange($prev['orders'], $current['orders']),
            'new_customers' => $current['new_customers'],
            'new_customers_prev' => $prev['new_customers'],
            'customers_change' => $this->percentChange($prev['new_customers'], $current['new_customers']),
            'active_customers' => $current['active_customers'],
            'avg_order' => $current['orders'] > 0 ? (int) round($current['sales'] / $current['orders']) : 0,
            'trend_labels' => $trend['labels'],
            'trend_data' => $trend['data'],
        ];
    }

    protected function periodMetrics(Carbon $from, Carbon $to, ?int $businessId = null): array
    {
        $sales = 0; $orders = 0; $newCust = 0; $activeCust = 0;

        if (Schema::hasTable('orders')) {
            try {
                $q = DB::table('orders')->whereBetween('placed_at', [$from, $to]);
                $q = $this->applyBusiness($q, 'orders', $businessId);
                $orders = (clone $q)->count();
                $col = Schema::hasColumn('orders', 'total') ? 'total' : (Schema::hasColumn('orders', 'payable') ? 'payable' : null);
                if ($col) $sales = (int) (clone $q)->sum($col);
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('customers')) {
            try {
                $q = DB::table('customers')->whereBetween('created_at', [$from, $to]);
                $q = $this->applyBusiness($q, 'customers', $businessId);
                $newCust = $q->count();

                if (Schema::hasTable('orders')) {
                    $oq = DB::table('orders')->whereBetween('placed_at', [$from, $to]);
                    $oq = $this->applyBusiness($oq, 'orders', $businessId);
                    $activeCust = (clone $oq)->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id');
                }
            } catch (\Throwable $e) {}
        }

        return ['sales' => $sales,'orders' => $orders,'new_customers' => $newCust,'active_customers' => $activeCust,];
    }

    protected function salesTrend(Carbon $from, Carbon $to, ?int $businessId = null): array
    {
        $labels = []; $data = [];
        if (!Schema::hasTable('orders')) return ['labels' => [], 'data' => []];
        $days = max(1, $from->diffInDays($to));
        $step = $days > 120 ? 'month' : ($days > 30 ? 'week' : 'day');
        $col = Schema::hasColumn('orders', 'total') ? 'total' : (Schema::hasColumn('orders', 'payable') ? 'payable' : null);
        if (!$col) return ['labels' => [], 'data' => []];
        try {
            if ($step === 'day') {
                $rows = DB::table('orders')->select(DB::raw('DATE(placed_at) as d'), DB::raw("SUM($col) as s"))->whereBetween('placed_at', [$from, $to]);
                $rows = $this->applyBusiness($rows, 'orders', $businessId);
                $rows = $rows->groupBy('d')->orderBy('d')->get();
                foreach ($rows as $r) { $labels[] = $this->jalaliShort($r->d, 'md'); $data[] = (int) $r->s; }
            } elseif ($step === 'week') {
                $rows = DB::table('orders')->select(DB::raw('YEARWEEK(placed_at, 1) as w'), DB::raw("SUM($col) as s"), DB::raw('MIN(placed_at) as first_day'))->whereBetween('placed_at', [$from, $to]);
                $rows = $this->applyBusiness($rows, 'orders', $businessId);
                $rows = $rows->groupBy('w')->orderBy('w')->get();
                foreach ($rows as $r) { $labels[] = 'هفته ' . $this->jalaliShort($r->first_day, 'md'); $data[] = (int) $r->s; }
            } else {
                $rows = DB::table('orders')->select(DB::raw('DATE_FORMAT(placed_at, "%Y-%m") as m'), DB::raw("SUM($col) as s"), DB::raw('MIN(placed_at) as first_day'))->whereBetween('placed_at', [$from, $to]);
                $rows = $this->applyBusiness($rows, 'orders', $businessId);
                $rows = $rows->groupBy('m')->orderBy('m')->get();
                foreach ($rows as $r) { $labels[] = $this->jalaliShort($r->first_day, 'ym'); $data[] = (int) $r->s; }
            }
        } catch (\Throwable $e) {}
        return ['labels' => $labels, 'data' => $data];
    }

    protected function buildAdvancedSalesReport(Carbon $from, Carbon $to, ?int $businessId = null): array
    {
        if (!Schema::hasTable('orders')) return ['by_status' => [], 'by_hour' => array_fill(0, 24, 0), 'by_weekday' => array_fill(0, 7, 0), 'by_source' => []];
        $col = Schema::hasColumn('orders', 'total') ? 'total' : (Schema::hasColumn('orders', 'payable') ? 'payable' : null);
        $byStatus = []; $byHour = array_fill(0, 24, 0); $byWeekday = array_fill(0, 7, 0); $bySource = [];
        try {
            if (Schema::hasColumn('orders', 'status') && $col) {
                $q = DB::table('orders')->select('status', DB::raw('COUNT(*) as c'), DB::raw("SUM($col) as s"))->whereBetween('placed_at', [$from, $to]);
                $q = $this->applyBusiness($q, 'orders', $businessId);
                $rows = $q->groupBy('status')->get();
                foreach ($rows as $r) { $byStatus[] = ['status' => self::ORDER_STATUSES[$r->status] ?? $r->status, 'count' => (int) $r->c, 'sum' => (int) $r->s]; }
            }
            $q = DB::table('orders')->select(DB::raw('HOUR(placed_at) as h'), DB::raw('COUNT(*) as c'))->whereBetween('placed_at', [$from, $to]);
            $q = $this->applyBusiness($q, 'orders', $businessId);
            $rows = $q->groupBy('h')->orderBy('h')->get();
            foreach ($rows as $r) $byHour[(int) $r->h] = (int) $r->c;
            $q = DB::table('orders')->select(DB::raw('WEEKDAY(placed_at) as w'), DB::raw('COUNT(*) as c'))->whereBetween('placed_at', [$from, $to]);
            $q = $this->applyBusiness($q, 'orders', $businessId);
            $rows = $q->groupBy('w')->orderBy('w')->get();
            foreach ($rows as $r) $byWeekday[(int) $r->w] = (int) $r->c;
            if (Schema::hasColumn('orders', 'source')) {
                $q = DB::table('orders')->select('source', DB::raw('COUNT(*) as c'), DB::raw($col ? "SUM($col) as s" : 'COUNT(*) as s'))->whereBetween('placed_at', [$from, $to]);
                $q = $this->applyBusiness($q, 'orders', $businessId);
                $rows = $q->groupBy('source')->get();
                foreach ($rows as $r) { $bySource[] = ['source' => $r->source ?: 'نامشخص', 'count' => (int) $r->c, 'sum' => (int) $r->s]; }
            }
        } catch (\Throwable $e) {}
        return ['by_status' => $byStatus, 'by_hour' => $byHour, 'by_weekday' => $byWeekday, 'by_source' => $bySource];
    }

    protected function buildRfmAnalysis(Carbon $from, Carbon $to, ?int $businessId = null): array
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('customers')) return ['segments' => [], 'total' => 0, 'top_customers' => []];
        $col = Schema::hasColumn('orders', 'total') ? 'total' : (Schema::hasColumn('orders', 'payable') ? 'payable' : 'id');
        try {
            $q = DB::table('orders')->join('customers', 'orders.customer_id', '=', 'customers.id')->select('orders.customer_id', 'customers.full_name', 'customers.phone', DB::raw('MAX(orders.placed_at) as last_order'), DB::raw('COUNT(orders.id) as frequency'), DB::raw("SUM(orders.$col) as monetary"))->whereNotNull('orders.customer_id')->whereBetween('orders.placed_at', [$from, $to])->groupBy('orders.customer_id', 'customers.full_name', 'customers.phone');
            if ($businessId && Schema::hasColumn('orders', 'business_id')) $q->where('orders.business_id', $businessId);
            if ($businessId && Schema::hasColumn('customers', 'business_id')) $q->where('customers.business_id', $businessId);
            $rows = $q->get();
        } catch (\Throwable $e) { return ['segments' => [], 'total' => 0, 'top_customers' => []]; }

        $now = Carbon::now();
        $segments = [
            'champions' => ['key' => 'champions', 'label' => 'قهرمانان', 'desc' => 'اخیراً و زیاد خرید کرده‌اند - وفادارترین‌ها', 'color' => '#10b981', 'count' => 0],
            'loyal' => ['key' => 'loyal', 'label' => 'وفاداران', 'desc' => 'زیاد خرید می‌کنند - قابل اتکا', 'color' => '#3b82f6', 'count' => 0],
            'potential' => ['key' => 'potential', 'label' => 'مستعد وفاداری', 'desc' => 'اخیراً خرید کرده‌اند - فرصت طلایی', 'color' => '#8b5cf6', 'count' => 0],
            'new' => ['key' => 'new', 'label' => 'مشتریان جدید', 'desc' => 'اولین خریدشان بوده - خوشامد ویژه', 'color' => '#14b8a6', 'count' => 0],
            'at_risk' => ['key' => 'at_risk', 'label' => 'در معرض خطر', 'desc' => 'مدتی است خرید نکرده‌اند - نیاز به یادآوری', 'color' => '#f59e0b', 'count' => 0],
            'lost' => ['key' => 'lost', 'label' => 'از دست رفته', 'desc' => 'خیلی وقت است خرید نکرده‌اند - آخرین تلاش', 'color' => '#ef4444', 'count' => 0],
        ];
        $customers = [];
        foreach ($rows as $r) {
            $recency = $r->last_order ? Carbon::parse($r->last_order)->diffInDays($now) : 999;
            $freq = (int) $r->frequency;
            if ($recency <= 30 && $freq >= 5) $seg = 'champions';
            elseif ($recency <= 60 && $freq >= 3) $seg = 'loyal';
            elseif ($recency <= 30 && $freq == 1) $seg = 'new';
            elseif ($recency <= 90 && $freq >= 1) $seg = 'potential';
            elseif ($recency <= 180) $seg = 'at_risk';
            else $seg = 'lost';
            $segments[$seg]['count']++;
            $customers[] = ['id' => $r->customer_id,'name' => $r->full_name ?: 'بدون نام','phone' => $r->phone ?: '---','recency' => $recency,'frequency' => $freq,'monetary' => (int) $r->monetary,'segment' => $segments[$seg]['label'],'color' => $segments[$seg]['color'],];
        }
        usort($customers, fn($a, $b) => $b['monetary'] <=> $a['monetary']);
        return ['segments' => array_values($segments),'total' => count($customers),'top_customers' => array_slice($customers, 0, 50),];
    }

    protected function buildClvAnalysis(?int $businessId = null): array
    {
        try {
            if (class_exists(\Modules\Core\Services\ClvAdvancedService::class)) {
                $service = app(\Modules\Core\Services\ClvAdvancedService::class);
                $advanced = $service->analyzeAll($businessId, 500);
                $legacyTop = [];
                if (Schema::hasTable('customers')) {
                    $q = Customer::query()->where('lifetime_value', '>', 0)->orderByDesc('lifetime_value');
                    if ($businessId && Schema::hasColumn('customers', 'business_id')) $q->where('business_id', $businessId);
                    $legacyTop = $q->limit(20)->get(['id', 'full_name', 'phone', 'lifetime_value'])->map(fn($c) => ['id' => $c->id,'name' => $c->full_name ?: '—','phone' => $c->phone ?: '---','value' => (int) $c->lifetime_value,'url' => url('/app/customers/' . $c->id),])->toArray();
                }
                return array_merge($advanced, ['avg_clv' => $advanced['avg_clv_12m'] ?? 0,'top_clv_legacy' => $legacyTop,'total' => $advanced['total_customers'] ?? 0,]);
            }
        } catch (\Throwable $e) {}
        if (!Schema::hasTable('customers')) return ['avg_clv' => 0, 'top_clv' => [], 'total' => 0, 'avg_clv_12m' => 0, 'total_customers' => 0, 'avg_churn_probability' => 0, 'total_at_risk' => 0, 'total_churned' => 0, 'total_predicted_revenue_12m' => 0, 'churn_distribution' => [], 'segment_distribution' => [], 'top_clv' => [], 'top_churn_risk' => [], 'all' => []];
        try {
            $q = Customer::query()->where('lifetime_value', '>', 0)->orderByDesc('lifetime_value');
            if ($businessId && Schema::hasColumn('customers', 'business_id')) $q->where('business_id', $businessId);
            $top = $q->limit(50)->get(['id', 'full_name', 'phone', 'lifetime_value'])->map(fn($c) => ['id' => $c->id,'name' => $c->full_name ?: '—','phone' => $c->phone ?: '---','value' => (int) $c->lifetime_value,'url' => url('/app/customers/' . $c->id),])->toArray();
            $avgQ = DB::table('customers')->where('lifetime_value', '>', 0);
            if ($businessId && Schema::hasColumn('customers', 'business_id')) $avgQ->where('business_id', $businessId);
            $avg = (int) ($avgQ->avg('lifetime_value') ?: 0);
            return ['avg_clv' => $avg,'avg_clv_12m' => $avg,'top_clv' => $top,'top_clv_legacy' => $top,'total' => count($top),'total_customers' => count($top),'avg_churn_probability' => 0,'total_at_risk' => 0,'total_churned' => 0,'total_predicted_revenue_12m' => 0,'churn_distribution' => [],'segment_distribution' => [],'top_churn_risk' => [],'all' => $top,];
        } catch (\Throwable $e) {
            return ['avg_clv' => 0, 'top_clv' => [], 'total' => 0, 'avg_clv_12m' => 0, 'total_customers' => 0, 'avg_churn_probability' => 0, 'total_at_risk' => 0, 'total_churned' => 0, 'total_predicted_revenue_12m' => 0, 'churn_distribution' => [], 'segment_distribution' => [], 'top_clv' => [], 'top_churn_risk' => [], 'all' => []];
        }
    }

    protected function buildRecoveryAnalysis(Carbon $from, Carbon $to, ?int $businessId = null): array
    {
        try {
            if (class_exists(\Modules\Core\Services\ClvAdvancedService::class) && class_exists(\Modules\Core\Services\RetentionAutomationService::class)) {
                $clvService = app(\Modules\Core\Services\ClvAdvancedService::class);
                $analysis = $clvService->analyzeAll($businessId, 500);
                $retentionService = app(\Modules\Core\Services\RetentionAutomationService::class);
                $recoveryMetrics = $retentionService->getRecoveryMetrics($analysis);
            } else {
                $analysis = [];
                $recoveryMetrics = ['recovered_30d' => 0,'recovered_7d' => 0,'recovered_revenue_30d' => 0,'recovery_rate' => 0,'recovered_list' => [],];
            }

            // محاسبه درآمد بازگشتی ماهانه شمسی - ۱۲ ماه اخیر با ماه‌های شمسی یونیک (بدون تکرار)
            $monthlyRecovered = []; $monthlyLabels = []; $monthlyCount = [];
            $monthNames = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];

            // تاریخ شمسی امروز
            try {
                $nowJalali = Jalali::toJalali((int)now()->format('Y'), (int)now()->format('n'), (int)now()->format('j'));
                $curJy = $nowJalali[0];
                $curJm = $nowJalali[1];
            } catch (\Throwable $e) {
                $curJy = 1404; $curJm = 5;
            }

            // ۱۲ ماه شمسی اخیر - از قدیمی به جدید - هر ماه یونیک
            $jalaliMonths = [];
            for ($k = 11; $k >= 0; $k--) {
                $jy = $curJy;
                $jm = $curJm - $k;
                while ($jm <= 0) { $jm += 12; $jy--; }
                while ($jm > 12) { $jm -= 12; $jy++; }
                $jalaliMonths[] = ['jy' => $jy, 'jm' => $jm];
            }

            foreach ($jalaliMonths as $jmInfo) {
                $jy = $jmInfo['jy'];
                $jm = $jmInfo['jm'];

                // برچسب یکتا: نام ماه + سال شمسی فارسی
                try {
                    $label = $monthNames[$jm-1] . ' ' . Num::fa($jy);
                } catch (\Throwable $e) {
                    $label = $monthNames[$jm-1] ?? 'ماه';
                }
                $monthlyLabels[] = $label;

                // بازه میلادی این ماه شمسی
                try {
                    // شروع: روز ۱ این ماه شمسی
                    [$gy1, $gm1, $gd1] = Jalali::toGregorian($jy, $jm, 1);
                    $start = Carbon::createFromDate($gy1, $gm1, $gd1)->startOfDay();

                    // پایان: روز ۱ ماه بعد منهای یک روز
                    $nextJy = $jy;
                    $nextJm = $jm + 1;
                    if ($nextJm > 12) { $nextJm = 1; $nextJy++; }
                    [$gy2, $gm2, $gd2] = Jalali::toGregorian($nextJy, $nextJm, 1);
                    $end = Carbon::createFromDate($gy2, $gm2, $gd2)->subDay()->endOfDay();

                } catch (\Throwable $e) {
                    // fallback به ماه میلادی
                    $start = now()->subMonths(12)->startOfMonth();
                    $end = now()->endOfMonth();
                }

                try {
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
            $totalCount12m = array_sum($monthlyCount);
            $avgMonthly = $totalCount12m > 0 ? (int)($totalRecovered12m / 12) : 0;

            $recoveredInRange = [];
            try {
                $ordersInRange = DB::table('orders')->whereIn('status', ['completed','processing'])->whereBetween('placed_at', [$from, $to])->whereNotNull('customer_id')->when($businessId && Schema::hasColumn('orders','business_id'), fn($q) => $q->where('business_id', $businessId))->orderByDesc('placed_at')->limit(200)->get();
                foreach ($ordersInRange as $order) {
                    try {
                        $prev = DB::table('orders')->where('customer_id', $order->customer_id)->whereIn('status', ['completed','processing'])->where('id','!=',$order->id)->where('placed_at','<',$order->placed_at)->orderByDesc('placed_at')->first();
                        if (!$prev) continue;
                        $gap = Carbon::parse($order->placed_at)->diffInDays(Carbon::parse($prev->placed_at));
                        if ($gap >= 45) {
                            $cust = DB::table('customers')->where('id', $order->customer_id)->first();
                            if (!$cust) continue;
                            try { $j = Jalali::fromCarbon(Carbon::parse($order->placed_at)); $fa = $j[0].'/'.str_pad($j[1],2,'0',STR_PAD_LEFT).'/'.str_pad($j[2],2,'0',STR_PAD_LEFT); } catch (\Throwable $e) { $fa = Carbon::parse($order->placed_at)->format('Y/m/d'); }
                            $recoveredInRange[] = ['customer_id' => $order->customer_id,'full_name' => $cust->full_name ?: 'بدون نام','phone' => $cust->phone ?: '---','gap_days' => (int)$gap,'total' => (int)$order->total,'date_fa' => $fa,'date' => $order->placed_at,];
                        }
                    } catch (\Throwable $e) { continue; }
                }
                $unique = []; $seen = [];
                foreach ($recoveredInRange as $r) { if (in_array($r['customer_id'], $seen)) continue; $seen[] = $r['customer_id']; $unique[] = $r; if (count($unique) >= 50) break; }
                $recoveredInRange = $unique;
            } catch (\Throwable $e) { $recoveredInRange = []; }

            return [
                'recovered_30d' => $recoveryMetrics['recovered_30d'] ?? 0,
                'recovered_7d' => $recoveryMetrics['recovered_7d'] ?? 0,
                'recovered_revenue_30d' => $recoveryMetrics['recovered_revenue_30d'] ?? 0,
                'recovery_rate' => $recoveryMetrics['recovery_rate'] ?? 0,
                'recovered_list' => $recoveryMetrics['recovered_list'] ?? [],
                'monthly_labels' => $monthlyLabels,
                'monthly_recovered' => $monthlyRecovered,
                'monthly_count' => $monthlyCount,
                'total_recovered_12m' => $totalRecovered12m,
                'total_count_12m' => $totalCount12m,
                'avg_monthly_recovered' => $avgMonthly,
                'recovered_in_range' => $recoveredInRange,
                'total_predicted' => $analysis['total_predicted_revenue_12m'] ?? 0,
                'total_at_risk' => $analysis['total_at_risk'] ?? 0,
            ];

        } catch (\Throwable $e) {
            return [
                'recovered_30d' => 0,'recovered_7d' => 0,'recovered_revenue_30d' => 0,'recovery_rate' => 0,'recovered_list' => [],
                'monthly_labels' => [],'monthly_recovered' => [],'monthly_count' => [],
                'total_recovered_12m' => 0,'total_count_12m' => 0,'avg_monthly_recovered' => 0,
                'recovered_in_range' => [],'total_predicted' => 0,'total_at_risk' => 0,
            ];
        }
    }

    protected function getTopProducts(Carbon $from, Carbon $to, ?int $businessId, int $limit = 20): array
    {
        if (!Schema::hasTable('order_items')) return [];
        try {
            $qtyCol = Schema::hasColumn('order_items', 'qty') ? 'qty' : (Schema::hasColumn('order_items', 'quantity') ? 'quantity' : 'qty');
            $priceCol = Schema::hasColumn('order_items', 'unit_price') ? 'unit_price' : (Schema::hasColumn('order_items', 'price') ? 'price' : null);
            if (!$priceCol) return [];
            $q = DB::table('order_items')->join('orders', 'order_items.order_id', '=', 'orders.id')->select(DB::raw('COALESCE(order_items.name, "محصول") as product_name'), DB::raw("SUM(order_items.$qtyCol) as qty"), DB::raw("SUM(order_items.$qtyCol * order_items.$priceCol) as total"))->whereBetween('orders.placed_at', [$from, $to])->groupBy('product_name')->orderByDesc('total')->limit($limit);
            if ($businessId && Schema::hasColumn('orders', 'business_id')) $q->where('orders.business_id', $businessId);
            return $q->get()->toArray();
        } catch (\Throwable $e) { return []; }
    }

    protected function getTopCategories(Carbon $from, Carbon $to, ?int $businessId, int $limit = 10): array
    {
        if (!Schema::hasTable('order_items') || !Schema::hasTable('products')) return [];
        try {
            $qtyCol = Schema::hasColumn('order_items', 'qty') ? 'qty' : 'quantity';
            $priceCol = Schema::hasColumn('order_items', 'unit_price') ? 'unit_price' : 'price';
            $q = DB::table('order_items')->join('products', 'order_items.product_id', '=', 'products.id')->join('orders', 'order_items.order_id', '=', 'orders.id')->select(DB::raw('COALESCE(products.category, "بدون دسته") as cat_name'), DB::raw("SUM(order_items.$qtyCol) as qty"), DB::raw("SUM(order_items.$qtyCol * order_items.$priceCol) as total"))->whereBetween('orders.placed_at', [$from, $to])->groupBy('cat_name')->orderByDesc('total')->limit($limit);
            if ($businessId && Schema::hasColumn('orders', 'business_id')) $q->where('orders.business_id', $businessId);
            return $q->get()->toArray();
        } catch (\Throwable $e) { return []; }
    }

    protected function getTopCustomers(Carbon $from, Carbon $to, ?int $businessId, int $limit = 20): array
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('customers')) return [];
        $col = Schema::hasColumn('orders', 'total') ? 'total' : (Schema::hasColumn('orders', 'payable') ? 'payable' : 'id');
        try {
            $q = DB::table('orders')->join('customers', 'orders.customer_id', '=', 'customers.id')->select('customers.id', 'customers.full_name', 'customers.phone', DB::raw('COUNT(orders.id) as orders_count'), DB::raw("SUM(orders.$col) as total_spent"))->whereBetween('orders.placed_at', [$from, $to])->whereNotNull('orders.customer_id')->groupBy('customers.id', 'customers.full_name', 'customers.phone')->orderByDesc('total_spent')->limit($limit);
            if ($businessId && Schema::hasColumn('orders', 'business_id')) $q->where('orders.business_id', $businessId);
            if ($businessId && Schema::hasColumn('customers', 'business_id')) $q->where('customers.business_id', $businessId);
            return $q->get()->map(fn($r) => ['id' => $r->id,'name' => $r->full_name ?: '—','phone' => $r->phone ?: '---','orders' => (int) $r->orders_count,'spent' => (int) $r->total_spent,'url' => url('/app/customers/' . $r->id),])->toArray();
        } catch (\Throwable $e) { return []; }
    }

    protected function getChannels(Carbon $from, Carbon $to, ?int $businessId): array
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'source')) return [];
        $col = Schema::hasColumn('orders', 'total') ? 'total' : (Schema::hasColumn('orders', 'payable') ? 'payable' : 'id');
        try {
            $q = DB::table('orders')->select('source', DB::raw('COUNT(*) as c'), DB::raw("SUM($col) as s"))->whereBetween('placed_at', [$from, $to])->groupBy('source')->orderByDesc('s');
            $q = $this->applyBusiness($q, 'orders', $businessId);
            $map = ['woocommerce' => 'فروشگاه اینترنتی','manual' => 'ثبت حضوری','manual_pricing' => 'ثبت از محاسبه قیمت','manual_quick' => 'فروش سریع','pos' => 'صندوق فروش','lead' => 'تبدیل سرنخ','telegram' => 'تلگرام','phone' => 'تلفنی','instagram' => 'اینستاگرام','api' => 'وب سرویس','app' => 'اپلیکیشن','web' => 'وب‌سایت','whatsapp' => 'واتساپ','bale' => 'بله','rubika' => 'روبیکا','eitaa' => 'ایتا',];
            return $q->get()->map(function($r) use ($map) { $raw = trim((string) ($r->source ?? '')); $label = $map[$raw] ?? 'سایر'; return ['source' => $raw,'label' => $label,'count' => (int) $r->c,'sales' => (int) $r->s,];})->toArray();
        } catch (\Throwable $e) { return []; }
    }

    protected function getPayments(Carbon $from, Carbon $to, ?int $businessId): array
    {
        if (!Schema::hasTable('payments')) return ['by_gateway' => [], 'by_status' => [], 'total' => 0];
        $amtCol = Schema::hasColumn('payments', 'amount') ? 'amount' : (Schema::hasColumn('payments', 'payable') ? 'payable' : 'total');
        try {
            $q = DB::table('payments')->whereBetween('created_at', [$from, $to]);
            $q = $this->applyBusiness($q, 'payments', $businessId);
            $total = (int) (clone $q)->sum($amtCol);
            $byGateway = []; $byStatus = [];
            if (Schema::hasColumn('payments', 'gateway')) {
                $gq = DB::table('payments')->select('gateway', DB::raw('COUNT(*) as c'), DB::raw("SUM($amtCol) as s"))->whereBetween('created_at', [$from, $to]);
                $gq = $this->applyBusiness($gq, 'payments', $businessId);
                $byGateway = $gq->groupBy('gateway')->orderByDesc('s')->get()->map(fn($r) => ['gateway' => self::GATEWAYS[$r->gateway] ?? ($r->gateway ?: 'نامشخص'), 'count' => (int) $r->c, 'sum' => (int) $r->s])->toArray();
            }
            if (Schema::hasColumn('payments', 'status')) {
                $sq = DB::table('payments')->select('status', DB::raw('COUNT(*) as c'), DB::raw("SUM($amtCol) as s"))->whereBetween('created_at', [$from, $to]);
                $sq = $this->applyBusiness($sq, 'payments', $businessId);
                $byStatus = $sq->groupBy('status')->get()->map(fn($r) => ['status' => self::PAYMENT_STATUSES[$r->status] ?? $r->status, 'count' => (int) $r->c, 'sum' => (int) $r->s])->toArray();
            }
            return ['by_gateway' => $byGateway, 'by_status' => $byStatus, 'total' => $total];
        } catch (\Throwable $e) { return ['by_gateway' => [], 'by_status' => [], 'total' => 0]; }
    }

    protected function getSupport(Carbon $from, Carbon $to, ?int $businessId): array
    {
        if (!Schema::hasTable('tickets')) return ['total' => 0, 'open' => 0, 'closed' => 0, 'avg_response' => 0, 'by_dept' => []];
        try {
            $q = DB::table('tickets')->whereBetween('created_at', [$from, $to]);
            $q = $this->applyBusiness($q, 'tickets', $businessId);
            $total = (clone $q)->count();
            $open = (clone $q)->whereNotIn('status', ['closed', 'resolved'])->count();
            $closed = (clone $q)->whereIn('status', ['closed', 'resolved'])->count();
            $avg = 0;
            if (Schema::hasColumn('tickets', 'first_response_at')) {
                $avg = (int) (clone $q)->whereNotNull('first_response_at')->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as m')->value('m');
            }
            $byDept = [];
            if (Schema::hasColumn('tickets', 'department')) {
                $dq = DB::table('tickets')->select('department', DB::raw('COUNT(*) as c'))->whereBetween('created_at', [$from, $to]);
                $dq = $this->applyBusiness($dq, 'tickets', $businessId);
                $byDept = $dq->groupBy('department')->get()->map(fn($r) => ['name' => $r->department ?: 'عمومی', 'count' => (int) $r->c])->toArray();
            }
            return ['total' => $total, 'open' => $open, 'closed' => $closed, 'avg_response' => $avg, 'by_dept' => $byDept];
        } catch (\Throwable $e) { return ['total' => 0, 'open' => 0, 'closed' => 0, 'avg_response' => 0, 'by_dept' => []]; }
    }

    private function buildOld(string $type, ?Carbon $from = null, ?Carbon $to = null): array
    {
        return match ($type) {
            'sales_by_source' => $this->oldSalesBySource($from, $to),
            'top_customers' => $this->oldTopCustomers(),
            'customers_by_src' => $this->oldCustomersBySrc(),
            'monthly_sales' => $this->oldMonthlySales($from, $to),
            'payments_by_gateway' => $this->oldPaymentsByGateway($from, $to),
            'tax_invoices_status' => $this->oldTaxInvoicesStatus($from, $to),
            'loyalty_from_sales' => $this->loyaltyFromSales($from, $to),
            'referral_sales' => $this->referralSales($from, $to),
            'orders_loyalty_effect' => $this->ordersLoyaltyEffect($from, $to),
            default => $this->oldSalesByStatus($from, $to),
        };
    }

    private function orderBase(?Carbon $from, ?Carbon $to)
    {
        return Order::query()->when($from, fn($q) => $q->where('placed_at', '>=', $from))->when($to, fn($q) => $q->where('placed_at', '<=', $to));
    }

    private function oldSalesByStatus(?Carbon $from, ?Carbon $to): array
    {
        $totalOrders = (clone $this->orderBase($from, $to))->count();
        $rows = $this->orderBase($from, $to)->selectRaw('status, COUNT(*) as count_orders, SUM(total) as sum_total, AVG(total) as avg_total, MAX(placed_at) as last_order_at')->groupBy('status')->orderByDesc('sum_total')->get();
        return $rows->map(function ($row) use ($totalOrders) {
            $status = (string) $row->status;
            return ['وضعیت' => self::ORDER_STATUSES[$status] ?? $status,'تعداد سفارش' => (int) $row->count_orders,'سهم از سفارش‌ها' => $totalOrders ? round(((int) $row->count_orders) * 100 / $totalOrders, 1) . '٪' : '۰٪','مبلغ کل' => (float) $row->sum_total,'میانگین سفارش' => (float) $row->avg_total,'آخرین سفارش' => $row->last_order_at ? Jalali::datetime(Carbon::parse($row->last_order_at)) : '—',];
        })->all();
    }

    private function oldSalesBySource(?Carbon $from, ?Carbon $to): array
    {
        return $this->orderBase($from, $to)->selectRaw('source as عنوان, COUNT(*) as تعداد, SUM(total) as مبلغ')->groupBy('source')->get()->map(fn($row) => (array) $row->getAttributes())->all();
    }

    private function oldTopCustomers(): array
    {
        return Customer::orderByDesc('lifetime_value')->limit(20)->get(['full_name', 'phone', 'lifetime_value'])->map(fn($c) => ['نام' => $c->full_name, 'موبایل' => $c->phone, 'ارزش' => $c->lifetime_value])->all();
    }

    private function oldCustomersBySrc(): array
    {
        return Customer::selectRaw('source as منبع, COUNT(*) as تعداد')->groupBy('source')->get()->map(fn($row) => (array) $row->getAttributes())->all();
    }

    private function oldMonthlySales(?Carbon $from = null, ?Carbon $to = null): array
    {
        $rows = $this->orderBase($from, $to)->selectRaw("DATE_FORMAT(placed_at,'%Y-%m') ym, COUNT(*) c, SUM(total) t")->whereNotNull('placed_at')->groupByRaw("DATE_FORMAT(placed_at,'%Y-%m')")->orderByRaw("DATE_FORMAT(placed_at,'%Y-%m')")->get();
        return $rows->map(function ($row) {
            [$year, $month] = explode('-', $row->ym);
            [$jy, $jm] = Jalali::toJalali((int) $year, (int) $month, 1);
            return ['ماه' => Jalali::monthName($jm) . ' ' . $jy, 'تعداد' => $row->c, 'مبلغ' => $row->t];
        })->all();
    }

    private function oldPaymentsByGateway(?Carbon $from, ?Carbon $to): array
    {
        $base = Payment::query()->when($from, fn($q) => $q->where('created_at', '>=', $from))->when($to, fn($q) => $q->where('created_at', '<=', $to));
        $rows = $base->selectRaw('gateway, status, COUNT(*) as count_rows, SUM(amount) as total_amount, MAX(created_at) as last_row_at')->groupBy('gateway', 'status')->orderByDesc('total_amount')->get();
        return $rows->map(fn($row) => ['درگاه' => self::GATEWAYS[$row->gateway] ?? $row->gateway, 'وضعیت' => self::PAYMENT_STATUSES[$row->status] ?? $row->status, 'تعداد' => (int) $row->count_rows, 'مبلغ کل' => (float) $row->total_amount, 'آخرین مورد' => $row->last_row_at ? Jalali::datetime(Carbon::parse($row->last_row_at)) : '—'])->all();
    }

    private function oldTaxInvoicesStatus(?Carbon $from, ?Carbon $to): array
    {
        $base = TaxInvoice::query()->when($from, fn($q) => $q->where('issued_at', '>=', $from))->when($to, fn($q) => $q->where('issued_at', '<=', $to));
        $rows = $base->selectRaw('status, COUNT(*) as count_rows, SUM(total_amount) as total_amount, SUM(vat_amount) as total_vat, SUM(payable) as total_payable, MAX(issued_at) as last_issue_at')->groupBy('status')->orderByDesc('total_payable')->get();
        return $rows->map(fn($row) => ['وضعیت' => self::TAX_STATUSES[$row->status] ?? $row->status, 'تعداد' => (int) $row->count_rows, 'جمع مبلغ' => (float) $row->total_amount, 'جمع مالیات' => (float) $row->total_vat, 'جمع قابل پرداخت' => (float) $row->total_payable, 'آخرین صدور' => $row->last_issue_at ? Jalali::datetime(Carbon::parse($row->last_issue_at)) : '—'])->all();
    }

    private function loyaltyFromSales(?Carbon $from, ?Carbon $to): array
    {
        try {
            $q = LoyaltyTransaction::query()->when($from, fn($qq) => $qq->where('created_at', '>=', $from))->when($to, fn($qq) => $qq->where('created_at', '<=', $to));
            $rows = $q->selectRaw('kind, direction, COUNT(*) as c, SUM(amount) as s')->groupBy('kind', 'direction')->get();
            return $rows->map(fn($r) => ['نوع' => $r->kind === 'point' ? 'امتیاز' : 'کیف پول', 'جهت' => $r->direction === 'credit' ? 'افزایش' : 'کاهش', 'تعداد' => (int) $r->c, 'مقدار' => (int) $r->s])->all();
        } catch (\Throwable $e) { return []; }
    }

    private function referralSales(?Carbon $from, ?Carbon $to): array
    {
        try {
            return LoyaltyReferral::with('referrerMember.customer')->limit(20)->get()->map(fn($ref) => ['معرف' => $ref->referrerMember?->customer?->full_name ?? '—','معرفی‌شده' => $ref->referredMember?->customer?->full_name ?? '—','وضعیت' => $ref->status ?? '—','تاریخ' => $ref->created_at ? Jalali::date($ref->created_at) : '—',])->all();
        } catch (\Throwable $e) { return []; }
    }

    private function ordersLoyaltyEffect(?Carbon $from, ?Carbon $to): array
    {
        try {
            $orders = $this->orderBase($from, $to)->with('customer')->whereIn('status', ['processing', 'completed'])->latest('placed_at')->limit(20)->get();
            return $orders->map(fn($o) => ['سفارش' => $o->number ?: $o->id,'مشتری' => $o->customer?->full_name ?? '—','وضعیت' => self::ORDER_STATUSES[$o->status] ?? $o->status,'مبلغ' => (float) $o->total,'تاریخ' => $o->placed_at ? Jalali::datetime($o->placed_at) : '—',])->all();
        } catch (\Throwable $e) { return []; }
    }

    public function modernExport(Request $request): StreamedResponse
    {
        $tab = $request->get('tab', $request->get('type', 'overview'));
        $range = $request->get('range', '30');
        [$from, $to] = $this->resolveRange($request, $range);
        $businessId = $this->currentBusinessId();

        $filename = 'گزارش-' . $tab . '-' . Jalali::date(Carbon::now()) . '.csv';

        return response()->stream(function () use ($tab, $from, $to, $businessId, $range) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $data = match ($tab) {
                'top' => $this->getTopCustomers($from, $to, $businessId, 500),
                'products' => $this->getTopProducts($from, $to, $businessId, 500),
                'channels' => $this->getChannels($from, $to, $businessId),
                'rfm' => $this->buildRfmAnalysis($from, $to, $businessId)['top_customers'] ?? [],
                'clv' => $this->buildClvAnalysis($businessId)['top_clv'] ?? [],
                'recovery' => $this->buildRecoveryAnalysis($from, $to, $businessId)['recovered_in_range'] ?? [],
                default => [],
            };
            if (!empty($data)) {
                $first = (array) $data[0];
                fputcsv($out, array_keys($first));
                foreach ($data as $row) fputcsv($out, (array) $row);
            } else {
                fputcsv($out, ['پیام']);
                fputcsv($out, ['برای این بازه داده‌ای وجود ندارد']);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function percentChange($old, $new): float
    {
        if ($old == 0) return $new > 0 ? 100 : 0;
        return round((($new - $old) / $old) * 100, 1);
    }

    protected function jalaliShort($dateStr, $format = 'md'): string
    {
        try {
            $date = Carbon::parse($dateStr);
            [$jy, $jm, $jd] = Jalali::toJalali($date->year, $date->month, $date->day);
            if ($format === 'ym') return Jalali::monthName($jm) . ' ' . $jy;
            return $jd . ' ' . Jalali::monthName($jm);
        } catch (\Throwable $e) {
            return (string) $dateStr;
        }
    }

    protected function currentBusinessId(): ?int
    {
        try {
            $user = Auth::user();
            if (!$user) return null;
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return null;
            return $user->business_id ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function applyBusiness($query, string $table, ?int $businessId)
    {
        try {
            if ($businessId && Schema::hasTable($table) && Schema::hasColumn($table, 'business_id')) {
                return $query->where($table . '.business_id', $businessId);
            }
        } catch (\Throwable $e) {}
        return $query;
    }
}
