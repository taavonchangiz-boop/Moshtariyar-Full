<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Activity;
use Modules\Core\Support\Money;
use Modules\Core\Support\Num;
use Modules\Core\Support\Jalali;
use Modules\Loyalty\Entities\LoyaltyCoupon;
use Modules\Loyalty\Entities\LoyaltyMission;
use Modules\Loyalty\Entities\LoyaltyCampaign;

class AssistantBrainService
{
    protected ?AIService $ai;

    protected Customer360Service $customer360;

    public function __construct(
        AIService $ai,
        Customer360Service $customer360
    ) {
        $this->ai = $ai;
        $this->customer360 = $customer360;
    }

    public function process(
        string $message,
        ?int $customerId = null,
        array $context = []
    ): array {
        $localResult = $this->processLocalQuestion(
            $message,
            $customerId,
            false,
            $context
        );

        if ($localResult) {
            $localResult['suggestions'] = $this->generateSuggestions(
                $localResult['intent'] ?? 'chat',
                $customerId
            );

            return $localResult;
        }

        try {
            $systemPrompt = $this->getSystemPrompt($customerId);

            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ];

            $aiResponse = $this->ai
                ? $this->ai->chat($messages, 0.2)
                : null;

            if (
                ! $aiResponse
                || str_contains($aiResponse, 'شبیه‌ساز')
                || str_contains($aiResponse, 'get_revenue_report')
            ) {
                return $this->fallbackToLocal(
                    $message,
                    $customerId,
                    $context
                );
            }

            if ($this->isToolCall($aiResponse)) {
                $result = $this->executeTool(
                    $aiResponse,
                    $customerId
                );

                $result['suggestions'] = $this->generateSuggestions(
                    $result['intent'] ?? 'chat',
                    $customerId
                );

                return $result;
            }

            return [
                'answer' => $aiResponse,
                'intent' => 'chat',
                'needs_ticket' => false,
                'suggestions' => $this->generateSuggestions(
                    'chat',
                    $customerId
                ),
            ];
        } catch (\Throwable $exception) {
            return $this->fallbackToLocal(
                $message,
                $customerId,
                $context
            );
        }
    }

    protected function fallbackToLocal(
        string $message,
        ?int $customerId,
        array $context
    ): array {
        $fallback = $this->processLocalQuestion(
            $message,
            $customerId,
            true,
            $context
        );

        if ($fallback && ($fallback['intent'] ?? '') !== 'guide') {
            $fallback['suggestions'] = $this->generateSuggestions(
                $fallback['intent'] ?? 'chat',
                $customerId
            );

            return $fallback;
        }

        return [
            'answer' => "سیستم پردازش ابری متصل نیست، اما شما همچنان می‌توانید دستوراتی مانند:\n\n"
                . "• «تخفیف ۲۰ درصد صادر کن»\n"
                . "• «یادآور برای مشتری ۱: فردا تماس بگیرم»\n"
                . "• «ثبت مشتری جدید نام علی موبایل 0912...»\n\n"
                . "را برای اجرای مستقیم و سریع در سامانه ارسال کنید.",
            'intent' => 'fallback_guide',
            'suggestions' => $this->generateSuggestions(
                'guide',
                $customerId
            ),
            'offline_mode' => true,
        ];
    }

    protected function processLocalQuestion(
        string $message,
        ?int $customerId = null,
        bool $force = false,
        array $context = []
    ): ?array {
        $text = mb_strtolower(trim($message));
        $pageContext = $context['page'] ?? [];

        $text = $this->convertPersianToEnglishNumbers($text);
        $originalMessage = $this->convertPersianToEnglishNumbers($message);

        $actionResult = $this->executeDirectAction(
            $originalMessage,
            $customerId,
            $pageContext
        );

        if ($actionResult) {
            return $actionResult;
        }

        $contextAnswer = $this->processContextQuestion(
            $message,
            $pageContext
        );

        if ($contextAnswer) {
            return $contextAnswer;
        }

        if (
            $this->hasAllWords($text, ['وضعیت', 'امروز'])
            || $this->hasAllWords($text, ['گزارش', 'امروز'])
            || $this->hasAllWords($text, ['فروش', 'امروز'])
            || $this->containsAny($text, ['داشبورد', 'نبض کسب'])
        ) {
            return $this->localBusinessOverview();
        }

        if ($this->containsAny($text, ['تیکت', 'پشتیبانی', 'پاسخ فوری'])) {
            return $this->localTicketReport();
        }

        if (
            $this->hasAllWords($text, ['گزارش', 'سفارش'])
            || $this->hasAllWords($text, ['گزارش', 'فروش'])
        ) {
            return $this->localOrderReport();
        }

        if (
            $this->hasAllWords($text, ['گزارش', 'مشتری'])
            || $this->containsAny($text, ['ریزش', 'پرونده مشتری'])
        ) {
            return $this->localCustomerReport($customerId);
        }

        if ($this->containsAny($text, ['کمپین', 'بازاریابی', 'رشد فروش'])) {
            return $this->localCampaignReport();
        }

        if (
            $this->containsAny(
                $text,
                [
                    'باشگاه',
                    'وفاداری',
                    'امتیاز',
                    'ماموریت',
                    'مأموریت',
                    'معرف',
                    'پاداش',
                ]
            )
        ) {
            return $this->localLoyaltyReport();
        }

        if (
            $this->containsAny(
                $text,
                [
                    'فاکتور',
                    'پیش فاکتور',
                    'پیش‌فاکتور',
                    'مالی',
                ]
            )
        ) {
            return $this->localFinanceReport();
        }

        if (
            $this->containsAny(
                $text,
                [
                    'محصول',
                    'موجودی',
                    'انبار',
                    'قیمت',
                ]
            )
        ) {
            return $this->localProductReport();
        }

        if (
            $this->containsAny(
                $text,
                [
                    'ووکامرس',
                    'همگام',
                    'فروشگاه',
                ]
            )
        ) {
            return $this->localWooReport();
        }

        return null;
    }

    protected function executeDirectAction(
        string $message,
        ?int $customerId,
        array $pageContext
    ): ?array {
        $text = mb_strtolower(trim($message));

        if (
            $this->containsAny($text, ['تخفیف'])
            && preg_match(
                '/(\d+)\s*(?:درصد|٪|%)/u',
                $text,
                $matches
            )
        ) {
            $value = (int) $matches[1];

            return $this->pendingAction(
                'coupon',
                [
                    'title' => "تخفیف دستیار {$value} درصدی",
                    'type' => 'percent',
                    'value' => $value,
                ],
                "پیش‌نمایش کد تخفیف آماده شد:\n\n"
                . "• مقدار تخفیف: {$value} درصد\n"
                . "• عنوان: تخفیف دستیار\n\n"
                . "در صورت تأیید، کد تخفیف در سامانه ایجاد می‌شود."
            );
        }

        if (
            $this->hasAllWords($text, ['یادآور'])
            || $this->hasAllWords($text, ['وظیفه'])
        ) {
            preg_match(
                '/مشتری(?:\s+شماره)?\s+(\d+)/u',
                $text,
                $customerMatches
            );

            $targetCustomerId = ! empty($customerMatches[1])
                ? (int) $customerMatches[1]
                : $customerId;

            if (! $targetCustomerId) {
                return [
                    'answer' => 'برای ثبت یادآور، باید شماره مشتری را اعلام کنید؛ نمونه: برای مشتری شماره ۱ یادآور بگذار.',
                ];
            }

            $date = str_contains($text, 'فردا')
                ? 'فردا'
                : (
                    str_contains($text, 'هفته')
                        ? 'هفته آینده'
                        : 'در اسرع وقت'
                );

            return $this->pendingAction(
                'reminder',
                [
                    'customer_id' => $targetCustomerId,
                    'text' => $message,
                    'date' => $date,
                ],
                "پیش‌نمایش یادآور آماده شد:\n\n"
                . "• مشتری: شماره {$targetCustomerId}\n"
                . "• زمان: {$date}\n"
                . "• متن: {$message}\n\n"
                . "در صورت تأیید، وظیفه در مرکز پیگیری ثبت می‌شود."
            );
        }

        if (
            $this->hasAllWords($text, ['مشتری', 'نام', 'موبایل'])
            || $this->hasAllWords($text, ['ثبت', 'مشتری', 'نام'])
        ) {
            preg_match(
                '/نام\s+(.+?)(?:\s+موبایل\s+(\d+))?$/u',
                $message,
                $matches
            );

            if (! empty($matches[1])) {
                $name = trim($matches[1]);
                $phone = $matches[2] ?? '09' . rand(10000000, 99999999);

                return $this->pendingAction(
                    'customer',
                    [
                        'full_name' => $name,
                        'phone' => $phone,
                    ],
                    "پیش‌نمایش ثبت مشتری آماده شد:\n\n"
                    . "• نام: {$name}\n"
                    . "• موبایل: {$phone}\n\n"
                    . "در صورت تأیید، مشتری جدید در سامانه ثبت می‌شود."
                );
            }
        }

        if (
            $this->hasAllWords($text, ['ساخت', 'کمپین'])
            || $this->hasAllWords($text, ['کمپین', 'جدید'])
        ) {
            $name = 'کمپین ' . Jalali::now()->format('Y/m/d');

            return $this->pendingAction(
                'campaign',
                [
                    'name' => $name,
                ],
                "پیش‌نمایش ساخت کمپین آماده شد:\n\n"
                . "• نام کمپین: {$name}\n"
                . "• وضعیت اولیه: پیش‌نویس\n\n"
                . "در صورت تأیید، کمپین به‌صورت پیش‌نویس ثبت می‌شود."
            );
        }

        if (
            $this->hasAllWords($text, ['ساخت', 'ماموریت'])
            || $this->hasAllWords($text, ['ماموریت', 'جدید'])
            || $this->hasAllWords($text, ['مأموریت', 'جدید'])
        ) {
            $title = 'ماموریت سیستم ' . Jalali::now()->format('m/d');

            return $this->pendingAction(
                'mission',
                [
                    'title' => $title,
                    'reward' => 100,
                ],
                "پیش‌نمایش ساخت مأموریت آماده شد:\n\n"
                . "• عنوان: {$title}\n"
                . "• پاداش: ۱۰۰ امتیاز\n\n"
                . "در صورت تأیید، مأموریت ثبت می‌شود."
            );
        }

        return null;
    }

    protected function pendingAction(
        string $type,
        array $params,
        string $preview
    ): array {
        return [
            'answer' => $preview
                . "\n\nبرای اجرا بنویسید: تأیید"
                . "\nبرای لغو بنویسید: لغو",
            'intent' => 'pending_' . $type,
            'params' => $params,
            'suggestions' => ['تأیید', 'لغو'],
        ];
    }

    protected function localBusinessOverview(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $ordersCount = 0;
        $revenue = 0;

        if (Schema::hasTable('orders')) {
            $ordersCount = DB::table('orders')
                ->whereBetween(
                    'placed_at',
                    [$todayStart, $todayEnd]
                )
                ->count();

            $totalColumn = Schema::hasColumn('orders', 'total')
                ? 'total'
                : 'payable';

            $revenue = DB::table('orders')
                ->whereBetween(
                    'placed_at',
                    [$todayStart, $todayEnd]
                )
                ->whereIn(
                    'status',
                    ['completed', 'processing']
                )
                ->sum($totalColumn);
        }

        $newCustomers = Schema::hasTable('customers')
            ? DB::table('customers')
                ->whereBetween(
                    'created_at',
                    [$todayStart, $todayEnd]
                )
                ->count()
            : 0;

        return [
            'answer' => "📊 **گزارش امروز تا این لحظه:**\n\n"
                . "• فروش موفق: "
                . Money::show($revenue)
                . ' '
                . Money::unitLabel()
                . "\n"
                . "• تعداد سفارش‌ها: "
                . Num::fa($ordersCount)
                . " مورد\n"
                . "• جذب مشتری جدید: "
                . Num::fa($newCustomers)
                . " نفر\n",
            'intent' => 'business_overview',
        ];
    }

    protected function localTasksReport(): array
    {
        $count = Activity::where('type', 'task')
            ->where('done', false)
            ->count();

        return [
            'answer' => "📋 **یادآورها و وظایف:**\nدر حال حاضر "
                . Num::fa($count)
                . " وظیفه باز در سامانه ثبت شده است.",
            'intent' => 'tasks_overview',
        ];
    }

    protected function localTicketReport(): array
    {
        $open = Schema::hasTable('tickets')
            ? DB::table('tickets')
                ->where('status', 'open')
                ->count()
            : 0;

        return [
            'answer' => "🎧 **پشتیبانی:**\nتعداد "
                . Num::fa($open)
                . " تیکت باز وجود دارد.",
            'intent' => 'tickets_overview',
            'url' => url('/app/tickets'),
        ];
    }

    protected function localOrderReport(): array
    {
        $pending = Schema::hasTable('orders')
            ? DB::table('orders')
                ->whereIn('status', ['pending', 'on_hold'])
                ->count()
            : 0;

        return [
            'answer' => "🛍️ **سفارشات معلق:**\nتعداد "
                . Num::fa($pending)
                . " سفارش باز و در انتظار پیگیری در سامانه وجود دارد.",
            'intent' => 'orders_overview',
            'url' => url('/app/orders'),
        ];
    }

    protected function localCustomerReport(?int $customerId): array
    {
        $total = Schema::hasTable('customers')
            ? DB::table('customers')->count()
            : 0;

        return [
            'answer' => "👥 **مشتریان:**\nدر کل "
                . Num::fa($total)
                . " مشتری ذخیره شده است.",
            'intent' => 'customers_overview',
            'url' => url('/app/customers'),
        ];
    }

    protected function localCampaignReport(): array
    {
        $active = Schema::hasTable('loyalty_campaigns')
            ? DB::table('loyalty_campaigns')
                ->where('status', 'active')
                ->count()
            : 0;

        return [
            'answer' => "🚀 **کمپین‌ها:**\nشما "
                . Num::fa($active)
                . " کمپین وفاداری فعال دارید.",
            'intent' => 'campaigns_overview',
            'url' => url('/app/campaigns'),
        ];
    }

    protected function localLoyaltyReport(): array
    {
        $members = Schema::hasTable('loyalty_members')
            ? DB::table('loyalty_members')->count()
            : 0;

        return [
            'answer' => "🎁 **اعضای باشگاه:**\nتعداد اعضای فعال باشگاه شما "
                . Num::fa($members)
                . " نفر می‌باشد.",
            'intent' => 'loyalty_overview',
            'url' => url('/app/loyalty'),
        ];
    }

    protected function localFinanceReport(): array
    {
        $proformas = Schema::hasTable('tax_invoices')
            ? DB::table('tax_invoices')
                ->where('invoice_kind', 'proforma')
                ->where('status', 'draft')
                ->count()
            : 0;

        return [
            'answer' => "🧾 **مالی:**\nشما "
                . Num::fa($proformas)
                . " پیش‌فاکتور به صورت پیش‌نویس دارید.",
            'intent' => 'finance_overview',
            'url' => url('/app/tax-invoices'),
        ];
    }

    protected function localProductReport(): array
    {
        $products = Schema::hasTable('products')
            ? DB::table('products')->count()
            : 0;

        return [
            'answer' => "📦 **انبار:**\nشما "
                . Num::fa($products)
                . " محصول ثبت‌شده دارید.",
            'intent' => 'products_overview',
        ];
    }

    protected function localWooReport(): array
    {
        return [
            'answer' => "🌐 **فروشگاه:** برای همگام‌سازی محصولات یا سفارش‌ها به بخش تنظیمات مراجعه کنید.",
            'intent' => 'woo_overview',
        ];
    }

    protected function processContextQuestion(
        string $message,
        array $pageContext
    ): ?array {
        return null;
    }

    protected function convertPersianToEnglishNumbers(string $string): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $numbers = range(0, 9);

        $converted = str_replace(
            $persian,
            $numbers,
            $string
        );

        return str_replace(
            $arabic,
            $numbers,
            $converted
        );
    }

    protected function containsAny(
        string $text,
        array $words
    ): bool {
        foreach ($words as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    protected function hasAllWords(
        string $text,
        array $words
    ): bool {
        foreach ($words as $word) {
            if (! str_contains($text, $word)) {
                return false;
            }
        }

        return true;
    }

    protected function generateSuggestions(
        string $intent,
        ?int $customerId
    ): array {
        return [
            'تخفیف ۲۰ درصد صادر کن',
            'وضعیت فروش امروز',
            'برای مشتری شماره ۱ یادآور بگذار تماس بگیرم',
        ];
    }

    protected function getSystemPrompt(?int $customerId): string
    {
        return 'شما دستیار هوشمند مدیریت کسب‌وکار هستید.';
    }

    protected function isToolCall(string $aiResponse): bool
    {
        return false;
    }

    protected function executeTool(
        string $aiResponse,
        ?int $customerId
    ): array {
        return [
            'answer' => 'عملیات اجرا شد.',
        ];
    }
}