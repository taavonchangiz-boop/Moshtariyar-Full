<?php

namespace Modules\Core\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Entities\Activity;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\OrderItem;
use Modules\Core\Support\Money;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\LoyaltyTier;

class Customer360Service
{
    /**
     * وضعیت‌های معتبر سفارش که در محاسبات مالی لحاظ می‌شوند
     */
    private const وضعیت‌های_معتبر = ['processing', 'completed'];

    /**
     * دادهٔ کامل پروندهٔ ۳۶۰ درجهٔ مشتری برای صفحهٔ مدیریتی.
     *
     * 🚀 بهینه‌سازی: تمام سفارش‌ها فقط یک‌بار با eager load خوانده می‌شوند.
     * تمام محاسبات بعدی روی حافظه (Collection) انجام می‌شود نه دیتابیس.
     */
    public function get360Data(Customer $customer): array
    {
        // ─── ۱. فقط یک‌بار سفارش‌ها را با تمام رابطه‌ها بخوان ───
        $همه_سفارش‌ها = $customer->orders()
            ->with('items.product')
            ->latest('placed_at')
            ->latest('id')
            ->get();

        // ─── ۲. تفکیک یک‌باره در حافظه (بدون کوئری اضافه) ───
        $سفارش‌های_معتبر = $همه_سفارش‌ها->whereIn('status', self::وضعیت‌های_معتبر);
        $سفارش‌های_تکمیل = $همه_سفارش‌ها->where('status', 'completed');

        // ─── ۳. آخرین سفارش ───
        $آخرین_سفارش = $سفارش‌های_معتبر
            ->sortByDesc(fn($order) => $order->placed_at?->timestamp ?? $order->id)
            ->first();

        // ─── ۴. محاسبات مالی ───
        $روز_از_آخرین_خرید = $آخرین_سفارش && $آخرین_سفارش->placed_at
            ? (int) now()->diffInDays($آخرین_سفارش->placed_at)
            : 999;

        $ارزش_طول_عمر = (float) $سفارش‌های_معتبر->sum('total');
        $تعداد_تکمیل = $سفارش‌های_تکمیل->count();
        $تعداد_معتبر = $سفارش‌های_معتبر->count();
        $میانگین_ارزش_سفارش = $تعداد_معتبر > 0
            ? round($ارزش_طول_عمر / $تعداد_معتبر)
            : 0;

        // ─── ۵. اطلاعات باشگاه مشتریان ───
        $عضو_باشگاه = LoyaltyMember::query()
            ->with([
                'tier',
                'transactions' => fn($query) => $query->latest('id')->limit(8),
                'badges' => fn($query) => $query->latest('awarded_at')->limit(8),
            ])
            ->where('customer_id', $customer->id)
            ->first();

        // ─── ۶. تحلیل‌های هوشمند ───
        $رفتار_مشتری = $this->محاسبه_رفتار_مشتری(
            $روز_از_آخرین_خرید,
            $تعداد_معتبر,
            $ارزش_طول_عمر
        );

        $ریسک_ریزش = $this->محاسبه_ریسک_ریزش(
            $روز_از_آخرین_خرید,
            $تعداد_معتبر,
            $عضو_باشگاه
        );

        $سلامت = $this->محاسبه_سلامت(
            $رفتار_مشتری,
            $ریسک_ریزش,
            $عضو_باشگاه,
            $تعداد_معتبر
        );

        $وفاداری = $this->ساخت_بخش_وفاداری($عضو_باشگاه);
        $تحلیل_خرید = $this->ساخت_تحلیل_خرید($همه_سفارش‌ها);
        $محصولات_مورد_علاقه = $this->محصولات_مورد_علاقه($customer->id);
        $دسته‌های_مورد_علاقه = $this->دسته‌های_مورد_علاقه($customer->id);
        $خط_زمان = $this->ساخت_خط_زمان($customer, $همه_سفارش‌ها, $عضو_باشگاه);
        $سفرها = $this->سفرهای_مشتری($customer->id);
        $عملیاتی = $this->داده‌های_عملیاتی($customer, $سفرها);

        $اقدام_بعدی = $this->اقدام_هوشمند_بعدی(
            $customer,
            $سلامت,
            $ریسک_ریزش,
            $عضو_باشگاه,
            $تعداد_معتبر,
            $روز_از_آخرین_خرید
        );

        return [
            'stats' => [
                'lifetime_value'     => $ارزش_طول_عمر,
                'completed_orders'   => $تعداد_تکمیل,
                'orders_count'       => $تعداد_معتبر,
                'aov'                => $میانگین_ارزش_سفارش,
                'days_since_purchase'=> $روز_از_آخرین_خرید,
                'last_order_total'   => (float) ($آخرین_سفارش?->total ?? 0),
                'last_order_id'      => $آخرین_سفارش?->id,
                'last_order_number'  => $آخرین_سفارش?->number,
                'last_order_at'      => $آخرین_سفارش?->placed_at,
            ],
            'rfm'                  => $رفتار_مشتری,
            'loyalty'              => $وفاداری,
            'journeys'             => $سفرها,
            'operational'          => $عملیاتی,
            'timeline'             => $خط_زمان,
            'churn_risk'           => $ریسک_ریزش['درصد'],
            'health'               => $سلامت,
            'purchase_insight'     => $تحلیل_خرید,
            'favorite_products'    => $محصولات_مورد_علاقه,
            'favorite_categories'  => $دسته‌های_مورد_علاقه,
            'smart_next_action'    => $اقدام_بعدی,
            'quick_actions'        => $this->اقدامات_سریع($customer),
        ];
    }

