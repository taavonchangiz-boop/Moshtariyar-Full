<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Entities\Activity;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Ticket;

/**
 * سرویس مرکز اعلان‌های یکپارچه — مشتری‌یار
 *
 * همهٔ رویدادهای مهم سامانه را از منابع مختلف (وظایف، تیکت‌ها، سفارش‌ها)
 * در یک فهرست یکپارچه جمع می‌کند تا مدیر بدون جابه‌جایی بین صفحات
 * از وضعیت سامانه باخبر شود.
 *
 * الهام‌گرفته از Notification Bell در Monday.com
 */
class AdminNotificationService
{
    /**
     * دریافت همهٔ اعلان‌های یکپارچه برای زنگولهٔ بالای صفحه
     *
     * @param  int  $limit  حداکثر تعداد اعلان‌ها
     * @return array
     */
    public function getUnifiedNotifications(int $limit = 12): array
    {
        $همه = [];

        // ═══ ۱. تیکت‌های باز و فوری ═══
        $همه = array_merge($همه, $this->ticketNotifications());

        // ═══ ۲. وظایف و یادآوری‌های عقب‌افتاده ═══
        $همه = array_merge($همه, $this->taskNotifications());

        // ═══ ۳. سفارش‌های جدید امروز ═══
        $همه = array_merge($همه, $this->orderNotifications());

        // ═══ ۴. سفارش‌های در انتظار پرداخت طولانی ═══
        $همه = array_merge($همه, $this->pendingPaymentNotifications());

        // ═══ ۵. پیام‌های سامانه (استراتژیک) ═══
        $همه = array_merge($همه, $this->systemNotifications());

        // مرتب‌سازی: فوری‌ترین‌ها اول
        usort($همه, fn ($a, $b) => ($a['time'] ?? 0) <=> ($b['time'] ?? 0));
        $همه = array_reverse($همه);

        return array_slice($همه, 0, $limit);
    }

    /**
     * شمارش کل اعلان‌های خوانده‌نشده
     */
    public function countUnread(): int
    {
        $count = 0;

        $count += $this->safeCount('tickets', fn ($q) => $q->whereIn('status', ['open', 'pending']));
        $count += $this->safeCount('activities', fn ($q) => $q->where('type', 'task')->where('done', false)->whereNotNull('due_at')->where('due_at', '<', now()));
        $count += $this->safeCount('orders', fn ($q) => $q->whereIn('status', ['pending', 'on_hold']));

        return $count;
    }

    // ═══════════════════════════════════════════════
    //  منابع اعلان‌ها
    // ═══════════════════════════════════════════════

    private function ticketNotifications(): array
    {
        $items = [];

        try {
            if (! Schema::hasTable('tickets')) return $items;

            $tickets = Ticket::with('customer')
                ->whereIn('status', ['open', 'pending'])
                ->latest('updated_at')
                ->limit(5)
                ->get();

            foreach ($tickets as $t) {
                $urgency = $t->priority === 'urgent' ? 'فوری' : '';
                $sla = '';

                if ($t->sla_due_at && $t->sla_due_at->isPast() && $t->status !== 'closed') {
                    $urgency = '⚠️ خارج از مهلت';
                    $sla = ' · مهلت: ' . \Modules\Core\Support\Jalali::date($t->sla_due_at);
                }

                $items[] = [
                    'type'    => 'ticket',
                    'icon'    => '🎫',
                    'title'   => $t->subject,
                    'body'    => ($urgency ? $urgency . ' — ' : '') . ($t->customer?->full_name ?? 'مشتری نامشخص') . $sla,
                    'url'     => url('/app/tickets/' . $t->id),
                    'time'    => $t->updated_at?->timestamp ?? 0,
                    'time_fa' => $t->updated_at ? \Modules\Core\Support\Jalali::datetime($t->updated_at) : '',
                    'urgency' => $t->priority === 'urgent' || ($t->sla_due_at && $t->sla_due_at->isPast()) ? 'urgent' : 'normal',
                ];
            }
        } catch (\Throwable $e) {}

        return $items;
    }

    private function taskNotifications(): array
    {
        $items = [];

        try {
            if (! Schema::hasTable('activities')) return $items;

            $tasks = Activity::query()
                ->where('type', 'task')
                ->where('done', false)
                ->whereNotNull('due_at')
                ->latest('due_at')
                ->limit(4)
                ->get();

            foreach ($tasks as $task) {
                $isOverdue = $task->due_at && $task->due_at->isPast();
                $prefix = $isOverdue ? '🔴 عقب‌افتاده: ' : '📝 ';

                $items[] = [
                    'type'    => 'task',
                    'icon'    => $isOverdue ? '🔴' : '📝',
                    'title'   => $prefix . ($task->body ?: 'وظیفهٔ بدون عنوان'),
                    'body'    => 'موعد: ' . \Modules\Core\Support\Jalali::date($task->due_at),
                    'url'     => url('/app/reminders'),
                    'time'    => ($task->due_at?->timestamp ?? 0),
                    'time_fa' => $task->due_at ? \Modules\Core\Support\Jalali::datetime($task->due_at) : '',
                    'urgency' => $isOverdue ? 'urgent' : 'normal',
                ];
            }
        } catch (\Throwable $e) {}

        return $items;
    }

