<?php

namespace Modules\Loyalty\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\IranPack\Managers\SmsManager;
use Modules\IranPack\Messaging\MessagingChannels;
use Modules\Loyalty\Entities\CustomerPortalNotification;
use Modules\Loyalty\Entities\LoyaltyCoupon;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\WheelPrize;
use Modules\Loyalty\Entities\WheelSpin;

/**
 * گردونه شانس: انتخاب جایزه به‌صورت تصادفی وزن‌دار، اعطای پاداش و ارسال پیام جایزه.
 */
class WheelService
{
    public function __construct(private LoyaltyService $loyalty)
    {
    }

    /** چرخاندن گردونه برای یک عضو */
    public function spin(LoyaltyMember $member): array
    {
        $member->loadMissing('customer');

        $prizes = WheelPrize::where('is_active', true)->get();
        if ($prizes->isEmpty()) {
            return ['ok' => false, 'message' => 'هیچ جایزه‌ای برای گردونه تعریف نشده است.'];
        }

        $prize = $this->pickWeighted($prizes);
        $coupon = null;

        if ($prize->prize_type === 'point' && (int) $prize->amount > 0) {
            $this->loyalty->addPoints($member, (int) $prize->amount, 'برنده گردونه شانس: ' . $prize->title, 'wheel', $prize->id);
        } elseif ($prize->prize_type === 'wallet' && (int) $prize->amount > 0) {
            $this->loyalty->adjustWallet($member, (int) $prize->amount, 'برنده گردونه شانس: ' . $prize->title, 'wheel', $prize->id);
        } elseif ($prize->prize_type === 'coupon') {
            $coupon = $this->createWheelCoupon($member, $prize);
        }

        $channels = $prize->selectedDeliveryChannels();

        $spin = WheelSpin::create([
            'member_id' => $member->id,
            'prize_id' => $prize->id,
            'prize_title' => $prize->title,
            'prize_type' => $prize->prize_type,
            'amount' => (int) $prize->amount,
            'prize_image' => $prize->image,
            'coupon_id' => $coupon?->id,
            'delivery_channels' => $channels,
            'delivery_status' => 'pending',
        ]);

        $deliveryReport = $this->deliverPrize($member, $prize, $spin, $coupon, $channels);
        $spin->update([
            'delivery_report' => $deliveryReport,
            'delivery_status' => $this->deliveryStatus($deliveryReport),
        ]);

        return [
            'ok' => true,
            'prize' => $prize->title,
            'type' => $prize->prize_type,
            'amount' => (int) $prize->amount,
            'coupon' => $coupon,
            'spin' => $spin->fresh(['coupon', 'prize']),
        ];
    }

    /** انتخاب تصادفی وزن‌دار بر اساس شانس هر جایزه */
    private function pickWeighted($prizes): WheelPrize
    {
        $total = max(1, (int) $prizes->sum('chance'));
        $rand = random_int(1, $total);
        $acc = 0;

        foreach ($prizes as $prize) {
            $acc += max(1, (int) $prize->chance);
            if ($rand <= $acc) {
                return $prize;
            }
        }

        return $prizes->first();
    }

    private function createWheelCoupon(LoyaltyMember $member, WheelPrize $prize): LoyaltyCoupon
    {
        $code = strtoupper('MY-WH-' . Str::random(7));
        while (LoyaltyCoupon::where('code', $code)->exists()) {
            $code = strtoupper('MY-WH-' . Str::random(7));
        }

        return LoyaltyCoupon::create([
            'member_id' => $member->id,
            'code' => $code,
            'title' => 'جایزه گردونه: ' . $prize->title,
            'discount_type' => $prize->coupon_discount_type === 'percent' ? 'percent' : 'fixed',
            'discount_value' => (float) ($prize->coupon_discount_value ?: $prize->amount),
            'min_order_total' => 0,
            'usage_limit' => 1,
            'source' => 'wheel',
            'expires_at' => now()->addDays(max(1, (int) ($prize->coupon_expires_days ?: 7))),
        ]);
    }