    /**
     * سازگاری با فراخوانی قدیمی.
     */
    public function getDetailedView(int $customerId): array
    {
        return $this->get360Data(Customer::findOrFail($customerId));
    }

    // ──────────────────────────────────────────────
    //  تحلیل رفتاری مشتری (RFM)
    // ──────────────────────────────────────────────

    private function محاسبه_رفتار_مشتری(
        int $روز_از_آخرین_خرید,
        int $تعداد_سفارش,
        float $ارزش_کل
    ): array {
        // امتیاز تازگی خرید
        $امتیاز_تازگی = match (true) {
            $روز_از_آخرین_خرید <= 7  => 40,
            $روز_از_آخرین_خرید <= 30 => 32,
            $روز_از_آخرین_خرید <= 60 => 24,
            $روز_از_آخرین_خرید <= 90 => 16,
            $روز_از_آخرین_خرید < 999 => 8,
            default                  => 0,
        };

        // امتیاز تعداد خرید
        $امتیاز_تعداد = match (true) {
            $تعداد_سفارش >= 10 => 30,
            $تعداد_سفارش >= 5  => 24,
            $تعداد_سفارش >= 2  => 16,
            $تعداد_سفارش === 1 => 8,
            default            => 0,
        };

        // امتیاز ارزش خرید
        $امتیاز_ارزش = match (true) {
            $ارزش_کل >= 50000000 => 30,
            $ارزش_کل >= 15000000 => 24,
            $ارزش_کل >= 5000000  => 16,
            $ارزش_کل > 0         => 8,
            default              => 0,
        };

        $امتیاز_نهایی = min(100, $امتیاز_تازگی + $امتیاز_تعداد + $امتیاز_ارزش);

        // بدون خرید
        if ($تعداد_سفارش === 0) {
            return [
                'score'   => 0,
                'segment' => 'بدون خرید',
                'color'   => '#64748b',
            ];
        }

        // قهرمان برند
        if ($امتیاز_نهایی >= 82) {
            return [
                'score'   => $امتیاز_نهایی,
                'segment' => 'قهرمان برند',
                'color'   => '#f59e0b',
            ];
        }

        // مشتری وفادار
        if ($امتیاز_نهایی >= 65) {
            return [
                'score'   => $امتیاز_نهایی,
                'segment' => 'مشتری وفادار',
                'color'   => '#10b981',
            ];
        }

        // در خطر ریزش
        if ($روز_از_آخرین_خرید > 90) {
            return [
                'score'   => $امتیاز_نهایی,
                'segment' => 'در خطر ریزش',
                'color'   => '#ef4444',
            ];
        }

        // تازه‌وارد
        if ($تعداد_سفارش === 1) {
            return [
                'score'   => $امتیاز_نهایی,
                'segment' => 'تازه‌وارد',
                'color'   => '#3b82f6',
            ];
        }

        // مشتری عادی
        return [
            'score'   => $امتیاز_نهایی,
            'segment' => 'مشتری عادی',
            'color'   => '#6366f1',
        ];
    }

