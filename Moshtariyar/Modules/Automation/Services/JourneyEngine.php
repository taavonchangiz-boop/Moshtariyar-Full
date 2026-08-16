<?php

namespace Modules\Automation\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Order;
use Modules\Core\Entities\Segment;
use Modules\Core\Entities\Ticket;
use Modules\Core\Services\Insights;
use Modules\Core\Support\Money;
use Modules\Core\Support\Num;
use Modules\Automation\Entities\JourneyExecution;
use Modules\Automation\Entities\JourneyStep;
use Modules\Automation\Entities\Workflow;
use Modules\IranPack\Managers\SmsManager;
use Modules\IranPack\Messaging\MessagingChannels;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Automation\Jobs\RunJourneyExecution;

/**
 * موتور اجرای سفرهای مشتری
 * مسئول مدیریت گام‌ها، بررسی شرط‌ها و اجرای عملیات خودکار.
 */
class JourneyEngine
{
    /**
     * شروع سفرهای مشتری بر اساس رویداد رخ‌داده.
     * این متد توسط موتور اتوماسیون صدا زده می‌شود و نباید باعث توقف همگام‌سازی سفارش شود.
     */
    public function startForEvent(string $event, Customer $customer, ?Order $order = null, array $context = []): void
    {
        try {
            $journeys = Workflow::with('steps')
                ->where('is_active', true)
                ->where('type', 'journey')
                ->where(function ($query) use ($event) {
                    $query->where('trigger_event', $event)
                        ->orWhere('event', $event);
                })
                ->get();

            if ($journeys->isEmpty()) {
                return;
            }

            foreach ($journeys as $journey) {
                if ($journey->trigger_segment_id && ! $customer->segments()->where('segments.id', $journey->trigger_segment_id)->exists()) {
                    continue;
                }

                $steps = $journey->steps->sortBy('step_order')->values();

                if ($steps->isEmpty()) {
                    continue;
                }

                $alreadyActive = JourneyExecution::where('workflow_id', $journey->id)
                    ->where('customer_id', $customer->id)
                    ->where('status', 'active')
                    ->exists();

                if ($alreadyActive) {
                    continue;
                }

                $payload = array_merge($context, [
                    'event' => $event,
                    'order_id' => $order?->id,
                    'started_at' => now()->toDateTimeString(),
                ]);

                $execution = JourneyExecution::create([
                    'workflow_id' => $journey->id,
                    'customer_id' => $customer->id,
                    'current_step_order' => (int) ($steps->first()->step_order ?? 0),
                    'status' => 'active',
                    'context' => $payload,
                    'next_run_at' => now(),
                ]);

                RunJourneyExecution::dispatch($execution->id);
            }
        } catch (\Throwable $exception) {
            Log::warning('شروع سفر مشتری با خطا مواجه شد: ' . $exception->getMessage(), [
                'event' => $event,
                'customer_id' => $customer->id ?? null,
                'order_id' => $order?->id,
            ]);
        }
    }

    /**
     * سازگاری با کار اجرای سفر.
     */
    public function processExecution(JourneyExecution $execution): void
    {
        $this->execute((int) $execution->id);
    }

    /**
     * اجرای یک گام از سفر برای مشتری
     */
    public function execute(int $executionId): void
    {
        $execution = JourneyExecution::with(['customer', 'workflow.steps'])->find($executionId);

        if (! $execution) {
            Log::error('خطای اتوماسیون: اجرای سفر پیدا نشد.', ['execution_id' => $executionId]);
            return;
        }

        $workflow = $execution->workflow;

        if (! $workflow) {
            $execution->update([
                'status' => 'completed',
                'next_run_at' => null,
            ]);

            return;
        }

        $steps = $workflow->steps->sortBy('step_order');
        $step = $steps->firstWhere('step_order', $execution->current_step_order) ?: $steps->first();

        if (! $step) {
            $execution->update([
                'status' => 'completed',
                'next_run_at' => null,
            ]);

            return;
        }

        $context = is_array($execution->context) ? $execution->context : [];
        $done = $context['done_steps'] ?? [];
        $next = $this->nextStepOrder($steps, $step->step_order);

        if ($step->type === 'wait') {
            $hours = max(1, (int) ($step->wait_hours ?: 1));

            $execution->update([
                'current_step_order' => $next ?? $step->step_order,
                'next_run_at' => now()->addHours($hours),
            ]);

            if ($next) {
                RunJourneyExecution::dispatch($execution->id)->delay(now()->addHours($hours));
            } else {
                $execution->update([
                    'status' => 'completed',
                    'next_run_at' => null,
                ]);
            }

            return;
        }

        if ($step->type === 'condition') {
            $pass = $this->evaluateCondition($execution, $step);

            $target = $pass
                ? ($step->branch_yes_step_order ?? $next)
                : ($step->branch_no_step_order ?? null);

            if ($target === null) {
                $execution->update([
                    'status' => 'completed',
                    'next_run_at' => null,
                ]);
            } else {
                $execution->update([
                    'current_step_order' => $target,
                    'next_run_at' => now(),
                ]);

                RunJourneyExecution::dispatch($execution->id);
            }

            return;
        }

        if ($step->type === 'action') {
            if (! isset($done[$step->step_order])) {
                $this->runAction($execution, $step);

                $done[$step->step_order] = now()->toDateTimeString();
                $context['done_steps'] = $done;
                $execution->context = $context;
                $execution->save();
            }

            if ($next === null) {
                $execution->update([
                    'status' => 'completed',
                    'next_run_at' => null,
                ]);
            } else {
                $execution->update([
                    'current_step_order' => $next,
                    'next_run_at' => now(),
                ]);

                RunJourneyExecution::dispatch($execution->id);
            }

            return;
        }

        $execution->update([
            'status' => 'completed',
            'next_run_at' => null,
        ]);
    }