    private function deliverPrize(LoyaltyMember $member, WheelPrize $prize, WheelSpin $spin, ?LoyaltyCoupon $coupon, array $channels): array
    {
        $report = [];
        $message = $this->buildMessage($member, $prize, $spin, $coupon);
        $customer = $member->customer;

        if (in_array('portal', $channels, true)) {
            try {
                CustomerPortalNotification::create([
                    'customer_id' => $member->customer_id,
                    'title' => 'جایزه گردونه شانس شما',
                    'body' => $message,
                    'type' => 'wheel',
                    'url' => route('club.rewards'),
                ]);
                $report['portal'] = true;
            } catch (\Throwable $exception) {
                report($exception);
                $report['portal'] = false;
            }
        }

        if (in_array('sms', $channels, true)) {
            try {
                $phone = trim((string) ($customer?->phone ?? ''));
                $report['sms'] = $phone !== '' ? app(SmsManager::class)->send($phone, $message) : false;
            } catch (\Throwable $exception) {
                report($exception);
                $report['sms'] = false;
            }
        }

        if (in_array('email', $channels, true)) {
            try {
                $email = trim((string) ($customer?->email ?? ''));
                if ($email !== '') {
                    Mail::raw($message, fn ($mail) => $mail->to($email)->subject('جایزه گردونه شانس'));
                    $report['email'] = true;
                } else {
                    $report['email'] = false;
                }
            } catch (\Throwable $exception) {
                report($exception);
                $report['email'] = false;
            }
        }

        foreach (['whatsapp', 'telegram', 'bale', 'rubika', 'eitaa'] as $channel) {
            if (! in_array($channel, $channels, true)) {
                continue;
            }

            try {
                $target = $this->channelTarget($member, $channel);
                $report[$channel] = $target && MessagingChannels::isEnabled($channel)
                    ? MessagingChannels::sendVia($channel, $target, $message)
                    : false;
            } catch (\Throwable $exception) {
                report($exception);
                $report[$channel] = false;
            }
        }

        return $report;
    }

    private function buildMessage(LoyaltyMember $member, WheelPrize $prize, WheelSpin $spin, ?LoyaltyCoupon $coupon): string
    {
        $customer = $member->customer;
        $code = $coupon?->code ?: '';
        $link = route('club.rewards');

        $default = 'تبریک {name} عزیز! شما در گردونه شانس برنده «{prize}» شدید. {reward_text} برای مشاهده جایزه وارد باشگاه مشتریان شوید: {link}';
        $template = trim((string) ($prize->delivery_message ?: $default));

        $rewardText = match ($prize->prize_type) {
            'point' => 'مقدار ' . number_format((int) $prize->amount) . ' امتیاز به حساب شما اضافه شد.',
            'wallet' => 'مقدار ' . number_format((int) $prize->amount) . ' به کیف پول شما اضافه شد.',
            'coupon' => $code ? 'کد تخفیف اختصاصی شما: ' . $code : 'کد تخفیف اختصاصی برای شما ساخته شد.',
            default => 'نتیجه چرخش شما در پروفایل ثبت شد.',
        };

        return str_replace(
            ['{name}', '{prize}', '{amount}', '{code}', '{reward_text}', '{link}', '{spin_id}'],
            [
                $customer?->full_name ?: 'مشتری',
                $prize->title,
                number_format((int) $prize->amount),
                $code,
                $rewardText,
                $link,
                (string) $spin->id,
            ],
            $template
        );
    }

    private function deliveryStatus(array $report): string
    {
        if (empty($report)) {
            return 'internal';
        }

        $success = collect($report)->filter(fn ($value) => $value === true)->count();
        if ($success === count($report)) {
            return 'done';
        }

        return $success > 0 ? 'partial' : 'failed';
    }

    private function channelTarget(LoyaltyMember $member, string $channel): ?string
    {
        $customer = $member->customer;
        $meta = is_array($customer?->meta ?? null) ? $customer->meta : [];

        if ($channel === 'whatsapp') {
            return $customer?->phone ?: null;
        }

        return $meta[$channel . '_chat_id'] ?? $meta[$channel] ?? null;
    }
}