    /**
     * محاسبهٔ ریسک ریزش مشتری (درصد ۰ تا ۱۰۰)
     */
    private function محاسبه_ریسک_ریزش(
        int $روز_از_آخرین_خرید,
        int $تعداد_سفارش,
        ?LoyaltyMember $عضو_باشگاه
    ): array {
        if ($تعداد_سفارش === 0) {
            return [
                'درصد' => 65,
                'برچسب' => 'نیازمند تبدیل به خریدار',
                'رنگ'   => '#f59e0b',
            ];
        }

        $ریسک = match (true) {
            $روز_از_آخرین_خرید <= 14  => 10,
            $روز_از_آخرین_خرید <= 30  => 20,
            $روز_از_آخرین_خرید <= 60  => 42,
            $روز_از_آخرین_خرید <= 90  => 58,
            $روز_از_آخرین_خرید <= 180 => 76,
            default                    => 88,
        };

        // اگر عضو باشگاه است و امتیاز دارد، کمی ریسک کاهش یابد
        if ($عضو_باشگاه && (int) $عضو_باشگاه->points > 0) {
            $ریسک = max(5, $ریسک - 8);
        }

        $درصد_نهایی = min(95, max(0, $ریسک));

        return [
            'درصد'  => $درصد_نهایی,
            'برچسب' => $درصد_نهایی >= 70
                ? 'پرخطر'
                : ($درصد_نهایی >= 40 ? 'نیازمند مراقبت' : 'سالم'),
            'رنگ'   => $درصد_نهایی >= 70
                ? '#ef4444'
                : ($درصد_نهایی >= 40 ? '#f59e0b' : '#10b981'),
        ];
    }

    /**
     * محاسبهٔ امتیاز سلامت کل مشتری
     */
    private function محاسبه_سلامت(
        array $رفتار,
        array $ریسک,
        ?LoyaltyMember $عضو_باشگاه,
        int $تعداد_سفارش
    ): array {
        $امتیاز_رفتاری = (int) ($رفتار['score'] ?? 0);

        $امتیاز = (int) round(
            ($امتیاز_رفتاری * 0.62)
            + ((100 - $ریسک['درصد']) * 0.28)
            + (min(10, $تعداد_سفارش) * 1)
        );

        if ($عضو_باشگاه) {
            $امتیاز += 8;
        }

        $امتیاز = min(100, max(0, $امتیاز));

        return [
            'score'  => $امتیاز,
            'label'  => $امتیاز >= 75
                ? 'عالی'
                : ($امتیاز >= 50 ? 'قابل رشد' : 'نیازمند توجه'),
            'color'  => $امتیاز >= 75
                ? '#10b981'
                : ($امتیاز >= 50 ? '#f59e0b' : '#ef4444'),
        ];
    }