    private function nextStepOrder($steps, int $current): ?int
    {
        return $steps->first(fn ($step) => $step->step_order > $current)?->step_order;
    }

    private function evaluateCondition(JourneyExecution $execution, JourneyStep $step): bool
    {
        $customer = $execution->customer;

        if (! $customer) {
            return false;
        }

        $context = is_array($execution->context) ? $execution->context : [];
        $order = ! empty($context['order_id'])
            ? Order::find($context['order_id'])
            : null;

        $field = $step->condition_field;
        $value = (string) ($step->condition_value ?? '');

        return match ($field) {
            'in_segment' => $this->conditionInSegment($customer, $value),
            'rfm_group' => $this->conditionRfmGroup($customer, $value),
            'bought_after_trigger' => $this->conditionBoughtAfterTrigger($customer, $order),
            'days_since_last_purchase' => $this->conditionDaysSinceLastPurchase($customer, $value),
            'birthday_within' => $this->conditionBirthdayWithin($customer, (int) ($step->condition_value ?: 7)),
            default => true,
        };
    }

    private function conditionInSegment(Customer $customer, string $value): bool
    {
        if ($value === '') {
            return true;
        }

        if (ctype_digit($value)) {
            return $customer->segments()->where('segments.id', (int) $value)->exists();
        }

        return $customer->segments()->where('name', $value)->exists();
    }

    private function conditionRfmGroup(Customer $customer, string $value): bool
    {
        if ($value === '') {
            return true;
        }

        try {
            $rfm = Insights::rfm($customer);
            [$segment] = Insights::segment($rfm);
            $normalized = strtolower(trim($value));

            return strtolower($segment) === $normalized || $segment === $value;
        } catch (\Throwable $exception) {
            Log::error('خطای تحلیل رفتار مشتری در اتوماسیون: ' . $exception->getMessage(), [
                'customer_id' => $customer->id,
            ]);

            return false;
        }
    }

    private function conditionBoughtAfterTrigger(Customer $customer, ?Order $order): bool
    {
        if (! $order) {
            return $customer->orders()
                ->where('placed_at', '>=', now()->subDays(90))
                ->exists();
        }

        return $customer->orders()
            ->where('id', '>', $order->id)
            ->whereIn('status', ['processing', 'completed'])
            ->exists();
    }

    private function conditionDaysSinceLastPurchase(Customer $customer, string $value): bool
    {
        $days = (int) $value;

        if ($days <= 0) {
            return true;
        }

        $last = $customer->orders()
            ->whereIn('status', ['processing', 'completed'])
            ->latest('placed_at')
            ->first();

        if (! $last) {
            return false;
        }

        return now()->diffInDays($last->placed_at) >= $days;
    }

    private function conditionBirthdayWithin(Customer $customer, int $days): bool
    {
        $meta = is_array($customer->meta) ? $customer->meta : [];
        $birth = $meta['birth_date'] ?? $meta['birthday'] ?? null;

        if (! $birth) {
            return false;
        }

        try {
            $birthDate = \Carbon\Carbon::parse($birth);
            $next = now()->copy()->month($birthDate->month)->day($birthDate->day);

            if ($next->isPast()) {
                $next->addYear();
            }

            return now()->diffInDays($next) <= $days;
        } catch (\Throwable $exception) {
            Log::error('خطای تاریخ تولد مشتری: ' . $exception->getMessage(), [
                'customer_id' => $customer->id,
            ]);

            return false;
        }
    }

