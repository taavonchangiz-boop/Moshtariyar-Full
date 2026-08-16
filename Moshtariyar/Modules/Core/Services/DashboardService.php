<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Support\Num;

class DashboardService
{
    public function getFullDashboardData(): array
    {
        try {
            $stats = $this->getStatsLight();
            $advancedClv = $this->getAdvancedClvMetrics();

            return [
                'stats' => array_merge($stats, $advancedClv),
                'top_customers' => $this->getTopLight(6),
                'recent_orders' => $this->getRecentLight(6),
                'loyalty_stats' => $this->getLoyaltyLight(),
                'quick_insights' => $this->getInsightsLight(array_merge($stats, $advancedClv)),
                'command_actions' => $this->getCommandActions(array_merge($stats, $advancedClv)),
                'growth_opportunities' => $this->getGrowthOpportunities(array_merge($stats, $advancedClv)),
                'assistant_scope' => $this->getAssistantScope(),
                'system_health' => $this->getSystemHealth(array_merge($stats, $advancedClv)),
                'advanced_clv' => $advancedClv,
            ];
        } catch (\Throwable $e) {
            return $this->fallback();
        }
    }

    private function getAdvancedClvMetrics(): array
    {
        try {
            if (class_exists(\Modules\Core\Services\ClvAdvancedService::class)) {
                $service = app(\Modules\Core\Services\ClvAdvancedService::class);
                $data = $service->analyzeAll(null, 300);

                // آمار بازگشت نجات یافته - برای داشبورد لحظه‌ای
                $recovery = [
                    'recovered_30d' => 0,
                    'recovered_7d' => 0,
                    'recovered_revenue_30d' => 0,
                    'recovery_rate' => 0,
                    'recovered_list' => [],
                ];
                try {
                    if (class_exists(\Modules\Core\Services\RetentionAutomationService::class)) {
                        $retService = app(\Modules\Core\Services\RetentionAutomationService::class);
                        $recovery = $retService->getRecoveryMetrics($data);
                    }
                } catch (\Throwable $e) {}

                return [
                    'avg_clv_12m' => $data['avg_clv_12m'] ?? 0,
                    'avg_churn_probability' => $data['avg_churn_probability'] ?? 0,
                    'total_at_risk' => $data['total_at_risk'] ?? 0,
                    'total_churned' => $data['total_churned'] ?? 0,
                    'total_predicted_revenue_12m' => $data['total_predicted_revenue_12m'] ?? 0,
                    'churn_distribution' => $data['churn_distribution'] ?? [],
                    'segment_distribution' => $data['segment_distribution'] ?? [],
                    'top_churn_risk' => $data['top_churn_risk'] ?? [],
                    'recoverable_revenue' => ($data['total_at_risk'] ?? 0) > 0 ? (int)(($data['total_predicted_revenue_12m'] ?? 0) * 0.35) : 0,
                    // درآمد نجات یافته برای داشبورد
                    'recovered_30d' => $recovery['recovered_30d'] ?? 0,
                    'recovered_7d' => $recovery['recovered_7d'] ?? 0,
                    'recovered_revenue_30d' => $recovery['recovered_revenue_30d'] ?? 0,
                    'recovery_rate' => $recovery['recovery_rate'] ?? 0,
                    'recovered_list' => $recovery['recovered_list'] ?? [],
                ];
            }
        } catch (\Throwable $e) {}
        return [
            'avg_clv_12m' => 0,
            'avg_churn_probability' => 0,
            'total_at_risk' => 0,
            'total_churned' => 0,
            'total_predicted_revenue_12m' => 0,
            'churn_distribution' => [],
            'segment_distribution' => [],
            'top_churn_risk' => [],
            'recoverable_revenue' => 0,
            'recovered_30d' => 0,
            'recovered_7d' => 0,
            'recovered_revenue_30d' => 0,
            'recovery_rate' => 0,
            'recovered_list' => [],
        ];
    }

