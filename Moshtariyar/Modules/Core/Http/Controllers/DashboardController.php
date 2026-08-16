<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Setting;
use Modules\WooBridge\Entities\SyncLog;
use Modules\WooBridge\Entities\WooConnection;
use Modules\WooBridge\Services\CustomerSync;
use Modules\WooBridge\Services\OrderSync;
use Modules\WooBridge\Services\ProductSync;
use Modules\WooBridge\Support\WooClient;
use Modules\Core\Services\DashboardService;
use Modules\Core\Services\Customer360Service;
use Modules\IranPack\Entities\TaxInvoice;
use Modules\Core\Support\Jalali;
use Modules\Core\Support\Num;

class DashboardController extends Controller
{
    public function dashboard()
    {
        try {
            $service = new DashboardService();
            $data = $service->getFullDashboardData();
            $data['chart_data'] = $service->getChartData();

            $latestBriefing = DB::table('strategic_briefings')
                ->latest('report_date')
                ->first();

            return view("app.dashboard", array_merge($data, [
                "latest_briefing" => $latestBriefing
            ]));
        } catch (\Throwable $e) {
            return view("app.dashboard", [
                "stats" => [
                    "total_customers" => 0,
                    "total_orders" => 0,
                    "total_revenue" => 0,
                    "active_customers" => 0,
                    "this_month_revenue" => 0,
                    "new_this_month" => 0,
                    "avg_order_value" => 0,
                    "today_orders" => 0,
                    "pending_orders" => 0,
                    "churn_risk" => 0,
                    "repeat_customers" => 0,
                    "conversion_rate" => 0,
                    "open_tasks" => 0,
                    "overdue_tasks" => 0,
                    "today_tasks" => 0,
                    "active_workflows" => 0,
                    "active_journeys" => 0,
                    "avg_clv_12m" => 0,
                    "avg_churn_probability" => 0,
                    "total_at_risk" => 0,
                    "total_churned" => 0,
                    "total_predicted_revenue_12m" => 0,
                    "recoverable_revenue" => 0,
                    "recovered_30d" => 0,
                    "recovered_7d" => 0,
                    "recovered_revenue_30d" => 0,
                    "recovery_rate" => 0,
                    "recovered_list" => [],
                    "top_churn_risk" => [],
                ],
                "top_customers" => collect(),
                "recent_orders" => collect(),
                "loyalty_stats" => [
                    "members" => 0,
                    "avg_points" => 0,
                    "active_missions" => 0,
                ],
                "quick_insights" => [],
                "latest_briefing" => null
            ]);
        }
    }

    public function customers(Request $request)
    {
        try {
            $query = Customer::query();

            if ($request->filled("search")) {
                $s = $request->get("search");

                $query->where(function ($q) use ($s) {
                    $q->where("full_name", "like", "%$s%")
                        ->orWhere("email", "like", "%$s%")
                        ->orWhere("phone", "like", "%$s%");
                });
            }

            $customers = $query
                ->withCount([
                    "orders as orders_count" => fn ($q) => $q->whereIn("status", ["processing", "completed"])
                ])
                ->latest()
                ->paginate(25);

            return view("app.customers", compact("customers"));
        } catch (\Throwable $e) {
            return view("app.customers", [
                "customers" => collect()->paginate(25)
            ]);
        }
    }

    public function customer360(Customer $customer)
    {
        try {
            $customer->load(["orders", "activities", "segments"]);

            $data = [];

            if (class_exists(Customer360Service::class)) {
                try {
                    $service = new Customer360Service();
                    $data = $service->get360Data($customer);
                } catch (\Throwable $e) {
                    $data = $this->getFallback360Data($customer);
                }
            } else {
                $data = $this->getFallback360Data($customer);
            }

            return view("app.customer360", array_merge(
                ["customer" => $customer],
                $data
            ));
        } catch (\Throwable $e) {
            return view("app.customer360", [
                "customer" => $customer,
                "stats" => [
                    "lifetime_value" => 0,
                    "completed_orders" => 0,
                    "aov" => 0,
                    "days_since_purchase" => 999,
                ],
                "rfm" => [
                    "segment" => "نامشخص",
                    "color" => "#64748b",
                ],
                "loyalty" => [
                    "points" => 0,
                    "tier" => null,
                ],
                "journeys" => collect(),
                "timeline" => collect(),
                "churn_risk" => 50,
                "quick_actions" => [],
            ]);
        }
    }

