<?php

namespace Modules\Automation\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\Automation\Entities\Workflow;
use Modules\Core\Entities\Activity;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Ticket;
use Modules\Core\Support\Money;
use Modules\Core\Support\Num;
use Modules\IranPack\Managers\SmsManager;
use Modules\IranPack\Messaging\MessagingChannels;

/**
 * موتور مرکزی اجرای قانون‌های خودکار سامانه.
 *
 * همه رویدادهای سفارش، پرداخت، مشتری و تیکت از این مسیر عبور می‌کنند
 * تا قانون‌ها دوباره اجرا نشوند و نتیجه هر رویداد قابل پیش‌بینی باشد.
 */
class WorkflowEngine
{
    /** هنگام همگام‌سازی یا تغییر وضعیت سفارش */
    public function onOrderSynced(Order $order, array $woo = []): void
    {
        $event = match ($order->status) {
            'completed' => 'order.completed',
            'processing' => 'order.processing',
            'cancelled' => 'order.cancelled',
            default => null,
        };

        if (! $event) {
            return;
        }

        $this->fire($event, $order, ['woo' => $woo]);

        if ($order->customer) {
            app(JourneyEngine::class)->startForEvent($event, $order->customer, $order);
        }
    }

    /** اجرای رویداد پرداخت موفق */
    public function onPaymentPaid(Order $order): void
    {
        $this->fire('payment.paid', $order);

        if ($order->customer) {
            app(JourneyEngine::class)->startForEvent('payment.paid', $order->customer, $order);
        }
    }

    /** ثبت مشتری جدید و آغاز سفرهای مرتبط */
    public function onCustomerRegistered(Customer $customer): void
    {
        $this->fire('customer.registered', $customer);
        app(JourneyEngine::class)->startForEvent('customer.registered', $customer);
    }

    /** ثبت تیکت جدید */
    public function onTicketOpened(Ticket $ticket): void
    {
        $this->fire('ticket.created', $ticket);
    }

    /**
     * مسیر سازگاری برای رویدادهای قدیمی پروژه.
     * این تابع اجازه می‌دهد مسیرهای قبلی بدون ایجاد موتور دوم همچنان کار کنند.
     */
    public function fireEvent(string $eventName, $subject = null, array $extraData = []): void
    {
        if ($subject === null) {
            return;
        }

        $this->fire($eventName, $subject, $extraData);
    }

    /** اجرای همه قانون‌های فعال یک رویداد */
    public function fire(string $event, $subject, array $context = []): void
    {
        try {
            if (! Schema::hasTable('workflows')) {
                return;
            }

            $customer = $this->extractCustomer($subject);
            $eventKeys = $this->eventKeys($event);
            $query = Workflow::query()
                ->whereIn('event', $eventKeys)
                ->where('is_active', true);

            if (Schema::hasColumn('workflows', 'type')) {
                $query->where(function ($builder) {
                    $builder->whereNull('type')->orWhere('type', 'simple');
                });
            }

            $workflows = $query->get();

            foreach ($workflows as $workflow) {
                if (! $customer && $workflow->action !== 'create_task') {
                    continue;
                }

                if ($customer && $workflow->trigger_segment_id) {
                    if (! $customer->segments()->where('segments.id', $workflow->trigger_segment_id)->exists()) {
                        continue;
                    }
                }

                if (! $this->shouldRunOnce($workflow, $event, $subject)) {
                    continue;
                }

                $message = $this->renderTemplate((string) ($workflow->template ?? ''), $subject, $customer);
                $this->runAction($workflow, $message, $subject, $customer, $context);
            }
        } catch (\Throwable $exception) {
            Log::warning('اجرای قانون خودکار با خطا مواجه شد: ' . $exception->getMessage(), [
                'event' => $event,
                'subject_id' => $subject->id ?? null,
            ]);
        }
    }

    /** جلوگیری از اجرای دوباره یک قانون برای یک رویداد */
    protected function shouldRunOnce(Workflow $workflow, string $event, $subject): bool
    {
        try {
            $subjectType = is_object($subject) ? get_class($subject) : 'unknown';
            $subjectId = is_object($subject) ? ($subject->id ?? '0') : '0';
            $updatedAt = is_object($subject) && $subject->updated_at
                ? $subject->updated_at->getTimestamp()
                : '0';
            $canonicalEvent = $this->canonicalEvent($event);
            $key = 'automation.workflow.' . $workflow->id . '.' . $canonicalEvent . '.' . $subjectType . '.' . $subjectId . '.' . $updatedAt;

            return Cache::add($key, true, now()->addSeconds(45));
        } catch (\Throwable $exception) {
            return true;
        }
    }

    /** نام‌های قدیمی و جدید یک رویداد */
    protected function eventKeys(string $event): array
    {
        return match ($event) {
            'customer.created', 'customer.registered' => ['customer.created', 'customer.registered'],
            'ticket.opened', 'ticket.created' => ['ticket.opened', 'ticket.created'],
            default => [$event],
        };
    }

    protected function canonicalEvent(string $event): string
    {
        return match ($event) {
            'customer.created' => 'customer.registered',
            'ticket.opened' => 'ticket.created',
            default => $event,
        };
    }