    /**
     * ساخت بخش وفاداری (باشگاه مشتریان)
     */
    private function ساخت_بخش_وفاداری(?LoyaltyMember $عضو_باشگاه): array
    {
        if (!$عضو_باشگاه) {
            return [
                'member'         => null,
                'points'         => 0,
                'tier'           => null,
                'wallet_balance' => 0,
                'badges'         => collect(),
                'next_tier'      => null,
                'next_tier_gap'  => null,
                'tier_progress'  => 0,
            ];
        }

        $امتیاز_فعلی = (int) $عضو_باشگاه->points;

        $سطح_بعدی = LoyaltyTier::query()
            ->where('min_points', '>', $امتیاز_فعلی)
            ->orderBy('min_points')
            ->first();

        $حداقل_سطح_فعلی = (int) ($عضو_باشگاه->tier?->min_points ?? 0);
        $حداقل_سطح_بعدی = (int) (
            $سطح_بعدی?->min_points
            ?? max($حداقل_سطح_فعلی + 1, $امتیاز_فعلی)
        );

        $فاصله = max(1, $حداقل_سطح_بعدی - $حداقل_سطح_فعلی);

        $پیشرفت = $سطح_بعدی
            ? min(100, max(0, round((($امتیاز_فعلی - $حداقل_سطح_فعلی) / $فاصله) * 100)))
            : 100;

        return [
            'member'         => $عضو_باشگاه,
            'points'         => $امتیاز_فعلی,
            'tier'           => $عضو_باشگاه->tier?->name,
            'wallet_balance' => (float) $عضو_باشگاه->wallet_balance,
            'badges'         => $عضو_باشگاه->badges ?? collect(),
            'next_tier'      => $سطح_بعدی?->name,
            'next_tier_gap'  => $سطح_بعدی
                ? max(0, $حداقل_سطح_بعدی - $امتیاز_فعلی)
                : 0,
            'tier_progress'  => $پیشرفت,
            'transactions'   => $عضو_باشگاه->transactions ?? collect(),
        ];
    }

    /**
     * تحلیل وضعیت خریدهای مشتری
     */
    private function ساخت_تحلیل_خرید($همه_سفارش‌ها): array
    {
        $آخرین = $همه_سفارش‌ها->first();
        $معوق = $همه_سفارش‌ها
            ->whereIn('status', ['pending', 'on_hold', 'failed'])
            ->count();
        $در_حال_پردازش = $همه_سفارش‌ها
            ->where('status', 'processing')
            ->count();

        return [
            'latest_order'    => $آخرین,
            'pending_count'   => $معوق,
            'processing_count'=> $در_حال_پردازش,
            'has_open_order'  => ($معوق + $در_حال_پردازش) > 0,
        ];
    }

    /**
     * محصولات پرتکرار در خریدهای مشتری
     */
    private function محصولات_مورد_علاقه(int $شناسه_مشتری)
    {
        return OrderItem::query()
            ->selectRaw(
                'COALESCE(product_id, 0) as product_id, name, sku, '
                . 'SUM(qty) as qty_sum, SUM(line_total) as total_sum'
            )
            ->whereHas(
                'order',
                fn($query) => $query
                    ->where('customer_id', $شناسه_مشتری)
                    ->whereIn('status', self::وضعیت‌های_معتبر)
            )
            ->groupBy('product_id', 'name', 'sku')
            ->orderByDesc('qty_sum')
            ->limit(5)
            ->get();
    }