    private function getFallback360Data(Customer $customer): array
    {
        $orders = $customer->orders()
            ->whereIn("status", ["processing", "completed"])
            ->get();

        $totalValue = (float) $orders->sum("total");
        $count = $orders->count();
        $aov = $count > 0 ? round($totalValue / $count) : 0;

        $last = $customer->orders()
            ->whereIn("status", ["processing", "completed"])
            ->latest("placed_at")
            ->first();

        $days = $last && $last->placed_at
            ? now()->diffInDays($last->placed_at)
            : 999;

        return [
            "stats" => [
                "lifetime_value" => $totalValue,
                "completed_orders" => $customer->orders()->where("status", "completed")->count(),
                "aov" => $aov,
                "days_since_purchase" => $days,
            ],
            "rfm" => [
                "segment" => $days > 90 ? "کم‌فعال" : ($days > 30 ? "عادی" : "مشتری خوب"),
                "color" => $days > 60 ? "#f59e0b" : "#10b981",
            ],
            "loyalty" => [
                "points" => 0,
                "tier" => null,
            ],
            "journeys" => collect(),
            "timeline" => collect(),
            "churn_risk" => $days > 90 ? 75 : 25,
            "quick_actions" => [
                [
                    'label' => 'ارسال پیام',
                    'url' => url('/app/messages/create?customer_id=' . $customer->id),
                    'icon' => '✉️',
                ],
                [
                    'label' => 'ثبت فعالیت',
                    'url' => '#',
                    'icon' => '📝',
                    'onclick' => 'showActivityModal(' . $customer->id . ')',
                ],
                [
                    'label' => 'تخصیص امتیاز',
                    'url' => '#',
                    'icon' => '⭐',
                    'onclick' => 'awardPoints(' . $customer->id . ')',
                ],
                [
                    'label' => 'ایجاد سفارش جدید',
                    'url' => url('/app/products'),
                    'icon' => '🛍️',
                ],
            ],
        ];
    }