    /** اجرای اقدام ثبت‌شده در قانون */
    protected function runAction(Workflow $workflow, string $message, $subject, ?Customer $customer, array $context = []): void
    {
        try {
            $action = (string) $workflow->action;
            $phone = $customer?->phone;
            $email = $customer?->email;

            if ($action === 'sms' && $phone) {
                app(SmsManager::class)->send($phone, $message);
                return;
            }

            if ($action === 'messenger' && $phone) {
                $channel = $workflow->channel ?: 'all';

                if ($channel === 'all') {
                    MessagingChannels::broadcast($phone, $message);
                } else {
                    MessagingChannels::sendVia($channel, $phone, $message);
                }

                return;
            }

            if ($action === 'email' && $email) {
                Mail::raw($message, function ($mail) use ($email) {
                    $mail->to($email)->subject(config('brand.name', 'مشتری‌یار'));
                });

                return;
            }

            if ($action === 'create_task') {
                $this->createTask($workflow, $message, $subject, $customer);
            }
        } catch (\Throwable $exception) {
            Log::warning('اقدام قانون خودکار اجرا نشد: ' . $exception->getMessage(), [
                'workflow_id' => $workflow->id,
                'action' => $workflow->action,
                'subject_id' => $subject->id ?? null,
            ]);
        }
    }

    /** ساخت وظیفه داخلی از نتیجه رویداد */
    protected function createTask(Workflow $workflow, string $message, $subject, ?Customer $customer): void
    {
        if (! $customer || ! Schema::hasTable('activities')) {
            return;
        }

        $delay = max(0, (int) ($workflow->delay_min ?? 0));

        Activity::create([
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'type' => 'task',
            'body' => 'اتوماسیون: ' . $message,
            'user_id' => auth()->id(),
            'due_at' => $delay > 0 ? now()->addMinutes($delay) : null,
            'done' => false,
        ]);
    }

    /** پیدا کردن مشتری مربوط به موضوع رویداد */
    protected function extractCustomer($subject): ?Customer
    {
        if ($subject instanceof Customer) {
            return $subject;
        }

        if ($subject instanceof Order) {
            return $subject->customer ?: ($subject->customer_id ? Customer::find($subject->customer_id) : null);
        }

        if ($subject instanceof Ticket) {
            return $subject->customer ?: ($subject->customer_id ? Customer::find($subject->customer_id) : null);
        }

        if (isset($subject->customer_id)) {
            return Customer::find($subject->customer_id);
        }

        return null;
    }

    /** جایگزینی متغیرهای پیام */
    protected function renderTemplate(string $template, $subject, ?Customer $customer): string
    {
        $order = $subject instanceof Order ? $subject : null;
        $ticket = $subject instanceof Ticket ? $subject : null;

        $member = $customer?->id
            ? \Modules\Loyalty\Entities\LoyaltyMember::where('customer_id', $customer->id)->first()
            : null;

        $statusLabels = [
            'pending' => 'در انتظار',
            'processing' => 'در حال آماده‌سازی',
            'completed' => 'تکمیل‌شده',
            'on_hold' => 'نیازمند بررسی',
            'cancelled' => 'لغوشده',
            'refunded' => 'مرجوعی',
            'failed' => 'ناموفق',
            'open' => 'باز',
            'answered' => 'پاسخ‌داده‌شده',
            'closed' => 'بسته‌شده',
        ];

        $status = $subject->status ?? '';

        $map = [
            '{name}' => $customer?->full_name ?? '',
            '{نام}' => $customer?->full_name ?? '',
            '{phone}' => $customer?->phone ?? '',
            '{تلفن}' => $customer?->phone ?? '',
            '{email}' => $customer?->email ?? '',
            '{ایمیل}' => $customer?->email ?? '',
            '{brand}' => config('brand.name', 'مشتری‌یار'),
            '{برند}' => config('brand.name', 'مشتری‌یار'),
            '{status}' => $statusLabels[$status] ?? $status,
            '{وضعیت}' => $statusLabels[$status] ?? $status,
            '{points}' => $member
                ? Num::fa(number_format((int) ($member->points_lifetime ?? $member->points ?? 0)))
                : '۰',
            '{امتیاز}' => $member
                ? Num::fa(number_format((int) ($member->points_lifetime ?? $member->points ?? 0)))
                : '۰',
            '{wallet}' => $member
                ? Money::show((float) ($member->wallet_balance ?? 0))
                : '۰',
            '{کیف_پول}' => $member
                ? Money::show((float) ($member->wallet_balance ?? 0))
                : '۰',
            '{referral_link}' => $member?->referral_link ?: route('club.register', [], false),
            '{لینک_دعوت}' => $member?->referral_link ?: route('club.register', [], false),
            '{موضوع}' => $ticket?->subject ?? '',
            '{تیکت}' => $ticket?->id ? Num::fa($ticket->id) : '',
            '{اولویت}' => $ticket?->priority ?? '',
        ];

        if ($order) {
            $map['{order}'] = Num::fa($order->number);
            $map['{سفارش}'] = Num::fa($order->number);
            $map['{total}'] = Money::show((float) $order->total);
            $map['{مبلغ}'] = Money::show((float) $order->total);
            $map['{last_order}'] = $order->placed_at
                ? \Modules\Core\Support\Jalali::datetime($order->placed_at)
                : '—';
            $map['{آخرین_خرید}'] = $map['{last_order}'];
        }

        $result = trim(strtr($template, $map));

        return $result !== '' ? $result : 'برای پیگیری این رویداد با مشتری تماس بگیرید.';
    }
}