    /**
     * دسته‌بندی‌های پرتکرار در خریدهای مشتری
     */
    private function دسته‌های_مورد_علاقه(int $شناسه_مشتری)
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'category')) {
            return collect();
        }

        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.customer_id', $شناسه_مشتری)
            ->whereIn('orders.status', self::وضعیت‌های_معتبر)
            ->selectRaw(
                'COALESCE(products.category, "بدون دسته‌بندی") as category, '
                . 'SUM(order_items.qty) as qty_sum, '
                . 'SUM(order_items.line_total) as total_sum'
            )
            ->groupBy('category')
            ->orderByDesc('qty_sum')
            ->limit(5)
            ->get();
    }

    /**
     * سفرهای فعال مشتری در اتوماسیون
     */
    private function سفرهای_مشتری(int $شناسه_مشتری)
    {
        if (!class_exists(\Modules\Automation\Entities\JourneyExecution::class)) {
            return collect();
        }

        return \Modules\Automation\Entities\JourneyExecution::query()
            ->with('workflow')
            ->where('customer_id', $شناسه_مشتری)
            ->latest('id')
            ->limit(6)
            ->get();
    }

    /**
     * داده‌های عملیاتی (وظایف، یادآوری‌ها، سفرهای فعال)
     */
    private function داده‌های_عملیاتی(Customer $مشتری, $سفرها): array
    {
        try {
            $فعالیت‌ها = Activity::query()
                ->where(function ($query) {
                    $query->where('subject_type', Customer::class)
                        ->orWhere('subject_type', 'customer');
                })
                ->where('subject_id', $مشتری->id)
                ->latest('due_at')
                ->latest('id')
                ->limit(60)
                ->get();
        } catch (\Throwable $e) {
            $فعالیت‌ها = collect();
        }

        $ابتدای_امروز = now()->startOfDay();
        $انتهای_امروز = now()->endOfDay();

        $وظایف = $فعالیت‌ها
            ->filter(fn($item) => $item->type === 'task' && !$item->done)
            ->values();

        $عقب‌افتاده = $وظایف
            ->filter(fn($item) => $item->due_at && $item->due_at->lt($ابتدای_امروز))
            ->values();

        $امروز = $وظایف
            ->filter(fn($item) => $item->due_at && $item->due_at->betweenIncluded($ابتدای_امروز, $انتهای_امروز))
            ->values();

        return [
            'tasks'          => $وظایف->take(8),
            'open_tasks'     => $وظایف->count(),
            'overdue_tasks'  => $عقب‌افتاده->count(),
            'today_tasks'    => $امروز->count(),
            'active_journeys'=> $سفرها->where('status', 'active')->count(),
        ];
    }

    /**
     * ساخت خط زمانی رویدادهای مشتری
     */
    private function ساخت_خط_زمان(
        Customer $مشتری,
        $همه_سفارش‌ها,
        ?LoyaltyMember $عضو_باشگاه
    ) {
        $رویدادها = collect();

        // رویدادهای سفارش
        foreach ($همه_سفارش‌ها->take(10) as $سفارش) {
            $رویدادها->push([
                'date'   => $سفارش->placed_at ?? $سفارش->created_at,
                'type'   => 'order',
                'icon'   => '🛒',
                'title'  => 'سفارش شماره ' . ($سفارش->number ?: $سفارش->id),
                'amount' => (float) $سفارش->total,
                'status' => $سفارش->status,
                'link'   => url('/app/orders/' . $سفارش->id),
            ]);
        }

        // تراکنش‌های باشگاه
        if ($عضو_باشگاه) {
            foreach (($عضو_باشگاه->transactions ?? collect())->take(8) as $تراکنش) {
                $رویدادها->push([
                    'date'   => $تراکنش->created_at,
                    'type'   => 'loyalty',
                    'icon'   => '⭐',
                    'title'  => 'تراکنش امتیاز باشگاه',
                    'amount' => (float) $تراکنش->amount,
                    'status' => $تراکنش->direction ?? null,
                ]);
            }
        }

        // فعالیت‌های ثبت‌شده
        try {
            $فعالیت‌ها = Activity::query()
                ->where(function ($query) use ($مشتری) {
                    $query->where('subject_type', Customer::class)
                        ->orWhere('subject_type', 'customer');
                })
                ->where('subject_id', $مشتری->id)
                ->latest('id')
                ->limit(8)
                ->get();

            foreach ($فعالیت‌ها as $فعالیت) {
                $رویدادها->push([
                    'date'   => $فعالیت->created_at,
                    'type'   => 'activity',
                    'icon'   => '📝',
                    'title'  => $فعالیت->body ?: 'فعالیت ثبت‌شده',
                    'status' => $فعالیت->done ? 'done' : 'active',
                ]);
            }
        } catch (\Throwable $e) {
            // بی‌صدا رد شو — نباید صفحه پرونده مشتری بشکند
        }

        return $رویدادها
            ->filter(fn($item) => !empty($item['date']))
            ->sortByDesc(
                fn($item) => $item['date'] instanceof \DateTimeInterface
                    ? $item['date']->getTimestamp()
                    : Carbon::parse($item['date'])->timestamp
            )
            ->values()
            ->take(18);
    }

    /**
     * تشخیص هوشمند اقدام بعدی برای مشتری
     */
    private function اقدام_هوشمند_بعدی(
        Customer $مشتری,
        array $سلامت,
        array $ریسک,
        ?LoyaltyMember $عضو_باشگاه,
        int $تعداد_سفارش,
        int $روز_از_آخرین_خرید
    ): array {
        // بدون خرید → تبدیل به خریدار
        if ($تعداد_سفارش === 0) {
            return [
                'title' => 'تبدیل مشتری به خریدار اول',
                'body'  => 'این مشتری هنوز خرید موفقی ندارد. پیشنهاد می‌شود پیام خوش‌آمد، معرفی محصولات پرفروش یا کد تخفیف خرید اول ارسال شود.',
                'type'  => 'conversion',
                'color' => '#3b82f6',
            ];
        }

        // ریسک بالا → کمپین بازگشت فوری
        if ($ریسک['درصد'] >= 70) {
            return [
                'title' => 'کمپین بازگشت فوری',
                'body'  => 'مدت زیادی از آخرین خرید گذشته است. پیشنهاد می‌شود پیام بازگشت همراه با پیشنهاد محدود زمانی ارسال شود.',
                'type'  => 'winback',
                'color' => '#ef4444',
            ];
        }

        // مشتری ارزشمند → حفظ وضعیت
        if ($عضو_باشگاه && !empty($عضو_باشگاه->tier) && $سلامت['score'] >= 75) {
            return [
                'title' => 'حفظ مشتری ارزشمند',
                'body'  => 'این مشتری وضعیت خوبی دارد. پیشنهاد می‌شود پیام تشکر، پیشنهاد اختصاصی یا مأموریت خرید بعدی برای او فعال شود.',
                'type'  => 'vip',
                'color' => '#10b981',
            ];
        }

        // دارای امتیاز → فعال‌سازی امتیازها
        if ($عضو_باشگاه && (int) $عضو_باشگاه->points > 0) {
            return [
                'title' => 'فعال‌سازی امتیازهای باشگاه',
                'body'  => 'مشتری امتیاز دارد. پیشنهاد می‌شود روش استفاده از امتیاز یا تبدیل آن به تخفیف به او یادآوری شود.',
                'type'  => 'loyalty',
                'color' => '#f59e0b',
            ];
        }

        // حالت پیش‌فرض
        return [
            'title' => 'پیگیری فروش بعدی',
            'body'  => 'این مشتری سابقه خرید دارد. پیشنهاد می‌شود بر اساس محصول‌های مورد علاقه، پیشنهاد خرید مکمل ارسال شود.',
            'type'  => 'next_purchase',
            'color' => '#6366f1',
        ];
    }

    /**
     * دکمه‌های اقدام سریع در پرونده مشتری
     */
    private function اقدامات_سریع(Customer $مشتری): array
    {
        return [
            [
                'label'   => 'ارسال پیام',
                'url'     => '#',
                'icon'    => '✉️',
                'onclick' => 'openMessageModal()',
            ],
            [
                'label'   => 'ثبت فعالیت',
                'url'     => '#',
                'icon'    => '📝',
                'onclick' => 'openActivityModal()',
            ],
            [
                'label'   => 'تخصیص امتیاز',
                'url'     => '#',
                'icon'    => '⭐',
                'onclick' => 'openPointsModal()',
            ],
            [
                'label'   => 'ایجاد سفارش جدید',
                'url'     => url('/app/products?quick_customer_id=' . $مشتری->id),
                'icon'    => '🛍️',
                'onclick' => 'openOrderModal()',
            ],
        ];
    }
}