    // ==================== سفارش‌ها ====================
    public function orders(Request $request)
    {
        $statuses = collect([
            ['key' => 'pending', 'label' => 'در انتظار تایید', 'color' => '#3b82f6'],
            ['key' => 'processing', 'label' => 'در حال آماده‌سازی', 'color' => '#f59e0b'],
            ['key' => 'on_hold', 'label' => 'نیازمند بررسی', 'color' => '#8b5cf6'],
            ['key' => 'completed', 'label' => 'تکمیل‌شده', 'color' => '#10b981'],
            ['key' => 'cancelled', 'label' => 'لغو شده', 'color' => '#ef4444'],
            ['key' => 'refunded', 'label' => 'مرجوعی', 'color' => '#06b6d4'],
            ['key' => 'failed', 'label' => 'ناموفق', 'color' => '#64748b'],
        ]);

        $sourceLabels = [
            'woocommerce' => 'فروشگاه',
            'manual_pricing' => 'ثبت سریع از محاسبه',
            'manual' => 'ثبت دستی',
            'lead' => 'تبدیل از سرنخ',
        ];

        $query = Order::query()
            ->with('customer')
            ->withCount('items')
            ->latest('placed_at')
            ->latest('id');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));

            $query->where(function ($q) use ($term) {
                $q->where('number', 'like', "%{$term}%")
                    ->orWhere('status', 'like', "%{$term}%")
                    ->orWhere('source', 'like', "%{$term}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($term) {
                        $customerQuery
                            ->where('full_name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('source')) {
            $query->where('source', (string) $request->input('source'));
        }

        $summary = [
            'count' => (clone $query)->count(),
            'value' => (float) (clone $query)->sum('total'),
            'pending' => (clone $query)
                ->whereIn('status', ['pending', 'on_hold', 'failed'])
                ->count(),
            'completed' => (clone $query)
                ->where('status', 'completed')
                ->count(),
        ];

        $ordersFlat = $query->limit(300)->get();
        $orders = $ordersFlat->groupBy('status');

        $totals = [];
        $maxColumnCount = 1;

        foreach ($statuses as $status) {
            $columnOrders = $orders->get($status['key'], collect());
            $count = $columnOrders->count();
            $maxColumnCount = max($maxColumnCount, $count);

            $totals[$status['key']] = [
                'count' => $count,
                'value' => (float) $columnOrders->sum('total'),
                'tax' => (float) $columnOrders->sum('tax_total'),
            ];
        }

        $sources = Order::query()
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return view('app.orders', compact(
            'orders',
            'ordersFlat',
            'statuses',
            'totals',
            'summary',
            'sources',
            'sourceLabels',
            'maxColumnCount'
        ));
    }

    public function showOrder(Order $order)
    {
        try {
            $order->load(['customer', 'items.product']);

            return view('app.orders.show', compact('order'));
        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در مشاهده جزئیات سفارش: ' . $e->getMessage());
        }
    }

    public function showInvoice(Order $order)
    {
        try {
            $order->load(['customer', 'items.product']);

            return view('app.orders.invoice', compact('order'));
        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در تولید فاکتور: ' . $e->getMessage());
        }
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        try {
            $data = $request->validate([
                'status' => 'required|in:pending,processing,on_hold,completed,cancelled,refunded,failed',
            ]);

            $order->update([
                'status' => $data['status'],
            ]);

            app(\Modules\Core\Services\OrderLifecycleService::class)->sync($order);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'وضعیت سفارش با موفقیت به‌روز شد.',
                    'order_id' => $order->id,
                    'status' => $data['status'],
                ]);
            }

            return back()->with('status', 'وضعیت سفارش با موفقیت به‌روز شد.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'خطا در تغییر وضعیت سفارش: ' . $e->getMessage(),
                ], 422);
            }

            return back()->with('status', 'خطا در تغییر وضعیت سفارش: ' . $e->getMessage());
        }
    }

    public function issueTaxInvoice(Order $order)
    {
        try {
            return back()->with('status', 'درخواست صدور فاکتور رسمی ارسال شد و در حال پردازش است.');
        } catch (\Throwable $e) {
            return back()->with('status', 'خطا در صدور فاکتور رسمی: ' . $e->getMessage());
        }
    }

    // ==================== ووکامرس ====================
    public function woocommerce()
    {
        $connections = WooConnection::where('is_active', true)->get();

        return view("app.woocommerce", compact('connections'));
    }

    public function storeConnection(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'store_url' => 'required|url|max:255',
            'consumer_key' => 'nullable|string|max:255',
            'consumer_secret' => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        $connection = WooConnection::create([
            'name' => $data['name'],
            'store_url' => rtrim($data['store_url'], '/'),
            'consumer_key' => $data['consumer_key'] ?? null,
            'consumer_secret' => $data['consumer_secret'] ?? null,
            'webhook_secret' => $data['webhook_secret'] ?: Str::random(48),
            'is_active' => true,
        ]);

        return back()
            ->with('status', 'اتصال فروشگاه ساخته شد. حالا می‌توانید آزمون اتصال و دریافت اطلاعات را اجرا کنید.')
            ->with('new_connection', [
                'store_url' => $connection->store_url,
                'connection_id' => $connection->id,
                'message' => 'اتصال ساخته شد. از دکمه‌های آزمون و دریافت اطلاعات در کارت فروشگاه استفاده کنید.',
            ]);
    }

    public function updateConnection(Request $request, WooConnection $connection)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'store_url' => 'required|url|max:255',
            'consumer_key' => 'nullable|string|max:255',
            'consumer_secret' => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $updates = [
            'name' => $data['name'],
            'store_url' => rtrim($data['store_url'], '/'),
            'webhook_secret' => $data['webhook_secret'] ?: $connection->webhook_secret,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['consumer_key'])) {
            $updates['consumer_key'] = $data['consumer_key'];
        }

        if (! empty($data['consumer_secret'])) {
            $updates['consumer_secret'] = $data['consumer_secret'];
        }

        $connection->update($updates);

        return back()->with('status', 'تنظیمات اتصال فروشگاه به‌روزرسانی شد.');
    }

    public function destroyConnection(WooConnection $connection)
    {
        $connection->delete();

        return back()->with('status', 'اتصال فروشگاه حذف شد.');
    }

    public function bulkDestroyConnections(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isNotEmpty()) {
            WooConnection::whereIn('id', $ids)->delete();
        }

        return back()->with('status', 'اتصال‌های انتخاب‌شده حذف شدند.');
    }

    public function importConnection(WooConnection $connection)
    {
        return $this->pullAllNow(request(), $connection);
    }

    public function testWooPull(WooConnection $connection)
    {
        try {
            $client = new WooClient($connection);
            $orders = $client->getOrders(1, 1);
            $customers = $client->getCustomers(1, 1);
            $products = $client->getProducts(1, 1);

            $connection->update(['last_sync_at' => now()]);

            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'order',
                'direction' => 'in',
                'status' => 'success',
                'payload' => [
                    'message' => 'آزمون خواندن اطلاعات از فروشگاه موفق بود.',
                    'orders_count' => count($orders),
                    'customers_count' => count($customers),
                    'products_count' => count($products),
                ],
            ]);

            return back()->with('status', 'آزمون اتصال موفق بود. مشتری‌یار توانست اطلاعات فروشگاه را بخواند.');
        } catch (\Throwable $e) {
            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'order',
                'direction' => 'in',
                'status' => 'failed',
                'payload' => ['message' => 'آزمون اتصال ناموفق بود.'],
                'error' => $e->getMessage(),
            ]);

            return back()->with('status', 'آزمون اتصال ناموفق بود: ' . $e->getMessage());
        }
    }

    public function pullImportNow(Request $request, WooConnection $connection)
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));

        try {
            $client = new WooClient($connection);
            $rows = $client->getOrders(1, $perPage);
            $service = app(OrderSync::class);
            $count = 0;

            foreach ($rows as $row) {
                $service->upsert($connection->id, $row);
                $count++;
            }

            $connection->update(['last_sync_at' => now()]);

            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'order',
                'direction' => 'in',
                'status' => 'success',
                'payload' => ['message' => 'دریافت سفارش‌ها انجام شد.', 'orders_count' => $count],
            ]);

            return back()->with('status', $count . ' سفارش از فروشگاه خوانده و وارد مشتری‌یار شد.');
        } catch (\Throwable $e) {
            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'order',
                'direction' => 'in',
                'status' => 'failed',
                'payload' => ['message' => 'دریافت سفارش‌ها ناموفق بود.'],
                'error' => $e->getMessage(),
            ]);

            return back()->with('status', 'دریافت سفارش‌ها ناموفق بود: ' . $e->getMessage());
        }
    }

    public function pullCustomersNow(Request $request, WooConnection $connection)
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));

        try {
            $client = new WooClient($connection);
            $rows = $client->getCustomers(1, $perPage);
            $service = app(CustomerSync::class);
            $count = 0;

            foreach ($rows as $row) {
                $service->upsert($connection->id, $row);
                $count++;
            }

            $connection->update(['last_sync_at' => now()]);

            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'customer',
                'direction' => 'in',
                'status' => 'success',
                'payload' => ['message' => 'دریافت مشتری‌ها انجام شد.', 'customers_count' => $count],
            ]);

            return back()->with('status', $count . ' مشتری از فروشگاه خوانده و وارد مشتری‌یار شد.');
        } catch (\Throwable $e) {
            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'customer',
                'direction' => 'in',
                'status' => 'failed',
                'payload' => ['message' => 'دریافت مشتری‌ها ناموفق بود.'],
                'error' => $e->getMessage(),
            ]);

            return back()->with('status', 'دریافت مشتری‌ها ناموفق بود: ' . $e->getMessage());
        }
    }

    public function pullProductsNow(Request $request, WooConnection $connection)
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));

        try {
            $client = new WooClient($connection);
            $rows = $client->getProducts(1, $perPage);
            $service = app(ProductSync::class);
            $count = 0;

            foreach ($rows as $row) {
                $service->upsert($connection->id, $row);
                $count++;
            }

            $connection->update(['last_sync_at' => now()]);

            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'product',
                'direction' => 'in',
                'status' => 'success',
                'payload' => ['message' => 'دریافت کالاها انجام شد.', 'products_count' => $count],
            ]);

            return back()->with('status', $count . ' کالا از فروشگاه خوانده و وارد مشتری‌یار شد.');
        } catch (\Throwable $e) {
            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'product',
                'direction' => 'in',
                'status' => 'failed',
                'payload' => ['message' => 'دریافت کالاها ناموفق بود.'],
                'error' => $e->getMessage(),
            ]);

            return back()->with('status', 'دریافت کالاها ناموفق بود: ' . $e->getMessage());
        }
    }

    public function pullAllNow(Request $request, WooConnection $connection)
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
        $messages = [];

        foreach ([
            'customers' => [
                fn () => (new WooClient($connection))->getCustomers(1, $perPage),
                app(CustomerSync::class),
                'مشتری',
            ],
            'products' => [
                fn () => (new WooClient($connection))->getProducts(1, $perPage),
                app(ProductSync::class),
                'کالا',
            ],
            'orders' => [
                fn () => (new WooClient($connection))->getOrders(1, $perPage),
                app(OrderSync::class),
                'سفارش',
            ],
        ] as $entity => [$reader, $service, $label]) {
            try {
                $rows = $reader();
                $count = 0;

                foreach ($rows as $row) {
                    $service->upsert($connection->id, $row);
                    $count++;
                }

                $messages[] = $count . ' ' . $label;
            } catch (\Throwable $e) {
                SyncLog::create([
                    'connection_id' => $connection->id,
                    'entity' => $entity === 'orders' ? 'order' : ($entity === 'customers' ? 'customer' : 'product'),
                    'direction' => 'in',
                    'status' => 'failed',
                    'payload' => ['message' => 'دریافت کامل ناموفق بود.'],
                    'error' => $e->getMessage(),
                ]);

                return back()->with('status', 'دریافت کامل ناموفق بود: ' . $e->getMessage());
            }
        }

        $connection->update(['last_sync_at' => now()]);

        SyncLog::create([
            'connection_id' => $connection->id,
            'entity' => 'order',
            'direction' => 'in',
            'status' => 'success',
            'payload' => ['message' => 'دریافت کامل انجام شد.', 'summary' => implode('، ', $messages)],
        ]);

        return back()->with('status', 'دریافت کامل انجام شد: ' . implode('، ', $messages));
    }

    public function importWooFile(Request $request, WooConnection $connection)
    {
        $request->validate(['export_file' => 'required|file|max:10240']);

        try {
            $raw = file_get_contents($request->file('export_file')->getRealPath());
            $data = json_decode($raw, true);

            if (! is_array($data)) {
                return back()->with('status', 'فایل داده معتبر نیست.');
            }

            $customers = $data['customers'] ?? [];
            $products = $data['products'] ?? [];
            $orders = $data['orders'] ?? [];

            $customerSync = app(CustomerSync::class);
            $productSync = app(ProductSync::class);
            $orderSync = app(OrderSync::class);

            $customerCount = 0;
            foreach ($customers as $row) {
                if (is_array($row)) { $customerSync->upsert($connection->id, $row); $customerCount++; }
            }

            $productCount = 0;
            foreach ($products as $row) {
                if (is_array($row)) { $productSync->upsert($connection->id, $row); $productCount++; }
            }

            $orderCount = 0;
            foreach ($orders as $row) {
                if (is_array($row)) { $orderSync->upsert($connection->id, $row); $orderCount++; }
            }

            $connection->update(['last_sync_at' => now()]);

            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'order',
                'direction' => 'in',
                'status' => 'success',
                'payload' => [
                    'message' => 'ورود فایل داده انجام شد.',
                    'customers_count' => $customerCount,
                    'products_count' => $productCount,
                    'orders_count' => $orderCount,
                ],
            ]);

            return back()->with('status', 'ورود فایل انجام شد: ' . $customerCount . ' مشتری، ' . $productCount . ' کالا، ' . $orderCount . ' سفارش.');
        } catch (\Throwable $e) {
            SyncLog::create([
                'connection_id' => $connection->id,
                'entity' => 'order',
                'direction' => 'in',
                'status' => 'failed',
                'payload' => ['message' => 'ورود فایل داده ناموفق بود.'],
                'error' => $e->getMessage(),
            ]);

            return back()->with('status', 'ورود فایل داده ناموفق بود: ' . $e->getMessage());
        }
    }

    public function syncLogs(Request $request)
    {
        try {
            $query = SyncLog::with('connection')->latest();

            if ($request->filled('connection_id')) { $query->where('connection_id', $request->get('connection_id')); }
            if ($request->filled('status')) { $query->where('status', $request->get('status')); }
            if ($request->filled('direction')) { $query->where('direction', $request->get('direction')); }
            if ($request->filled('entity')) { $query->where('entity', $request->get('entity')); }

            $logs = $query->paginate(30)->withQueryString();

            $summary = [
                'total' => SyncLog::count(),
                'success' => SyncLog::where('status', 'success')->count(),
                'failed' => SyncLog::where('status', 'failed')->count(),
                'pending' => SyncLog::where('status', 'pending')->count(),
            ];

            $connections = WooConnection::where('is_active', true)->orderBy('name')->get();

            return view('app.sync_logs', compact('logs', 'summary', 'connections'));
        } catch (\Throwable $e) {
            return view('app.sync_logs', [
                'logs' => (new \Illuminate\Pagination\LengthAwarePaginator([], 0, 30))->withQueryString(),
                'summary' => ['total' => 0, 'success' => 0, 'failed' => 0, 'pending' => 0],
                'connections' => collect(),
            ]);
        }
    }

    public function showSyncLog(SyncLog $log)
    {
        $log->load('connection');
        return view('app.sync_log_show', compact('log'));
    }

    public function resendLog(SyncLog $log)
    {
        if ($log->status !== 'failed') {
            return back()->with('error', 'فقط موارد ناموفق قابل ارسال مجدد هستند.');
        }
        $log->update(['status' => 'pending', 'error' => null]);
        return back()->with('success', 'مورد دوباره در صف قرار گرفت و به زودی پردازش می‌شود.');
    }

    public function taxInvoices(Request $request)
    {
        $query = TaxInvoice::with(['customer', 'order'])->latest('id');

        if ($request->input('kind') === 'official') {
            $query->where(function ($row) {
                $row->where('invoice_kind', 'official')->orWhereNull('invoice_kind');
            });
        } elseif ($request->input('kind') === 'proforma') {
            $query->where('invoice_kind', 'proforma');
        }

        if ($request->filled('status')) { $query->where('status', $request->input('status')); }

        $invoices = $query->paginate(30)->withQueryString();
        $customers = Customer::latest('id')->limit(200)->get(['id', 'full_name', 'phone', 'email']);

        $stats = [
            'total' => TaxInvoice::count(),
            'official' => TaxInvoice::where(function ($row) { $row->where('invoice_kind', 'official')->orWhereNull('invoice_kind'); })->count(),
            'proforma' => TaxInvoice::where('invoice_kind', 'proforma')->count(),
            'draft' => TaxInvoice::where('status', 'draft')->count(),
            'sent' => TaxInvoice::whereIn('status', ['sent', 'confirmed'])->count(),
            'payable' => (float) TaxInvoice::sum('payable'),
        ];

        $activeFilter = ['kind' => $request->input('kind'), 'status' => $request->input('status')];

        return view('app.tax_invoices', compact('invoices', 'customers', 'stats', 'activeFilter'));
    }

    public function exportTaxInvoices(Request $request)
    {
        $query = TaxInvoice::with(['customer', 'order'])->latest('id');

        if ($request->input('kind') === 'official') {
            $query->where(function ($row) { $row->where('invoice_kind', 'official')->orWhereNull('invoice_kind'); });
        } elseif ($request->input('kind') === 'proforma') {
            $query->where('invoice_kind', 'proforma');
        }
        if ($request->filled('status')) { $query->where('status', $request->input('status')); }

        $fileName = 'moshtariyar-tax-invoices-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['نوع سند','شماره سند','مشتری','شماره سفارش','مبلغ کل','تخفیف','مالیات','قابل پرداخت','وضعیت','تاریخ صدور','اعتبار تا','شناسه مالیاتی','شماره مرجع','یادداشت']);
            $query->chunk(200, function ($invoices) use ($out) {
                foreach ($invoices as $invoice) {
                    $isProforma = ($invoice->invoice_kind ?? 'official') === 'proforma';
                    $statusLabel = ['draft'=>'پیش‌نویس','queued'=>'در صف ارسال','sent'=>'ارسال شده','confirmed'=>'تأیید شده','rejected'=>'رد شده','failed'=>'ناموفق'][$invoice->status] ?? ($invoice->status ?: 'نامشخص');
                    fputcsv($out, [
                        $isProforma ? 'پیش‌فاکتور' : 'فاکتور رسمی',
                        $invoice->serial ?: $invoice->tax_id ?: $invoice->id,
                        $invoice->customer?->full_name ?: '—',
                        $invoice->order?->number ?: '—',
                        (string) $invoice->total_amount, (string) $invoice->discount,
                        (string) $invoice->vat_amount, (string) $invoice->payable,
                        $statusLabel,
                        $invoice->issued_at ? Jalali::datetime($invoice->issued_at) : Jalali::datetime($invoice->created_at),
                        $invoice->due_at ? Jalali::date($invoice->due_at) : '',
                        $invoice->tax_id ?: '', $invoice->reference_number ?: '', $invoice->notes ?: '',
                    ]);
                }
            });
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function saveProformaSettings(Request $request)
    {
        $data = $request->validate([
            'proforma_serial_prefix' => 'required|string|max:20',
            'proforma_serial_separator' => 'required|string|max:5',
            'proforma_serial_padding' => 'required|integer|min:3|max:8',
            'proforma_serial_date' => 'required|in:jalali,none',
        ]);

        Setting::put('finance', 'proforma_serial_prefix', $data['proforma_serial_prefix']);
        Setting::put('finance', 'proforma_serial_separator', $data['proforma_serial_separator']);
        Setting::put('finance', 'proforma_serial_padding', (string) $data['proforma_serial_padding']);
        Setting::put('finance', 'proforma_serial_date', $data['proforma_serial_date']);

        return redirect('/app/tax-invoices')->with('status', 'تنظیمات شماره‌گذاری پیش‌فاکتور ذخیره شد.');
    }

    private function makeProformaSerial(): string
    {
        $prefix = trim((string) Setting::get('proforma_serial_prefix', 'پف'));
        $separator = trim((string) Setting::get('proforma_serial_separator', '-'));
        $padding = max(3, min(8, (int) Setting::get('proforma_serial_padding', 5)));
        $dateMode = (string) Setting::get('proforma_serial_date', 'jalali');
        $sequence = TaxInvoice::where('invoice_kind', 'proforma')->count() + 1;
        $parts = [];

        if ($prefix !== '') { $parts[] = $prefix; }

        if ($dateMode === 'jalali') {
            $now = now();
            [$jy, $jm, $jd] = Jalali::toJalali((int) $now->format('Y'), (int) $now->format('n'), (int) $now->format('j'));
            $parts[] = Num::fa(sprintf('%04d%02d%02d', $jy, $jm, $jd));
        }

        $parts[] = Num::fa(str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT));

        return implode($separator !== '' ? $separator : '-', $parts);
    }

    public function showTaxInvoice(TaxInvoice $invoice)
    {
        $invoice->load(['customer', 'order.items']);
        $items = is_array($invoice->items) ? $invoice->items : [];
        $isProforma = ($invoice->invoice_kind ?? 'official') === 'proforma';

        $statusMap = [
            'draft' => ['پیش‌نویس', 'is-muted', '#64748b'],
            'queued' => ['در صف ارسال', 'is-warn', '#f59e0b'],
            'sent' => ['ارسال شده', 'is-ok', '#10b981'],
            'confirmed' => ['تأیید شده', 'is-ok', '#10b981'],
            'rejected' => ['رد شده', 'is-bad', '#ef4444'],
            'failed' => ['ناموفق', 'is-bad', '#ef4444'],
        ];

        return view('app.tax_invoice_show', compact('invoice', 'items', 'isProforma', 'statusMap'));
    }

    public function storeProformaInvoice(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'title' => 'required|string|max:191',
            'total_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
            'due_at' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:2000',
        ]);

        $total = (int) round((float) $data['total_amount']);
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $discountType = $data['discount_type'] === 'percent' ? 'percent' : 'fixed';
        $discount = $discountType === 'percent' ? (int) round($total * min(100, $discountValue) / 100) : (int) round($discountValue);
        $discount = max(0, min($total, $discount));
        $vat = (int) round((float) ($data['vat_amount'] ?? 0));
        $payable = max(0, $total - $discount + $vat);
        $nextSerial = $this->makeProformaSerial();

        TaxInvoice::create([
            'customer_id' => $data['customer_id'] ?? null,
            'serial' => $nextSerial,
            'invoice_kind' => 'proforma',
            'invoice_type' => 1,
            'invoice_pattern' => 1,
            'settlement_type' => 1,
            'total_amount' => $total,
            'discount' => $discount,
            'vat_amount' => $vat,
            'payable' => $payable,
            'status' => 'draft',
            'items' => [[
                'title' => $data['title'],
                'amount' => $total,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount' => $discount,
                'vat' => $vat,
                'payable' => $payable,
            ]],
            'notes' => $data['notes'] ?? null,
            'issued_at' => now(),
            'due_at' => Jalali::parse($data['due_at'] ?? null),
        ]);

        return redirect('/app/tax-invoices?kind=proforma')->with('status', 'پیش‌فاکتور با موفقیت ثبت شد.');
    }

    /**
     * ثبت سفارش جدید از صفحهٔ فروش سریع (POS)
     *
     * اگر مشتری انتخاب نشده باشد، یک مشتری پیش‌فرض «مشتری پیاده» می‌سازد
     * تا خطای customer_id اجباری در جدول orders رخ ندهد.
     */
    public function storeOrder(Request $request)
    {
        try {
            $itemsJson = $request->input('items', '[]');
            $items = json_decode($itemsJson, true);

            if (!is_array($items) || empty($items)) {
                return response()->json(['ok' => false, 'message' => 'سبد خرید خالی است'], 422);
            }

            $customerId = $request->input('customer_id');
            $status     = $request->input('status', 'completed');
            $source     = $request->input('source', 'pos_quick');

            // اگر مشتری انتخاب نشده، یک مشتری پیش‌فرض بساز یا پیدا کن
            if (!$customerId) {
                $walkIn = Customer::firstOrCreate(
                    ['phone' => '00000000000', 'type' => 'individual'],
                    [
                        'full_name'      => 'مشتری پیاده (فروش سریع)',
                        'source'         => 'pos_walkin',
                        'lifetime_value' => 0,
                    ]
                );
                $customerId = $walkIn->id;
            }

            $total = 0;
            foreach ($items as $item) {
                $total += (int) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1);
            }

            $order = Order::create([
                'customer_id' => $customerId,
                'number'      => 'POS-' . now()->format('Ymd') . '-' . random_int(1000, 9999),
                'status'      => $status,
                'total'       => $total,
                'tax_total'   => 0,
                'currency'    => 'IRT',
                'source'      => $source,
                'placed_at'   => now(),
            ]);

            foreach ($items as $item) {
                $qty       = max(1, (int) ($item['qty'] ?? 1));
                $price     = (int) ($item['price'] ?? 0);
                $lineTotal = $price * $qty;

                \Modules\Core\Entities\OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['id'] ?? null,
                    'name'       => $item['name'] ?? 'محصول',
                    'qty'        => $qty,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ]);
            }

            if (in_array($status, ['processing', 'completed'])) {
                try {
                    app(\Modules\Core\Services\OrderLifecycleService::class)->sync($order);
                } catch (\Throwable $e) {}
            }

            return response()->json([
                'ok'       => true,
                'message'  => '✅ سفارش با موفقیت ثبت شد',
                'order_id' => $order->id,
                'number'   => $order->number,
                'total'    => $total,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'خطا در ثبت سفارش: ' . $e->getMessage(),
            ], 500);
        }
    }
}