    private function runAction(JourneyExecution $execution, JourneyStep $step): void
    {
        $customer = $execution->customer;

        if (! $customer) {
            Log::error('مشتری برای اجرای عملیات پیدا نشد.', [
                'execution_id' => $execution->id,
            ]);

            return;
        }

        $context = is_array($execution->context) ? $execution->context : [];
        $order = ! empty($context['order_id'])
            ? Order::with('customer')->find($context['order_id'])
            : null;

        $member = LoyaltyMember::where('customer_id', $customer->id)->first();
        $text = $this->renderTemplate(
            $step->action_template ?: '',
            $customer,
            $order,
            $member
        );

        try {
            if ($step->action_type === 'sms' && $customer->phone) {
                app(SmsManager::class)->send($customer->phone, $text);
            } elseif ($step->action_type === 'email' && $customer->email) {
                Mail::raw($text, function ($mail) use ($customer) {
                    $mail->to($customer->email)->subject(config('brand.name', 'مشتری‌یار'));
                });
            } elseif ($step->action_type === 'messenger' && $customer->phone) {
                $channel = $step->action_channel ?: 'all';

                $channel === 'all'
                    ? MessagingChannels::broadcast($customer->phone, $text)
                    : MessagingChannels::sendVia($channel, $customer->phone, $text);
            } elseif ($step->action_type === 'coupon') {
                $member = $member ?? app(LoyaltyService::class)->ensureMember($customer);
                $coupon = $this->parseCouponFromText($step->action_template ?: '');

                app(LoyaltyService::class)->createCoupon(
                    $member,
                    $step->label ?: 'هدیه سفر مشتری',
                    $coupon['type'],
                    $coupon['value'],
                    'journey'
                );
            } elseif ($step->action_type === 'add_segment') {
                $target = trim((string) ($step->action_template ?: ''));

                if ($target) {
                    $segment = ctype_digit($target)
                        ? Segment::find((int) $target)
                        : Segment::where('name', $target)->first();

                    if ($segment) {
                        $segment->customers()->syncWithoutDetaching([$customer->id]);
                    } else {
                        Log::warning('گروه مورد نظر برای افزودن یافت نشد.', [
                            'segment_name' => $target,
                        ]);
                    }
                }
            } elseif ($step->action_type === 'notify_admin') {
                Ticket::create([
                    'customer_id' => $customer->id,
                    'subject' => 'یادآوری سفر: ' . ($step->label ?: 'سفر خودکار'),
                    'priority' => 'normal',
                    'department' => 'پشتیبانی',
                    'status' => 'open',
                    'last_reply_at' => now(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('خطا در اجرای عملیات اتوماسیون: ' . $exception->getMessage(), [
                'execution_id' => $execution->id,
                'step_id' => $step->id,
                'action_type' => $step->action_type,
                'customer_id' => $customer->id,
            ]);
        }
    }

    private function renderTemplate(
        string $template,
        Customer $customer,
        ?Order $order,
        ?LoyaltyMember $member
    ): string {
        $map = [
            '{name}' => $customer->full_name ?? $customer->name ?? '',
            '{نام}' => $customer->full_name ?? $customer->name ?? '',
            '{brand}' => config('brand.name', 'مشتری‌یار'),
            '{برند}' => config('brand.name', 'مشتری‌یار'),
            '{points}' => $member ? Num::fa($member->points_lifetime ?? 0) : '۰',
            '{امتیاز}' => $member ? Num::fa($member->points_lifetime ?? 0) : '۰',
            '{wallet}' => $member ? Money::show($member->wallet_balance ?? 0) : '۰',
            '{کیف_پول}' => $member ? Money::show($member->wallet_balance ?? 0) : '۰',
            '{referral_link}' => $member?->referral_link ?: route('club.register', [], false),
            '{لینک_دعوت}' => $member?->referral_link ?: route('club.register', [], false),
            '{phone}' => $customer->phone ?? '',
            '{email}' => $customer->email ?? '',
        ];

        if ($order) {
            $map['{order_id}'] = $order->id;
            $map['{سفارش}'] = $order->number ?: $order->id;
            $map['{order}'] = $order->number ?: $order->id;
            $map['{مبلغ}'] = Money::show($order->total ?? 0);
            $map['{total}'] = Money::show($order->total ?? 0);
        }

        $output = strtr($template, $map);

        return trim($output) ?: 'پیام خودکار از طرف فروشگاه';
    }

    private function parseCouponFromText(string $text): array
    {
        $text = str_replace('٪', '%', $text);

        if (preg_match('/(\d+)\s*%/u', $text, $match)) {
            return ['type' => 'percent', 'value' => (int) $match[1]];
        }

        if (preg_match('/(\d+)\s*(تومان|ریال)/u', $text, $match)) {
            return ['type' => 'fixed', 'value' => (int) $match[1]];
        }

        if (preg_match('/(\d+)/u', $text, $match)) {
            return ['type' => 'percent', 'value' => (int) $match[1]];
        }

        return ['type' => 'percent', 'value' => 10];
    }
}