    private function getStatsLight(): array
    {
        try {
            $totalCustomers = $this->safeCountModel(Customer::class);
            $totalOrders = $this->safeCountModel(Order::class);
            $totalRevenue = $this->safeOrderRevenue();
            $activeCustomers = $this->safeActiveCustomers();
            $thisMonthRevenue = $this->safeOrderRevenue(fn ($q) => $q->where('placed_at', '>=', now()->startOfMonth()));
            $newThisMonth = $this->safeModelCountWhere(Customer::class, fn ($q) => $q->where('created_at', '>=', now()->startOfMonth()));
            $todayOrders = $this->safeModelCountWhere(Order::class, fn ($q) => $q->where('placed_at', '>=', now()->startOfDay()));
            $pendingOrders = $this->safeModelCountWhere(Order::class, fn ($q) => $q->whereIn('status', ['pending', 'on_hold', 'failed', 'processing']));
            $repeatCustomers = $this->safeRepeatCustomers();
            $openTickets = $this->safeTableCount('tickets', fn ($q) => $q->whereNotIn('status', ['closed']));
            $urgentTickets = $this->safeTableCount('tickets', fn ($q) => $q->where('priority', 'urgent')->whereNotIn('status', ['closed']));
            $overdueTickets = $this->safeTableCount('tickets', fn ($q) => $q->whereNull('first_response_at')->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->whereNotIn('status', ['closed']));
            $loyaltyMembers = $this->safeTableCount('loyalty_members');
            $activeMissions = $this->safeTableCount('loyalty_missions', fn ($q) => $q->where('is_active', 1));
            $activeCampaigns = $this->safeTableCount('loyalty_campaigns', fn ($q) => $q->where('status', 'active'));
            $proformas = $this->safeTableCount('tax_invoices', fn ($q) => $q->where('invoice_kind', 'proforma')->whereNotIn('status', ['cancelled']));
            $draftInvoices = $this->safeTableCount('tax_invoices', fn ($q) => $q->where('status', 'draft'));
            $lowStockProducts = $this->safeLowStockProducts();
            $wooErrors = $this->safeTableCount('sync_logs', fn ($q) => $q->whereIn('status', ['failed', 'error']));
            $openTasks = $this->safeTableCount('activities', fn ($q) => $q->where('type', 'task')->where('done', false));
            $overdueTasks = $this->safeTableCount('activities', fn ($q) => $q->where('type', 'task')->where('done', false)->whereNotNull('due_at')->where('due_at', '<', now()));
            $todayTasks = $this->safeTableCount('activities', fn ($q) => $q->where('type', 'task')->where('done', false)->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]));
            $activeWorkflows = $this->safeTableCount('workflows', fn ($q) => $q->where('is_active', true));
            $activeJourneys = $this->safeTableCount('journey_executions', fn ($q) => $q->where('status', 'active'));