    private function orderNotifications(): array
    {
        $items = [];

        try {
            if (! Schema::hasTable('orders')) return $items;

            $todayOrders = Order::with('customer')
                ->where('placed_at', '>=', now()->startOfDay())
                ->latest('placed_at')
                ->limit(4)
                ->get();

            foreach ($todayOrders as $order) {
                $items[] = [
                    'type'    => 'order',
                    'icon'    => '🛒',
                    'title'   => 'سفارش جدید: ' . ($order->number ?: $order->id),
                    'body'    => ($order->customer?->full_name ?? 'مشتری نامشخص') . ' · ' . \Modules\Core\Support\Money::show($order->total ?? 0) . ' ' . \Modules\Core\Support\Money::unitLabel(),
                    'url'     => url('/app/orders/' . $order->id),
                    'time'    => $order->placed_at?->timestamp ?? $order->created_at?->timestamp ?? 0,
                    'time_fa' => $order->placed_at ? \Modules\Core\Support\Jalali::datetime($order->placed_at) : ($order->created_at ? \Modules\Core\Support\Jalali::datetime($order->created_at) : ''),
                    'urgency' => 'normal',
                ];
            }
        } catch (\Throwable $e) {}

        return $items;
    }

    private function pendingPaymentNotifications(): array
    {
        $items = [];

        try {
            if (! Schema::hasTable('orders')) return $items;

            $oldPending = Order::with('customer')
                ->whereIn('status', ['pending', 'on_hold', 'failed'])
                ->where('placed_at', '<', now()->subHours(24))
                ->latest('placed_at')
                ->limit(3)
                ->get();

            foreach ($oldPending as $order) {
                $items[] = [
                    'type'    => 'payment',
                    'icon'    => '💳',
                    'title'   => 'در انتظار پرداخت: سفارش ' . ($order->number ?: $order->id),
                    'body'    => ($order->customer?->full_name ?? 'مشتری نامشخص') . ' · بیش از ۲۴ ساعت',
                    'url'     => url('/app/orders/' . $order->id),
                    'time'    => $order->placed_at?->timestamp ?? 0,
                    'time_fa' => $order->placed_at ? \Modules\Core\Support\Jalali::datetime($order->placed_at) : '',
                    'urgency' => 'warning',
                ];
            }
        } catch (\Throwable $e) {}

        return $items;
    }

    private function systemNotifications(): array
    {
        $items = [];

        try {
            if (Schema::hasTable('strategic_briefings')) {
                $todayBriefing = DB::table('strategic_briefings')
                    ->whereDate('report_date', now()->toDateString())
                    ->first();

                if ($todayBriefing) {
                    $items[] = [
                        'type'    => 'system',
                        'icon'    => '📊',
                        'title'   => 'گزارش استراتژیک امروز آماده است',
                        'body'    => 'برای مشاهدهٔ تحلیل ریزش، رشد و سلامت کسب‌وکار کلیک کن',
                        'url'     => url('/app'),
                        'time'    => now()->subHour()->timestamp,
                        'time_fa' => \Modules\Core\Support\Jalali::datetime(now()->subHour()),
                        'urgency' => 'info',
                    ];
                }
            }

            if (Schema::hasTable('tickets')) {
                $overdueCount = DB::table('tickets')
                    ->whereNull('first_response_at')
                    ->whereNotNull('sla_due_at')
                    ->where('sla_due_at', '<', now())
                    ->whereNotIn('status', ['closed'])
                    ->count();

                if ($overdueCount >= 5) {
                    $items[] = [
                        'type'    => 'system',
                        'icon'    => '🚨',
                        'title'   => \Modules\Core\Support\Num::fa($overdueCount) . ' تیکت خارج از مهلت پاسخ‌گویی',
                        'body'    => 'نیاز به اقدام فوری تیم پشتیبانی — روی این پیام کلیک کن',
                        'url'     => url('/app/tickets?overdue=1'),
                        'time'    => now()->subMinutes(30)->timestamp,
                        'time_fa' => \Modules\Core\Support\Jalali::datetime(now()->subMinutes(30)),
                        'urgency' => 'urgent',
                    ];
                }
            }
        } catch (\Throwable $e) {}

        return $items;
    }

    private function safeCount(string $table, callable $callback): int
    {
        try {
            if (! Schema::hasTable($table)) return 0;
            $query = DB::table($table);
            $callback($query);
            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}