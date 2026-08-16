<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Automation\Entities\Campaign;
use Modules\Automation\Entities\Workflow;
use Modules\Core\Entities\Activity;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\CustomerMessage;
use Modules\Core\Entities\Setting;
use Modules\Core\Entities\Ticket;
use Modules\Core\Support\Jalali;
use Modules\Core\Support\Num;
use Modules\IranPack\Entities\TaxInvoice;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\LoyaltyTransaction;

class ActionExecutionService
{
    protected function logAiAction(
        string $action,
        $targetId,
        string $details,
        array $params
    ): void {
        try {
            if (! Schema::hasTable('ai_action_logs')) {
                Log::info('AI action: ' . $action, [
                    'target_id' => $targetId,
                    'details' => $details,
                    'params' => $params,
                ]);

                return;
            }

            DB::table('ai_action_logs')->insert([
                'action' => $action,
                'target_id' => (string) $targetId,
                'details' => $details,
                'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'executed_at' => now(),
                'user_id' => auth()->id() ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI Audit Log Error: ' . $e->getMessage());
        }
    }

    public function executeCreateCustomer(array $params): string
    {
        $name = trim((string) ($params['full_name'] ?? ''));
        $phone = trim((string) ($params['phone'] ?? ''));

        if ($name === '' || $phone === '') {
            return 'نام و شماره موبایل مشتری معتبر نیست.';
        }

        $existing = Customer::where('phone', $phone)->first();

        if ($existing) {
            return 'مشتری با این شماره موبایل از قبل در سامانه ثبت شده است.';
        }

        $customer = Customer::create([
            'full_name' => $name,
            'phone' => $phone,
            'source' => 'دستیار هوشمند',
        ]);

        $this->logAiAction(
            'create_customer',
            $customer->id,
            "ایجاد مشتری جدید: {$customer->full_name}",
            $params
        );

        return '👤 مشتری «'
            . $customer->full_name
            . '» با موفقیت ثبت شد. شناسه: '
            . Num::fa($customer->id);
    }

    public function executeCreateCampaign(array $params): string
    {
        $campaign = Campaign::create([
            'name' => $params['name'] ?? 'کمپین جدید',
            'referral_slug' => $params['referral_slug'] ?? Str::random(8),
            'channel' => $params['channel'] ?? 'whatsapp',
            'segment' => $params['segment'] ?? 'all',
            'rfm_group' => $params['rfm_group'] ?? 'all',
            'message' => $params['message'] ?? 'پیام پیش‌فرض',
            'status' => 'draft',
            'total' => 0,
            'sent' => 0,
            'reward_rules' => $params['reward_rules'] ?? [],
            'meta' => array_merge(
                ['created_by_assistant' => true],
                $params['meta'] ?? []
            ),
        ]);

        $this->logAiAction(
            'create_campaign',
            $campaign->id,
            "ایجاد کمپین جدید: {$campaign->name}",
            $params
        );

        return "✨ کمپین «{$campaign->name}» با موفقیت به‌صورت پیش‌نویس ثبت شد. شناسه: "
            . Num::fa($campaign->id);
    }

    public function executeCreateCoupon(array $params): string
    {
        $code = $params['code'] ?? Str::upper(Str::random(6));

        if (! Schema::hasTable('loyalty_coupons')) {
            return 'جدول کدهای تخفیف باشگاه هنوز در پایگاه داده وجود ندارد.';
        }

        DB::table('loyalty_coupons')->insert([
            'code' => $code,
            'title' => $params['title'] ?? 'تخفیف ویژه',
            'discount_type' => $params['type'] ?? 'percent',
            'discount_value' => $params['value'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logAiAction(
            'create_coupon',
            $code,
            "ایجاد کد تخفیف: {$code}",
            $params
        );

        return "🎁 کد تخفیف «{$code}» با موفقیت ایجاد شد.";
    }

    public function executeCreateMission(array $params): string
    {
        if (! Schema::hasTable('loyalty_missions')) {
            return 'جدول مأموریت‌های باشگاه هنوز در پایگاه داده وجود ندارد.';
        }

        $id = DB::table('loyalty_missions')->insertGetId([
            'title' => $params['title'] ?? 'مأموریت جدید',
            'description' => $params['description'] ?? 'ثبت شده توسط دستیار هوشمند',
            'event' => $params['event'] ?? 'orders_count',
            'target' => $params['target'] ?? 1,
            'reward_type' => $params['reward_type'] ?? 'points_fixed',
            'reward_value' => $params['reward'] ?? ($params['reward_value'] ?? 50),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logAiAction(
            'create_mission',
            $id,
            "ایجاد مأموریت جدید با شناسه {$id}",
            $params
        );

        return "🎮 مأموریت جدید با شناسه "
            . Num::fa($id)
            . " فعال شد.";
    }

    public function executeToggleWorkflow(int $id, string $status): string
    {
        $workflow = Workflow::find($id);

        if (! $workflow) {
            return 'اتوماسیون مورد نظر پیدا نشد.';
        }

        $workflow->update([
            'is_active' => $status === 'active',
        ]);

        $state = $status === 'active'
            ? 'فعال'
            : 'غیرفعال';

        $this->logAiAction(
            'toggle_workflow',
            $id,
            "تغییر وضعیت اتوماسیون «{$workflow->name}» به {$state}",
            ['status' => $status]
        );

        return "✅ وضعیت اتوماسیون «{$workflow->name}» به {$state} تغییر یافت.";
    }

    public function executeAdjustLoyaltyPoints(array $params): string
    {
        $customerId = (int) ($params['customer_id'] ?? 0);
        $amount = (int) ($params['amount'] ?? 0);
        $reason = $params['reason'] ?? 'تغییر توسط دستیار هوشمند';

        if ($customerId <= 0 || $amount === 0) {
            return 'شناسه مشتری یا مقدار امتیاز معتبر نیست.';
        }

        $member = LoyaltyMember::firstOrCreate(
            ['customer_id' => $customerId],
            [
                'points' => 0,
                'wallet_balance' => 0,
                'tier_id' => null,
                'referral_code' => Str::upper(Str::random(8)),
            ]
        );

        DB::transaction(function () use ($member, $amount, $reason) {
            $newBalance = max(0, (int) $member->points + $amount);

            $member->update([
                'points' => $newBalance,
            ]);

            LoyaltyTransaction::create([
                'member_id' => $member->id,
                'kind' => 'point',
                'direction' => $amount >= 0 ? 'credit' : 'debit',
                'amount' => abs($amount),
                'balance_after' => $newBalance,
                'reason' => $reason,
                'ref_type' => 'assistant',
                'ref_id' => auth()->id(),
            ]);
        });

        $action = $amount >= 0 ? 'افزوده' : 'کسر';

        $this->logAiAction(
            'adjust_points',
            $customerId,
            "{$action} شدن " . abs($amount) . " امتیاز برای مشتری {$customerId}",
            $params
        );

        return "💎 مقدار "
            . Num::fa(abs($amount))
            . " امتیاز با موفقیت {$action} شد.";
    }

    public function executeSendMessage(array $params): string
    {
        $customerId = (int) ($params['customer_id'] ?? 0);
        $message = trim((string) ($params['message'] ?? ''));

        if ($customerId <= 0 || $message === '') {
            return 'شناسه مشتری یا متن پیام معتبر نیست.';
        }

        CustomerMessage::create([
            'customer_id' => $customerId,
            'sender' => 'bot',
            'message' => $message,
        ]);

        $this->logAiAction(
            'send_message',
            $customerId,
            "ارسال پیام به مشتری {$customerId}",
            $params
        );

        return '✉️ پیام با موفقیت برای مشتری ثبت شد.';
    }

    public function executeUpdateCustomerTag(array $params): string
    {
        $customerId = (int) ($params['customer_id'] ?? 0);
        $tag = trim((string) ($params['tag'] ?? ''));
        $action = $params['action'] ?? 'add';

        if ($customerId <= 0 || $tag === '') {
            return 'شناسه مشتری یا نام برچسب معتبر نیست.';
        }

        if (! Schema::hasTable('customer_tags')) {
            return 'جدول برچسب‌های مشتریان هنوز در پایگاه داده وجود ندارد.';
        }

        if ($action === 'add') {
            DB::table('customer_tags')->updateOrInsert(
                [
                    'customer_id' => $customerId,
                    'tag' => $tag,
                ],
                [
                    'created_at' => now(),
                ]
            );

            $result = "برچسب «{$tag}» اضافه شد.";
        } else {
            DB::table('customer_tags')
                ->where('customer_id', $customerId)
                ->where('tag', $tag)
                ->delete();

            $result = "برچسب «{$tag}» حذف شد.";
        }

        $this->logAiAction(
            'update_tag',
            $customerId,
            "تغییر برچسب مشتری {$customerId}: {$result}",
            $params
        );

        return "🏷️ {$result}";
    }

    public function executeScheduleReminder(array $params): string
    {
        $customerId = (int) ($params['customer_id'] ?? 0);
        $text = trim((string) ($params['text'] ?? ''));
        $date = (string) ($params['date'] ?? 'در اسرع وقت');

        if ($customerId <= 0 || $text === '') {
            return 'شناسه مشتری یا متن یادآور معتبر نیست.';
        }

        $dueAt = now();

        if (str_contains($date, 'فردا')) {
            $dueAt = now()->addDay();
        } elseif (str_contains($date, 'پس فردا')) {
            $dueAt = now()->addDays(2);
        } elseif (str_contains($date, 'هفته آینده')) {
            $dueAt = now()->addWeek();
        }

        Activity::create([
            'subject_type' => 'customer',
            'subject_id' => $customerId,
            'type' => 'task',
            'body' => $text,
            'user_id' => auth()->id(),
            'due_at' => $dueAt,
            'done' => false,
        ]);

        $this->logAiAction(
            'schedule_reminder',
            $customerId,
            "ثبت یادآور برای مشتری {$customerId}: {$text}",
            $params
        );

        return "⏰ یادآور با موفقیت برای {$date} ثبت شد.";
    }

    public function executeCreateProforma(array $params): string
    {
        $total = (int) round((float) ($params['total_amount'] ?? 0));

        if ($total <= 0) {
            return 'مبلغ پیش‌فاکتور معتبر نیست.';
        }

        $customerId = $params['customer_id'] ?? null;

        if ($customerId && ! Customer::whereKey($customerId)->exists()) {
            return 'مشتری مورد نظر برای پیش‌فاکتور پیدا نشد.';
        }

        $discountType = ($params['discount_type'] ?? 'fixed') === 'percent'
            ? 'percent'
            : 'fixed';

        $discountValue = (float) ($params['discount_value'] ?? 0);

        $discount = $discountType === 'percent'
            ? (int) round($total * min(100, $discountValue) / 100)
            : (int) round($discountValue);

        $discount = max(0, min($total, $discount));
        $vat = (int) round((float) ($params['vat_amount'] ?? 0));
        $payable = max(0, $total - $discount + $vat);
        $title = trim((string) ($params['title'] ?? 'پیش‌فاکتور ثبت‌شده توسط دستیار'));

        $invoice = TaxInvoice::create([
            'customer_id' => $customerId,
            'serial' => $this->makeProformaSerial(),
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
                'title' => $title,
                'amount' => $total,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount' => $discount,
                'vat' => $vat,
                'payable' => $payable,
            ]],
            'notes' => $params['notes'] ?? 'ثبت‌شده توسط دستیار هوشمند',
            'issued_at' => now(),
            'due_at' => isset($params['due_at'])
                ? Jalali::parse($params['due_at'])
                : null,
        ]);

        $this->logAiAction(
            'create_proforma',
            $invoice->id,
            "ایجاد پیش‌فاکتور {$invoice->serial}",
            $params
        );

        return "🧾 پیش‌فاکتور «{$invoice->serial}» با موفقیت به‌صورت پیش‌نویس ثبت شد.";
    }

    public function executeCreateTicket(array $params): string
    {
        $subject = trim((string) ($params['subject'] ?? 'تیکت ثبت‌شده توسط دستیار'));
        $message = trim((string) ($params['message'] ?? $subject));

        $priority = in_array(
            ($params['priority'] ?? 'normal'),
            ['low', 'normal', 'high', 'urgent'],
            true
        )
            ? $params['priority']
            : 'normal';

        $customerId = $params['customer_id'] ?? null;

        if ($customerId && ! Customer::whereKey($customerId)->exists()) {
            return 'مشتری مورد نظر برای تیکت پیدا نشد.';
        }

        $ticket = Ticket::create([
            'customer_id' => $customerId,
            'subject' => $subject,
            'priority' => $priority,
            'department' => $params['department'] ?? 'پشتیبانی',
            'status' => 'open',
            'staff_unread' => true,
            'customer_unread' => false,
            'last_reply_at' => now(),
            'sla_due_at' => now()->addHours($this->slaHours($priority)),
        ]);

        $ticket->replies()->create([
            'author' => 'staff',
            'author_name' => optional(auth()->user())->name ?? 'دستیار هوشمند',
            'message' => $message,
        ]);

        $this->logAiAction(
            'create_ticket',
            $ticket->id,
            "ایجاد تیکت {$ticket->subject}",
            $params
        );

        return "🎧 تیکت شماره "
            . Num::fa($ticket->id)
            . " با موفقیت ثبت شد.";
    }

    public function executeReplyTicket(array $params): string
    {
        $ticketId = (int) ($params['ticket_id'] ?? 0);
        $message = trim((string) ($params['message'] ?? ''));
        $isInternal = (bool) ($params['is_internal'] ?? false);

        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            return 'تیکت مورد نظر پیدا نشد.';
        }

        if ($message === '') {
            return 'متن پاسخ تیکت خالی است.';
        }

        $ticket->replies()->create([
            'author' => 'staff',
            'is_internal' => $isInternal,
            'author_name' => optional(auth()->user())->name ?? 'دستیار هوشمند',
            'message' => $message,
        ]);

        $ticket->update([
            'status' => $isInternal ? $ticket->status : 'answered',
            'customer_unread' => ! $isInternal,
            'staff_unread' => false,
            'last_reply_at' => now(),
            'first_response_at' => $ticket->first_response_at
                ?: ($isInternal ? null : now()),
        ]);

        $this->logAiAction(
            'reply_ticket',
            $ticket->id,
            "ثبت پاسخ برای تیکت {$ticket->id}",
            $params
        );

        return "💬 پاسخ تیکت شماره "
            . Num::fa($ticket->id)
            . " با موفقیت ثبت شد.";
    }

    public function executeUpdateTicketStatus(array $params): string
    {
        $ticketId = (int) ($params['ticket_id'] ?? 0);
        $status = (string) ($params['status'] ?? 'pending');
        $allowed = ['open', 'pending', 'answered', 'closed'];

        if (! in_array($status, $allowed, true)) {
            return 'وضعیت تیکت معتبر نیست.';
        }

        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            return 'تیکت مورد نظر پیدا نشد.';
        }

        $ticket->update([
            'status' => $status,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);

        $labels = [
            'open' => 'باز',
            'pending' => 'در حال بررسی',
            'answered' => 'پاسخ داده‌شده',
            'closed' => 'بسته‌شده',
        ];

        $this->logAiAction(
            'update_ticket_status',
            $ticket->id,
            "تغییر وضعیت تیکت {$ticket->id} به {$status}",
            $params
        );

        return "🔁 وضعیت تیکت شماره "
            . Num::fa($ticket->id)
            . " به «"
            . ($labels[$status] ?? $status)
            . "» تغییر کرد.";
    }

    protected function makeProformaSerial(): string
    {
        $prefix = trim((string) Setting::get('proforma_serial_prefix', 'پف'));
        $separator = trim((string) Setting::get('proforma_serial_separator', '-'));
        $padding = max(3, min(8, (int) Setting::get('proforma_serial_padding', 5)));
        $dateMode = (string) Setting::get('proforma_serial_date', 'jalali');
        $sequence = TaxInvoice::where('invoice_kind', 'proforma')->count() + 1;
        $parts = [];

        if ($prefix !== '') {
            $parts[] = $prefix;
        }

        if ($dateMode === 'jalali') {
            $now = now();

            [$jy, $jm, $jd] = Jalali::toJalali(
                (int) $now->format('Y'),
                (int) $now->format('n'),
                (int) $now->format('j')
            );

            $parts[] = Num::fa(sprintf('%04d%02d%02d', $jy, $jm, $jd));
        }

        $parts[] = Num::fa(str_pad(
            (string) $sequence,
            $padding,
            '0',
            STR_PAD_LEFT
        ));

        return implode(
            $separator !== '' ? $separator : '-',
            $parts
        );
    }

    protected function slaHours(string $priority): int
    {
        return match ($priority) {
            'urgent' => 2,
            'high' => 6,
            'low' => 48,
            default => 24,
        };
    }
}