            return [
                'total_customers' => $totalCustomers,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'active_customers' => $activeCustomers,
                'this_month_revenue' => $thisMonthRevenue,
                'new_this_month' => $newThisMonth,
                'avg_order_value' => $totalOrders > 0 ? round($totalRevenue / max($totalOrders, 1)) : 0,
                'today_orders' => $todayOrders,
                'pending_orders' => $pendingOrders,
                'churn_risk' => max(0, $totalCustomers - $activeCustomers),
                'repeat_customers' => $repeatCustomers,
                'conversion_rate' => $totalCustomers > 0 ? round(($totalOrders / max($totalCustomers, 1)) * 100) : 0,
                'open_tickets' => $openTickets,
                'urgent_tickets' => $urgentTickets,
                'overdue_tickets' => $overdueTickets,
                'loyalty_members' => $loyaltyMembers,
                'active_missions' => $activeMissions,
                'active_campaigns' => $activeCampaigns,
                'open_proformas' => $proformas,
                'draft_invoices' => $draftInvoices,
                'low_stock_products' => $lowStockProducts,
                'woo_errors' => $wooErrors,
                'open_tasks' => $openTasks,
                'overdue_tasks' => $overdueTasks,
                'today_tasks' => $todayTasks,
                'active_workflows' => $activeWorkflows,
                'active_journeys' => $activeJourneys,
            ];
        } catch (\Throwable $e) {
            return $this->baseStats();
        }
    }

    private function getTopLight(int $limit = 6)
    {
        try {
            return Customer::orderByDesc('id')->take($limit)->get()->map(function ($customer) {
                try {
                    $customer->orders_count = (int) $customer->orders()->whereIn('status', ['completed', 'processing'])->count();
                    $customer->total_spent = (float) $customer->orders()->whereIn('status', ['completed', 'processing'])->sum('total');
                } catch (\Throwable $e) {
                    $customer->orders_count = 0;
                    $customer->total_spent = 0;
                }

                return $customer;
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function getRecentLight(int $limit = 6)
    {
        try {
            return Order::with('customer')->latest('placed_at')->latest('id')->take($limit)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function getLoyaltyLight(): array
    {
        return [
            'members' => $this->safeTableCount('loyalty_members'),
            'avg_points' => $this->safeTableAvg('loyalty_members', 'points'),
            'active_missions' => $this->safeTableCount('loyalty_missions', fn ($q) => $q->where('is_active', 1)),
            'active_campaigns' => $this->safeTableCount('loyalty_campaigns', fn ($q) => $q->where('status', 'active')),
        ];
    }

    private function getInsightsLight(array $stats): array
    {
        $insights = [];

        if (($stats['overdue_tasks'] ?? 0) > 0) {
            $insights[] = 'وظیفه عقب‌افتاده دارید؛ ابتدا پیگیری‌های فوری را انجام دهید تا فرصت‌های فروش از دست نروند.';
        } elseif (($stats['open_tasks'] ?? 0) > 0) {
            $insights[] = 'برای امروز ' . Num::fa($stats['open_tasks']) . ' وظیفه باز دارید؛ مرکز پیگیری را برای مرتب‌سازی کارها باز کنید.';
        }

        if (($stats['overdue_tickets'] ?? 0) > 0) {
            $insights[] = 'تیکت خارج از مهلت دارید؛ اولویت امروز باید پاسخ‌گویی سریع باشد.';
        }

        if (($stats['pending_orders'] ?? 0) > 0) {
            $insights[] = 'چند سفارش نیازمند پیگیری وجود دارد؛ قبل از کمپین جدید، وضعیت آن‌ها را روشن کنید.';
        }

        if (($stats['churn_risk'] ?? 0) > 0) {
            $insights[] = 'بخشی از مشتریان در سی روز اخیر خرید فعال نداشته‌اند؛ کمپین بازگشت می‌تواند مؤثر باشد.';
        }

        if (($stats['active_missions'] ?? 0) === 0) {
            $insights[] = 'مأموریت فعال باشگاه کم است؛ یک مأموریت ساده برای خرید مجدد یا معرفی دوستان تعریف کنید.';
        }

        if (($stats['low_stock_products'] ?? 0) > 0) {
            $insights[] = 'برخی محصولات کم‌موجودی هستند؛ قبل از تبلیغ فروش، موجودی را بررسی کنید.';
        }

        if (! count($insights)) {
            $insights[] = 'وضعیت کلی آرام است؛ بهترین اقدام امروز تحلیل فرصت‌های رشد و فعال‌سازی مشتریان کم‌فعال است.';
        }

        return $insights;
    }

    private function getCommandActions(array $stats): array
    {
        $actions = [
            [
                'group' => 'مرکز وظایف',
                'title' => 'پیگیری وظایف باز و عقب‌افتاده',
                'description' => 'وظایف روزانه، پیگیری مشتری و کارهای ساخته‌شده توسط اتوماسیون را مرتب کنید.',
                'count' => $stats['open_tasks'] ?? 0,
                'tone' => (($stats['overdue_tasks'] ?? 0) > 0) ? 'red' : ((($stats['open_tasks'] ?? 0) > 0) ? 'purple' : 'green'),
                'url' => (($stats['overdue_tasks'] ?? 0) > 0) ? url('/app/reminders?scope=overdue') : url('/app/reminders'),
                'owner' => 'مدیریت',
            ],
            [
                'group' => 'اتوماسیون',
                'title' => 'بررسی قانون‌ها و سفرهای در حال اجرا',
                'description' => 'قانون‌های فعال و سفرهای مشتری را بررسی کنید تا عملیات خودکار بدون توقف ادامه پیدا کند.',
                'count' => ($stats['active_workflows'] ?? 0) + ($stats['active_journeys'] ?? 0),
                'tone' => (($stats['active_workflows'] ?? 0) > 0) ? 'blue' : 'warn',
                'url' => url('/app/workflows'),
                'owner' => 'عملیات',
            ],
            [
                'group' => 'نیازمند اقدام فوری',
                'title' => 'بررسی تیکت‌های خارج از مهلت',
                'description' => 'تیکت‌هایی که پاسخ اولیه نگرفته‌اند و از زمان تعهد عبور کرده‌اند.',
                'count' => $stats['overdue_tickets'] ?? 0,
                'tone' => (($stats['overdue_tickets'] ?? 0) > 0) ? 'red' : 'green',
                'url' => url('/app/tickets?overdue=1'),
                'owner' => 'پشتیبانی',
            ],
            [
                'group' => 'پیگیری فروش',
                'title' => 'رسیدگی به سفارش‌های باز',
                'description' => 'سفارش‌های در انتظار، در حال پردازش یا نیازمند بررسی.',
                'count' => $stats['pending_orders'] ?? 0,
                'tone' => (($stats['pending_orders'] ?? 0) > 0) ? 'warn' : 'green',
                'url' => url('/app/orders'),
                'owner' => 'فروش',
            ],
            [
                'group' => 'مالی و اسناد',
                'title' => 'پیگیری پیش‌فاکتورهای باز',
                'description' => 'پیش‌فاکتورهایی که می‌توانند به سفارش یا فاکتور رسمی تبدیل شوند.',
                'count' => $stats['open_proformas'] ?? 0,
                'tone' => (($stats['open_proformas'] ?? 0) > 0) ? 'blue' : 'green',
                'url' => url('/app/tax-invoices'),
                'owner' => 'مالی',
            ],
            [
                'group' => 'باشگاه مشتریان',
                'title' => 'فعال‌سازی مأموریت و پاداش',
                'description' => 'بررسی مأموریت‌های فعال، پاداش‌ها و معرفی دوستان برای افزایش خرید مجدد.',
                'count' => $stats['active_missions'] ?? 0,
                'tone' => (($stats['active_missions'] ?? 0) > 0) ? 'green' : 'warn',
                'url' => url('/app/loyalty/settings'),
                'owner' => 'باشگاه',
            ],
            [
                'group' => 'محصول و فروشگاه',
                'title' => 'بررسی موجودی و همگام‌سازی',
                'description' => 'کم‌موجودی‌ها و خطاهای اتصال فروشگاه قبل از فروش ویژه باید بررسی شوند.',
                'count' => ($stats['low_stock_products'] ?? 0) + ($stats['woo_errors'] ?? 0),
                'tone' => ((($stats['low_stock_products'] ?? 0) + ($stats['woo_errors'] ?? 0)) > 0) ? 'warn' : 'green',
                'url' => url('/app/products'),
                'owner' => 'عملیات',
            ],
        ];

        return collect($actions)->sortByDesc('count')->values()->all();
    }

    private function getGrowthOpportunities(array $stats): array
    {
        return [
            [
                'title' => 'بازگشت مشتریان کم‌فعال',
                'description' => 'مشتریانی که در سی روز اخیر خریدی نداشته‌اند، بهترین هدف برای پیام بازگشت هستند.',
                'value' => $stats['churn_risk'] ?? 0,
                'url' => url('/app/reports/rfm'),
                'tone' => 'warn',
            ],
            [
                'title' => 'افزایش خرید مجدد از باشگاه',
                'description' => 'مأموریت خرید مجدد، پاداش معرفی دوستان و کد تخفیف می‌تواند ارزش طول عمر مشتری را بالا ببرد.',
                'value' => $stats['loyalty_members'] ?? 0,
                'url' => url('/app/loyalty'),
                'tone' => 'green',
            ],
            [
                'title' => 'تبدیل پیش‌فاکتور به فروش',
                'description' => 'پیگیری پیش‌فاکتورهای باز، سریع‌ترین مسیر افزایش فروش بدون جذب مشتری جدید است.',
                'value' => $stats['open_proformas'] ?? 0,
                'url' => url('/app/tax-invoices'),
                'tone' => 'blue',
            ],
        ];
    }

    private function getAssistantScope(): array
    {
        return [
            ['title' => 'فروش و سفارش‌ها', 'description' => 'گزارش سفارش‌ها، درآمد، سفارش‌های باز و وضعیت پرداخت‌ها', 'url' => url('/app/orders')],
            ['title' => 'مشتریان و پرونده ۳۶۰', 'description' => 'تحلیل مشتری، ارزش خرید، ریزش، رفتار و اقدام بعدی', 'url' => url('/app/customers')],
            ['title' => 'پشتیبانی و تیکت‌ها', 'description' => 'تیکت‌های فوری، زمان پاسخ، مسئول پیگیری و کیفیت پشتیبانی', 'url' => url('/app/tickets')],
            ['title' => 'باشگاه مشتریان', 'description' => 'امتیاز، سطح، مأموریت، پاداش، گردونه و معرفی دوستان', 'url' => url('/app/loyalty')],
            ['title' => 'محصولات و فروشگاه', 'description' => 'محصول، موجودی، قیمت، ووکامرس و همگام‌سازی', 'url' => url('/app/products')],
            ['title' => 'مالی و فاکتورها', 'description' => 'فاکتور رسمی، پیش‌فاکتور، پیش‌نویس و اسناد مالی', 'url' => url('/app/tax-invoices')],
            ['title' => 'وظایف و پیگیری‌ها', 'description' => 'کارهای باز، عقب‌افتاده، امروز و وظایف ساخته‌شده توسط دستیار', 'url' => url('/app/reminders')],
            ['title' => 'قانون‌ها و سفرهای خودکار', 'description' => 'قانون‌های فعال، سفرهای مشتری و اقدام‌های خودکار', 'url' => url('/app/workflows')],
        ];
    }

    private function getSystemHealth(array $stats): array
    {
        $score = 92;
        $score -= min(20, (int) ($stats['overdue_tickets'] ?? 0) * 4);
        $score -= min(16, (int) ($stats['overdue_tasks'] ?? 0) * 2);
        $score -= min(14, (int) ($stats['pending_orders'] ?? 0));
        $score -= min(10, (int) ($stats['woo_errors'] ?? 0) * 2);
        $score = max(45, min(100, $score));

        return [
            'score' => $score,
            'label' => $score >= 80 ? 'مطلوب' : ($score >= 60 ? 'نیازمند توجه' : 'پرریسک'),
            'tone' => $score >= 80 ? 'ok' : ($score >= 60 ? 'warn' : 'bad'),
        ];
    }

    private function safeCountModel(string $class): int
    {
        try {
            return (int) $class::count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeModelCountWhere(string $class, callable $callback): int
    {
        try {
            $query = $class::query();
            $callback($query);

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeOrderRevenue(?callable $callback = null): float
    {
        try {
            $query = Order::whereIn('status', ['completed', 'processing']);

            if ($callback) {
                $callback($query);
            }

            return (float) $query->sum('total');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeActiveCustomers(): int
    {
        try {
            return (int) Customer::whereHas(
                'orders',
                fn ($q) => $q
                    ->whereIn('status', ['completed', 'processing'])
                    ->where('placed_at', '>=', now()->subDays(30))
            )->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeRepeatCustomers(): int
    {
        try {
            return (int) DB::table('orders')
                ->select('customer_id')
                ->whereNotNull('customer_id')
                ->whereIn('status', ['completed', 'processing'])
                ->groupBy('customer_id')
                ->havingRaw('COUNT(*) > 1')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeLowStockProducts(): int
    {
        try {
            if (! Schema::hasTable('products')) {
                return 0;
            }

            if (! Schema::hasColumn('products', 'stock') || ! Schema::hasColumn('products', 'min_stock')) {
                return 0;
            }

            return (int) DB::table('products')
                ->whereNotNull('stock')
                ->whereColumn('stock', '<=', 'min_stock')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeTableCount(string $table, ?callable $callback = null): int
    {
        try {
            if (! Schema::hasTable($table)) {
                return 0;
            }

            $query = DB::table($table);

            if ($callback) {
                $callback($query);
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeTableAvg(string $table, string $column): int
    {
        try {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                return 0;
            }

            return (int) round((float) DB::table($table)->avg($column));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function baseStats(): array
    {
        return [
            'total_customers' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'active_customers' => 0,
            'this_month_revenue' => 0,
            'new_this_month' => 0,
            'avg_order_value' => 0,
            'today_orders' => 0,
            'pending_orders' => 0,
            'churn_risk' => 0,
            'repeat_customers' => 0,
            'conversion_rate' => 0,
            'open_tickets' => 0,
            'urgent_tickets' => 0,
            'overdue_tickets' => 0,
            'loyalty_members' => 0,
            'active_missions' => 0,
            'active_campaigns' => 0,
            'open_proformas' => 0,
            'draft_invoices' => 0,
            'low_stock_products' => 0,
            'woo_errors' => 0,
            'open_tasks' => 0,
            'overdue_tasks' => 0,
            'today_tasks' => 0,
            'active_workflows' => 0,
            'active_journeys' => 0,
            // ویجت درآمد نجات یافته - حتی اگر صفر باشد نشان داده شود
            'avg_clv_12m' => 0,
            'avg_churn_probability' => 0,
            'total_at_risk' => 0,
            'total_churned' => 0,
            'total_predicted_revenue_12m' => 0,
            'recoverable_revenue' => 0,
            'recovered_30d' => 0,
            'recovered_7d' => 0,
            'recovered_revenue_30d' => 0,
            'recovery_rate' => 0,
            'recovered_list' => [],
            'top_churn_risk' => [],
        ];
    }

    private function fallback(): array
    {
        $stats = $this->baseStats();

        return [
            'stats' => $stats,
            'top_customers' => collect(),
            'recent_orders' => collect(),
            'loyalty_stats' => [
                'members' => 0,
                'avg_points' => 0,
                'active_missions' => 0,
                'active_campaigns' => 0,
            ],
            'quick_insights' => ['داده کافی برای تحلیل موجود نیست.'],
            'command_actions' => [],
            'growth_opportunities' => [],
            'assistant_scope' => $this->getAssistantScope(),
            'system_health' => [
                'score' => 75,
                'label' => 'در حال بررسی',
                'tone' => 'warn',
            ],
        ];
    }

    /** 
     * داده‌های نمودارهای داشبورد
     */
    public function getChartData(): array
    {
        try {
            return [
                'monthly_sales' => $this->getMonthlySalesData(),
                'orders_by_status' => $this->getOrdersByStatusData(),
                'customer_growth' => $this->getCustomerGrowthData(),
            ];
        } catch (\Throwable $e) {
            return [
                'monthly_sales' => ['labels' => [], 'values' => []],
                'orders_by_status' => ['labels' => [], 'values' => [], 'colors' => []],
                'customer_growth' => ['labels' => [], 'values' => []],
            ];
        }
    }

    private function getMonthlySalesData(): array
    {
        $labels = [];
        $values = [];
        $monthNames = [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ];

        // تاریخ شمسی امروز برای ۱۲ ماه اخیر یونیک
        try {
            $nowJalali = \Modules\Core\Support\Jalali::toJalali((int)now()->format('Y'), (int)now()->format('n'), (int)now()->format('j'));
            $curJy = $nowJalali[0];
            $curJm = $nowJalali[1];
        } catch (\Throwable $e) {
            $curJy = 1404; $curJm = 5;
        }

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
                $label = $monthNames[$jm-1] . ' ' . \Modules\Core\Support\Num::fa($jy);
            } catch (\Throwable $e) {
                $label = $monthNames[$jm-1] ?? 'ماه';
            }
            $labels[] = $label;

            try {
                [$gy1, $gm1, $gd1] = \Modules\Core\Support\Jalali::toGregorian($jy, $jm, 1);
                $start = \Carbon\Carbon::createFromDate($gy1, $gm1, $gd1)->startOfDay();
                $nextJy = $jy; $nextJm = $jm + 1;
                if ($nextJm > 12) { $nextJm = 1; $nextJy++; }
                [$gy2, $gm2, $gd2] = \Modules\Core\Support\Jalali::toGregorian($nextJy, $nextJm, 1);
                $end = \Carbon\Carbon::createFromDate($gy2, $gm2, $gd2)->subDay()->endOfDay();

                $val = (float) DB::table('orders')
                    ->whereIn('status', ['completed', 'processing'])
                    ->whereBetween('placed_at', [$start, $end])
                    ->sum('total');
            } catch (\Throwable $e) {
                $val = 0;
            }
            $values[] = $val;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function getCustomerGrowthData(): array
    {
        $labels = [];
        $values = [];
        $monthNames = [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ];

        try {
            $nowJalali = \Modules\Core\Support\Jalali::toJalali((int)now()->format('Y'), (int)now()->format('n'), (int)now()->format('j'));
            $curJy = $nowJalali[0];
            $curJm = $nowJalali[1];
        } catch (\Throwable $e) {
            $curJy = 1404; $curJm = 5;
        }

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
                $label = $monthNames[$jm-1] . ' ' . \Modules\Core\Support\Num::fa($jy);
            } catch (\Throwable $e) {
                $label = $monthNames[$jm-1] ?? 'ماه';
            }
            $labels[] = $label;

            try {
                [$gy1, $gm1, $gd1] = \Modules\Core\Support\Jalali::toGregorian($jy, $jm, 1);
                $start = \Carbon\Carbon::createFromDate($gy1, $gm1, $gd1)->startOfDay();
                $nextJy = $jy; $nextJm = $jm + 1;
                if ($nextJm > 12) { $nextJm = 1; $nextJy++; }
                [$gy2, $gm2, $gd2] = \Modules\Core\Support\Jalali::toGregorian($nextJy, $nextJm, 1);
                $end = \Carbon\Carbon::createFromDate($gy2, $gm2, $gd2)->subDay()->endOfDay();

                $val = (int) DB::table('customers')
                    ->where('created_at', '<=', $end)
                    ->count();
            } catch (\Throwable $e) {
                $val = 0;
            }
            $values[] = $val;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}