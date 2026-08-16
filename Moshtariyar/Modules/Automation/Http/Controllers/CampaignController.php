<?php

namespace Modules\Automation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Customer;
use Modules\Core\Entities\Segment;
use Modules\Automation\Entities\Campaign;
use Modules\Automation\Jobs\SendCampaign;
use Modules\Loyalty\Entities\LoyaltyReferral;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = Campaign::query()->latest('id');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $baseQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%")
                    ->orWhere('message', 'like', "%{$term}%")
                    ->orWhere('referral_slug', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $baseQuery->where('status', (string) $request->input('status'));
        }

        if ($request->filled('channel')) {
            $baseQuery->where('channel', (string) $request->input('channel'));
        }

        if ($request->filled('segment')) {
            $baseQuery->where('segment', (string) $request->input('segment'));
        }

        if ($request->filled('rfm_group')) {
            $baseQuery->where('rfm_group', (string) $request->input('rfm_group'));
        }

        if ($request->boolean('needs_optimization')) {
            $baseQuery->where('status', 'sent')
                ->where(function ($q) {
                    $q->where('clicks_count', 0)
                        ->orWhereColumn('conversions_count', '<', 'clicks_count');
                });
        }

        $campaigns = (clone $baseQuery)->paginate(20)->withQueryString();
        $campaignBoard = (clone $baseQuery)->limit(200)->get();
        $totalRevenue = (clone $baseQuery)->sum('roi_revenue');
        $totalClicks = (clone $baseQuery)->sum('clicks_count');
        $totalConversions = (clone $baseQuery)->sum('conversions_count');
        $dynamicSegments = Segment::where('is_active', true)->orderBy('name')->get(['id', 'name', 'members_count']);

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'active' => (clone $baseQuery)->whereIn('status', ['queued', 'sending'])->count(),
            'sent' => (clone $baseQuery)->where('status', 'sent')->count(),
            'needs_optimization' => (clone $baseQuery)->where('status', 'sent')->where(function ($q) {
                $q->where('clicks_count', 0)->orWhereColumn('conversions_count', '<', 'clicks_count');
            })->count(),
        ];

        return view('app.campaigns', [
            'campaigns' => $campaigns,
            'campaignBoard' => $campaignBoard,
            'segments' => Campaign::SEGMENTS,
            'rfmGroups' => Campaign::RFM_GROUPS,
            'channels' => Campaign::CHANNELS,
            'dynamicSegments' => $dynamicSegments,
            'totalRevenue' => $totalRevenue,
            'totalClicks' => $totalClicks,
            'totalConversions' => $totalConversions,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'referral_slug' => 'nullable|string|max:80',
            'channel'       => 'required|string|max:50',
            'segment'       => 'required|string|max:40',
            'segment_id'    => 'nullable|integer|exists:segments,id',
            'rfm_group'     => 'nullable|string|max:50',
            'subject'       => 'nullable|string|max:191',
            'message'       => 'required|string',
            'reward_event'  => 'nullable|string|max:50',
            'reward_value'  => 'nullable|numeric',
        ]);

        $rewardRules = [];
        if (!empty($data['reward_value']) && $data['reward_value'] > 0) {
            $rewardRules = [
                'event' => $data['reward_event'] ?? 'referral_registered',
                'value' => (float) $data['reward_value'],
            ];
        }

        Campaign::create([
            'name'          => $data['name'],
            'referral_slug' => $data['referral_slug'] ?: \Illuminate\Support\Str::slug($data['name']),
            'channel'       => $data['channel'],
            'segment'       => $data['segment'],
            'rfm_group'     => $data['rfm_group'] ?? 'all',
            'subject'       => $data['subject'] ?? null,
            'message'       => $data['message'],
            'reward_rules'  => !empty($rewardRules) ? $rewardRules : null,
            'meta'          => [
                'segment_id' => $data['segment'] === 'custom_segs' ? ($data['segment_id'] ?? null) : null,
                'created_from' => 'campaign_center',
            ],
            'status'        => 'draft',
            'total'         => 0,
            'sent'          => 0,
        ]);

        return back()->with('status', 'کمپین هوشمند با موفقیت به‌صورت پیش‌نویس ساخته شد. قبل از ارسال، متن و گروه هدف را یک بار بررسی کنید.');
    }

    public function send(Campaign $campaign)
    {
        $totalCount = $this->targetQuery($campaign)->count();

        $campaign->update(['total' => $totalCount, 'sent' => 0, 'status' => 'queued']);
        SendCampaign::dispatch($campaign->id, 0);

        return back()->with('status', 'کمپین در صف ارسال قرار گرفت. مجموع گیرندگان هدف: ' . \Modules\Core\Support\Num::fa($totalCount) . ' نفر.');
    }

    public function show(Campaign $campaign)
    {
        return view('app.campaign_report', compact('campaign'));
    }

    private function targetQuery(Campaign $campaign)
    {
        $query = Customer::query();

        if (! empty($campaign->segment) && $campaign->segment !== 'all') {
            match ($campaign->segment) {
                'woocommerce' => $query->where('source', 'woocommerce'),
                'has_orders' => $query->whereHas('orders'),
                'referral_buyers' => $query->whereIn('id', LoyaltyReferral::whereNotNull('first_purchase_at')->pluck('referred_customer_id')->filter()->all()),
                'custom_segs' => $this->applyCustomSegment($query, $campaign),
                default => $query,
            };
        }

        if (! empty($campaign->rfm_group) && $campaign->rfm_group !== 'all') {
            $baseSql = "SELECT c.id, COALESCE(o.orders_count,0) oc, COALESCE(o.total_spent,0) t, o.last_order_at lo
                FROM customers c
                LEFT JOIN (SELECT customer_id, MAX(placed_at) last_order_at, COUNT(*) orders_count, COALESCE(SUM(total),0) total_spent FROM orders WHERE customer_id IS NOT NULL GROUP BY customer_id) o ON o.customer_id = c.id";

            $where = match ($campaign->rfm_group) {
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
                $query->whereIn('id', $ids);
            }
        }

        return $query;
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

        $query->whereIn('id', $customerIds);
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return back()->with('status', 'کمپین با موفقیت حذف شد.');
    }
}