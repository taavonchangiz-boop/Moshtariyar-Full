<?php

namespace Modules\Loyalty\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Core\Entities\Order;
use Modules\Loyalty\Entities\LoyaltyCoupon;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\LoyaltyMission;
use Modules\Loyalty\Entities\LoyaltyMissionCompletion;
use Modules\Loyalty\Entities\LoyaltyRule;
use Modules\Loyalty\Entities\LoyaltyTransaction;

class LoyaltyService
{
    public function ensureMember(\Modules\Core\Entities\Customer $customer): LoyaltyMember
    {
        return LoyaltyMember::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'points' => 0,
                'points_lifetime' => 0,
                'wallet_balance' => 0,
                'joined_at' => now(),
                'referral_code' => $this->makeReferralCode(),
            ]
        );
    }

    /**
     * ثبت خرید و اعطای امتیاز به مشتری
     */
    public function onPurchase(Order $order): void
    {
        try {
            if (! $order->customer_id || ! $order->customer) {
                return;
            }

            $member = $this->ensureMember($order->customer);
            $pointsToAward = (int) round(((float) $order->total) / 1000);

            if ($pointsToAward > 0) {
                $this->addPoints($member, $pointsToAward, 'خرید سفارش شماره ' . ($order->number ?: $order->id), 'order_reward', $order->id);
            }
        } catch (\Throwable $exception) {
            Log::error('Loyalty onPurchase Error: ' . $exception->getMessage());
        }
    }

    /**
     * بازگرداندن امتیازات در صورت لغو سفارش
     */
    public function rollbackPurchase(Order $order): void
    {
        try {
            if (! $order->customer_id) {
                return;
            }

            $member = LoyaltyMember::where('customer_id', $order->customer_id)->first();
            if (! $member) {
                return;
            }

            $alreadyRollback = LoyaltyTransaction::where('member_id', $member->id)
                ->where('kind', 'point')
                ->where('direction', 'debit')
                ->where('ref_type', 'rollback')
                ->where('ref_id', $order->id)
                ->exists();

            if ($alreadyRollback) {
                return;
            }

            $earned = LoyaltyTransaction::where('member_id', $member->id)
                ->where('kind', 'point')
                ->where('direction', 'credit')
                ->where('ref_type', 'order_reward')
                ->where('ref_id', $order->id)
                ->sum('amount');

            $pointsToDeduct = min((int) $member->points, (int) $earned);

            if ($pointsToDeduct > 0) {
                $this->addPoints($member, -$pointsToDeduct, 'برگشت امتیاز سفارش شماره ' . ($order->number ?: $order->id), 'rollback', $order->id);
            }
        } catch (\Throwable $exception) {
            Log::error('Loyalty rollbackPurchase Error: ' . $exception->getMessage());
        }
    }

    public function addPoints(LoyaltyMember $member, int $amount, string $reason, ?string $refType = 'manual', ?int $refId = null): LoyaltyTransaction
    {
        $current = (int) $member->points;
        $newBalance = max(0, $current + $amount);

        $member->points = $newBalance;

        if ($amount > 0) {
            $member->points_lifetime = (int) $member->points_lifetime + $amount;
        }

        if (! $member->referral_code) {
            $member->referral_code = $this->makeReferralCode();
        }

        if (! $member->joined_at) {
            $member->joined_at = now();
        }

        $member->save();

        return LoyaltyTransaction::create([
            'member_id' => $member->id,
            'kind' => 'point',
            'direction' => $amount >= 0 ? 'credit' : 'debit',
            'amount' => abs($amount),
            'balance_after' => $newBalance,
            'reason' => $reason,
            'ref_type' => $refType,
            'ref_id' => $refId,
        ]);
    }

    public function adjustWallet(LoyaltyMember $member, int|float $amount, string $reason, ?string $refType = 'manual', ?int $refId = null): LoyaltyTransaction
    {
        $amount = (int) round($amount);
        $current = (int) $member->wallet_balance;
        $newBalance = max(0, $current + $amount);

        $member->wallet_balance = $newBalance;

        if (! $member->referral_code) {
            $member->referral_code = $this->makeReferralCode();
        }

        if (! $member->joined_at) {
            $member->joined_at = now();
        }

        $member->save();

        return LoyaltyTransaction::create([
            'member_id' => $member->id,
            'kind' => 'wallet',
            'direction' => $amount >= 0 ? 'credit' : 'debit',
            'amount' => abs($amount),
            'balance_after' => $newBalance,
            'reason' => $reason,
            'ref_type' => $refType,
            'ref_id' => $refId,
        ]);
    }

    public function convertPointsToWallet(LoyaltyMember $member, int $points): bool
    {
        if ($points <= 0 || (int) $member->points < $points) {
            return false;
        }

        $this->addPoints($member, -$points, 'تبدیل امتیاز به کیف پول', 'convert');
        $this->adjustWallet($member, $points, 'افزایش کیف پول از تبدیل امتیاز', 'convert');

        return true;
    }

    public function awardPoints(int $customerId, int $amount, string $reason): bool
    {
        try {
            $customer = \Modules\Core\Entities\Customer::find($customerId);
            if (! $customer) {
                return false;
            }

            $member = $this->ensureMember($customer);
            $this->addPoints($member, $amount, $reason, 'manual');

            return true;
        } catch (\Throwable $exception) {
            Log::error('Loyalty awardPoints Error: ' . $exception->getMessage());
            return false;
        }
    }

    public function createCoupon(LoyaltyMember $member, string $title, string $discountType, int|float $discountValue, string $source = 'manual'): LoyaltyCoupon
    {
        $code = strtoupper('MY-' . Str::random(8));

        while (LoyaltyCoupon::where('code', $code)->exists()) {
            $code = strtoupper('MY-' . Str::random(8));
        }

        return LoyaltyCoupon::create([
            'member_id' => $member->id,
            'code' => $code,
            'title' => $title,
            'discount_type' => $discountType === 'percent' ? 'percent' : 'fixed',
            'discount_value' => $discountValue,
            'source' => $source,
            'usage_limit' => 1,
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * اجرای قوانین وفاداری برای یک رویداد مشخص، بدون تکرار پاداش همان قانون.
     */
    public function runRules(string $event, LoyaltyMember $member, float|int|null $baseAmount = null): void
    {
        try {
            $rules = LoyaltyRule::where('event', $event)->where('is_active', true)->get();

            foreach ($rules as $rule) {
                $alreadyApplied = LoyaltyTransaction::where('member_id', $member->id)
                    ->where('ref_type', 'loyalty_rule')
                    ->where('ref_id', $rule->id)
                    ->exists();

                if ($alreadyApplied) {
                    continue;
                }

                $rewardValue = (float) $rule->reward_value;
                $maxReward = $rule->max_reward !== null ? (int) $rule->max_reward : null;

                if ($rule->reward_type === 'points_fixed') {
                    $amount = (int) round($rewardValue);
                    if ($maxReward !== null) $amount = min($amount, $maxReward);
                    if ($amount > 0) {
                        $this->addPoints($member, $amount, $rule->name ?: 'پاداش باشگاه', 'loyalty_rule', $rule->id);
                    }
                    continue;
                }

                if ($rule->reward_type === 'cashback_fixed') {
                    $amount = (int) round($rewardValue);
                    if ($maxReward !== null) $amount = min($amount, $maxReward);
                    if ($amount > 0) {
                        $this->adjustWallet($member, $amount, $rule->name ?: 'پاداش باشگاه', 'loyalty_rule', $rule->id);
                    }
                    continue;
                }

                if ($baseAmount !== null && in_array($rule->reward_type, ['points_percent', 'cashback_percent'], true)) {
                    $amount = (int) round(((float) $baseAmount) * $rewardValue / 100);
                    if ($maxReward !== null) $amount = min($amount, $maxReward);
                    if ($amount <= 0) {
                        continue;
                    }

                    if ($rule->reward_type === 'points_percent') {
                        $this->addPoints($member, $amount, $rule->name ?: 'پاداش باشگاه', 'loyalty_rule', $rule->id);
                    } else {
                        $this->adjustWallet($member, $amount, $rule->name ?: 'پاداش باشگاه', 'loyalty_rule', $rule->id);
                    }
                }
            }
        } catch (\Throwable $exception) {
            Log::error('Loyalty runRules Error: ' . $exception->getMessage());
        }
    }

    /**
     * بررسی و ثبت مأموریت‌های مربوط به یک رویداد، بدون ثبت تکراری.
     */
    public function checkMissions(LoyaltyMember $member, string $event): void
    {
        try {
            $missions = LoyaltyMission::where('event', $event)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->get();

            foreach ($missions as $mission) {
                $alreadyDone = LoyaltyMissionCompletion::where('mission_id', $mission->id)
                    ->where('member_id', $member->id)
                    ->exists();

                if ($alreadyDone) {
                    continue;
                }

                LoyaltyMissionCompletion::create([
                    'mission_id' => $mission->id,
                    'member_id' => $member->id,
                    'completed_at' => now(),
                    'meta' => ['event' => $event],
                ]);

                $rewardAmount = (int) round((float) $mission->reward_value);
                if ($rewardAmount <= 0) {
                    continue;
                }

                if ($mission->reward_type === 'points_fixed') {
                    $this->addPoints($member, $rewardAmount, 'پاداش مأموریت: ' . $mission->title, 'mission', $mission->id);
                } else {
                    $this->adjustWallet($member, $rewardAmount, 'پاداش مأموریت: ' . $mission->title, 'mission', $mission->id);
                }
            }
        } catch (\Throwable $exception) {
            Log::error('Loyalty checkMissions Error: ' . $exception->getMessage());
        }
    }

    private function makeReferralCode(): string
    {
        $code = strtoupper(Str::random(8));

        while (LoyaltyMember::where('referral_code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }

        return $code;
    }
}