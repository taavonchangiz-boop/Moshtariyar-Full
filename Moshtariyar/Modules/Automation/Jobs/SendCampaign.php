<?php

namespace Modules\Automation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Entities\Customer;
use Modules\Core\Support\Money;
use Modules\Core\Support\Num;
use Modules\Automation\Entities\Campaign;
use Modules\IranPack\Managers\SmsManager;
use Modules\IranPack\Messaging\MessagingChannels;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\LoyaltyReferral;

/**
 * ارسال پیام‌های هدفمند پیشرفته به‌صورت دسته‌ای.
 */
class SendCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct(public int $campaignId, public int $afterId = 0)
    {
    }

    public function handle(SmsManager $sms): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (! $campaign || $campaign->status === 'sent') {
            return;
        }
        $campaign->update(['status' => 'sending']);

        $batchSize = 50;
        $query = $this->targetQuery($campaign)->where('customers.id', '>', $this->afterId)->orderBy('customers.id');
        $batch = $query->limit($batchSize)->get(['customers.*']);

        if ($batch->isEmpty()) {
            $campaign->update(['status' => 'sent', 'sent_at' => now()]);
            return;
        }

        $lastId = $this->afterId;
        foreach ($batch as $customer) {
            $lastId = $customer->id;
            try {
                $msg = $this->personalize($campaign, $customer);
                $channel = $campaign->channel;

                if ($channel === 'sms' && $customer->phone) {
                    $sms->send($customer->phone, $msg);
                } elseif ($channel === 'email' && $customer->email) {
                    Mail::raw($msg, fn ($m) => $m->to($customer->email)->subject($campaign->subject ?? config('brand.name', 'سامانه مدیریت')));
                } elseif (in_array($channel, ['whatsapp', 'bale', 'eitaa', 'telegram'], true) && $customer->phone) {
                    MessagingChannels::sendVia($channel, $customer->phone, $msg);
                }
                $campaign->increment('sent');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        self::dispatch($this->campaignId, $lastId);
    }

    private function targetQuery(Campaign $c)
    {
        $q = Customer::query();

        if (!empty($c->segment) && $c->segment !== 'all') {
            match ($c->segment) {
                'woocommerce' => $q->where('source', 'woocommerce'),
                'has_orders'  => $q->whereHas('orders'),
                'referral_buyers' => $q->whereIn('customers.id', LoyaltyReferral::whereNotNull('first_purchase_at')->pluck('referred_customer_id')->filter()->all()),
                'custom_segs' => $this->applyCustomSegment($q, $c),
                default       => $q,
            };
        }

        if (!empty($c->rfm_group) && $c->rfm_group !== 'all') {
            $baseSql = "SELECT c.id, COALESCE(o.orders_count,0) oc, COALESCE(o.total_spent,0) t, o.last_order_at lo
                FROM customers c
                LEFT JOIN (SELECT customer_id, MAX(placed_at) last_order_at, COUNT(*) orders_count, COALESCE(SUM(total),0) total_spent FROM orders WHERE customer_id IS NOT NULL GROUP BY customer_id) o ON o.customer_id = c.id";

            $where = match ($c->rfm_group) {
                'champions'   => "oc >= 8 AND t >= 50000000 AND lo >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                'loyal'       => "oc >= 4 AND lo >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                'potential'   => "oc BETWEEN 1 AND 3 AND lo >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                'new'         => "oc = 1 AND lo >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                'at_risk'     => "oc >= 3 AND lo < DATE_SUB(NOW(), INTERVAL 60 DAY) AND lo >= DATE_SUB(NOW(), INTERVAL 120 DAY)",
                'hibernating' => "oc > 0 AND lo < DATE_SUB(NOW(), INTERVAL 120 DAY) AND lo >= DATE_SUB(NOW(), INTERVAL 365 DAY)",
                'lost'        => "oc > 0 AND lo < DATE_SUB(NOW(), INTERVAL 365 DAY)",
                default       => null,
            };

            if ($where) {
                $matchedIds = DB::select("SELECT id FROM ({$baseSql}) x WHERE {$where}");
                $ids = array_column($matchedIds, 'id');
                $q->whereIn('customers.id', $ids);
            }
        }

        return $q;
    }

    private function applyCustomSegment($query, Campaign $campaign): void
    {
        $segmentId = data_get($campaign->meta, 'segment_id');
        if (! $segmentId) {
            $query->whereRaw('1=0');
            return;
        }

        $customerIds = DB::table('segment_member')->where('segment_id', $segmentId)->pluck('customer_id')->all();
        if (empty($customerIds)) {
            $query->whereRaw('1=0');
            return;
        }

        $query->whereIn('customers.id', $customerIds);
    }

    private function personalize(Campaign $camp, Customer $c): string
    {
        $member = LoyaltyMember::with('tier')->where('customer_id', $c->id)->first();
        if (!$member) {
            try {
                $member = app(\Modules\Loyalty\Services\LoyaltyService::class)->ensureMember($c);
            } catch (\Throwable $err) {
                // ادامه
            }
        }

        $refSlug = $camp->referral_slug ?: ($camp->slug ?? 'promo');
        $refLink = $member ? url("/club/c/{$refSlug}/{$camp->channel}/" . strtoupper($member->referral_code)) : url('/');

        return strtr($camp->message, [
            '{name}'          => $c->full_name ?: 'دوست عزیز',
            '{نام}'           => $c->full_name ?: 'دوست عزیز',
            '{brand}'         => config('brand.name', 'فروشگاه'),
            '{برند}'          => config('brand.name', 'فروشگاه'),
            '{referral_link}' => $refLink,
            '{لینک_دعوت}'     => $refLink,
            '{points}'        => $member ? Num::fa(number_format($member->points_lifetime)) : '۰',
            '{امتیاز}'        => $member ? Num::fa(number_format($member->points_lifetime)) : '۰',
            '{wallet}'        => $member ? Money::show($member->wallet_balance) : '۰',
            '{کیف_پول}'       => $member ? Money::show($member->wallet_balance) : '۰',
            '{rfm_group}'     => $camp->rfm_group ? (\Modules\Automation\Entities\Campaign::RFM_GROUPS[$camp->rfm_group] ?? '') : '',
            '{گروه_رفتاری}'   => $camp->rfm_group ? (\Modules\Automation\Entities\Campaign::RFM_GROUPS[$camp->rfm_group] ?? '') : '',
        ]);
    }
}
