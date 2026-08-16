<?php

namespace Modules\Loyalty\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Entities\Customer;
use Modules\Loyalty\Entities\LoyaltyMember;
use Modules\Loyalty\Entities\LoyaltyTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Loyalty\Entities\LoyaltyRule;
use Modules\Loyalty\Entities\LoyaltyMission;
use Modules\Loyalty\Entities\LoyaltyBadge;
use Modules\Loyalty\Entities\LoyaltyCoupon;
use Modules\Loyalty\Entities\LoyaltyCampaign;
use Modules\Loyalty\Entities\LoyaltyCampaignChannel;
use Modules\Loyalty\Entities\LoyaltyCampaignReward;
use Modules\Loyalty\Entities\LoyaltyCampaignRewardRule;
use Modules\Loyalty\Entities\LoyaltyReferral;
use Modules\Loyalty\Entities\LoyaltyRedemptionRule;
use Modules\Loyalty\Entities\LoyaltyTransaction;
use Modules\Core\Entities\Order;
use Modules\Core\Services\ImageOptimizerService;
use Modules\Loyalty\Services\LoyaltyService;

class LoyaltyController extends Controller
{
    public function __construct(private LoyaltyService $service)
    {
    }

    /** فهرست اعضای باشگاه */
    public function index(Request $request)
    {
        $query = LoyaltyMember::query()
            ->with([
                'customer',
                'tier',
                'transactions' => function ($txQuery) {
                    $txQuery->latest()->limit(10);
                },
            ])
            ->withCount([
                'referralsMade as referrals_count',
                'coupons as coupons_count',
            ]);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($memberQuery) use ($search) {
                $memberQuery->where('referral_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('tier_id')) {
            $query->where('tier_id', (int) $request->input('tier_id'));
        }

        if ($request->boolean('without_tier')) {
            $query->whereNull('tier_id');
        }

        if ($request->boolean('with_wallet')) {
            $query->where('wallet_balance', '>', 0);
        }

        if ($request->boolean('without_referral')) {
            $query->where(function ($row) {
                $row->whereNull('referral_code')->orWhere('referral_code', '');
            });
        }

        match ($request->input('sort', 'points')) {
            'wallet' => $query->orderByDesc('wallet_balance'),
            'referrals' => $query->orderByDesc('referrals_count'),
            'joined' => $query->latest('id'),
            default => $query->orderByDesc('points'),
        };

        $members = $query->paginate(60)->withQueryString();

        $stats = [
            'members' => LoyaltyMember::count(),
            'points' => LoyaltyMember::sum('points'),
            'wallet' => LoyaltyMember::sum('wallet_balance'),
            'referrals' => LoyaltyReferral::count(),
            'without_tier' => LoyaltyMember::whereNull('tier_id')->count(),
            'with_wallet' => LoyaltyMember::where('wallet_balance', '>', 0)->count(),
            'without_referral' => LoyaltyMember::where(function ($row) {
                $row->whereNull('referral_code')->orWhere('referral_code', '');
            })->count(),
            'active_profiles' => LoyaltyMember::where('profile_completed', true)->count(),
        ];

        $tiers = LoyaltyTier::orderBy('min_points')->get();

        return view('loyalty::members', compact('members', 'stats', 'tiers'));
    }

    public function reports()
    {
        $referredCustomerIds = LoyaltyReferral::whereNotNull('first_purchase_at')
            ->whereNotNull('referred_customer_id')
            ->pluck('referred_customer_id')
            ->unique()
            ->values();

        $stats = [
            'members' => LoyaltyMember::count(),
            'points_current' => LoyaltyMember::sum('points'),
            'points_lifetime' => LoyaltyMember::sum('points_lifetime'),
            'wallet' => LoyaltyMember::sum('wallet_balance'),
            'referrals' => LoyaltyReferral::count(),
            'transactions' => LoyaltyTransaction::count(),
            'orders' => class_exists(Order::class) ? Order::count() : 0,
            'order_points' => LoyaltyTransaction::where('kind', 'point')->where('direction', 'credit')->where('ref_type', 'order_reward')->sum('amount'),
            'order_wallet' => LoyaltyTransaction::where('kind', 'wallet')->where('direction', 'credit')->whereIn('ref_type', ['order_reward', 'order_tier'])->sum('amount'),
            'rollback_points' => LoyaltyTransaction::where('kind', 'point')->where('direction', 'debit')->where('ref_type', 'rollback')->where('reason', 'like', '%سفارش%')->sum('amount'),
            'rollback_wallet' => LoyaltyTransaction::where('kind', 'wallet')->where('direction', 'debit')->where('ref_type', 'rollback')->where('reason', 'like', '%سفارش%')->sum('amount'),
            'rewarded_members' => LoyaltyTransaction::whereIn('ref_type', ['order_reward', 'order_tier'])->where('direction', 'credit')->distinct('member_id')->count('member_id'),
            'referred_buyers' => $referredCustomerIds->count(),
            'referral_sales' => $referredCustomerIds->isEmpty() ? 0 : Order::whereIn('customer_id', $referredCustomerIds)->whereIn('status', ['processing', 'completed'])->sum('total'),
        ];

        $topReferrers = LoyaltyMember::with('customer')
            ->withCount(['referralsMade as direct_referrals_count' => fn($q) => $q->where('level', 1)])
            ->orderByDesc('direct_referrals_count')->limit(10)->get();

        $topPoints = LoyaltyMember::with(['customer','tier'])->orderByDesc('points_lifetime')->limit(10)->get();
        $nearTier = LoyaltyMember::with(['customer','tier'])->orderByDesc('points_lifetime')->limit(12)->get();

        $topPurchaseEffects = LoyaltyMember::with(['customer','tier'])
            ->select('loyalty_members.*')
            ->selectSub(function($q){
                $q->from('loyalty_transactions')->selectRaw('COALESCE(SUM(amount),0)')
                    ->whereColumn('loyalty_transactions.member_id', 'loyalty_members.id')
                    ->where('kind', 'point')->where('direction', 'credit')->where('ref_type', 'order_reward');
            }, 'purchase_points')
            ->selectSub(function($q){
                $q->from('loyalty_transactions')->selectRaw('COALESCE(SUM(amount),0)')
                    ->whereColumn('loyalty_transactions.member_id', 'loyalty_members.id')
                    ->where('kind', 'wallet')->where('direction', 'credit')
                    ->whereIn('ref_type', ['order_reward', 'order_tier']);
            }, 'purchase_wallet')
            ->orderByRaw('(COALESCE(purchase_points,0) + COALESCE(purchase_wallet,0)) DESC')
            ->limit(10)
            ->get()
            ->filter(fn($m) => ((int) ($m->purchase_points ?? 0) > 0) || ((int) ($m->purchase_wallet ?? 0) > 0))
            ->values();

        $recentOrders = Order::with('customer')
            ->whereIn('status', ['processing', 'completed', 'cancelled', 'refunded', 'failed'])
            ->latest('placed_at')
            ->limit(15)
            ->get();
        $memberMap = LoyaltyMember::whereIn('customer_id', $recentOrders->pluck('customer_id')->filter()->unique()->values())
            ->get()
            ->keyBy('customer_id');
        $recentOrderEffects = $recentOrders->map(function ($order) use ($memberMap) {
            $member = $memberMap->get($order->customer_id);
            if (! $member) return null;

            $pointsAdded = LoyaltyTransaction::where('member_id', $member->id)
                ->where('kind', 'point')->where('direction', 'credit')
                ->where('ref_type', 'order_reward')->where('ref_id', $order->id)
                ->sum('amount');
            $walletAdded = LoyaltyTransaction::where('member_id', $member->id)
                ->where('kind', 'wallet')->where('direction', 'credit')
                ->whereIn('ref_type', ['order_reward', 'order_tier'])->where('ref_id', $order->id)
                ->sum('amount');
            $pointsBack = LoyaltyTransaction::where('member_id', $member->id)
                ->where('kind', 'point')->where('direction', 'debit')
                ->where('ref_type', 'rollback')->where('reason', 'like', '%'.($order->number ?: $order->id).'%')
                ->sum('amount');
            $walletBack = LoyaltyTransaction::where('member_id', $member->id)
                ->where('kind', 'wallet')->where('direction', 'debit')
                ->where('ref_type', 'rollback')->where('reason', 'like', '%'.($order->number ?: $order->id).'%')
                ->sum('amount');

            if ($pointsAdded == 0 && $walletAdded == 0 && $pointsBack == 0 && $walletBack == 0) {
                return null;
            }

            return [
                'order' => $order,
                'customer' => $order->customer,
                'points_added' => (int) $pointsAdded,
                'wallet_added' => (int) $walletAdded,
                'points_back' => (int) $pointsBack,
                'wallet_back' => (int) $walletBack,
            ];
        })->filter()->values();

        $recentRollbacks = LoyaltyTransaction::with('member.customer')
            ->where('ref_type', 'rollback')
            ->where('reason', 'like', '%سفارش%')
            ->latest('id')
            ->limit(15)
            ->get();

        $dailyTransactions = LoyaltyTransaction::selectRaw('DATE(created_at) d, kind, direction, SUM(amount) total, COUNT(*) cnt')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('d','kind','direction')
            ->orderBy('d')
            ->get();

        return view('loyalty::reports', compact(
            'stats',
            'topReferrers',
            'topPoints',
            'nearTier',
            'dailyTransactions',
            'topPurchaseEffects',
            'recentOrderEffects',
            'recentRollbacks'
        ));
    }

    /** پروندهٔ یک عضو */
    public function show(LoyaltyMember $member)
    {
        $member->load(['customer', 'tier', 'transactions', 'referralsMade.referredCustomer', 'referralsMade.referredMember.tier']);
        $tiers = LoyaltyTier::orderBy('min_points')->get();
        return view('loyalty::member', compact('member', 'tiers'));
    }

    /** شارژ/کسر دستی امتیاز یا کیف پول */
    public function adjust(Request $request, LoyaltyMember $member)
    {
        $data = $request->validate([
            'kind'   => 'required|in:point,wallet',
            'amount' => 'required|integer',
            'reason' => 'required|string|max:120',
        ]);
        if ($data['kind'] === 'point') {
            $this->service->addPoints($member, $data['amount'], $data['reason'], 'manual');
        } else {
            $this->service->adjustWallet($member, $data['amount'], $data['reason'], 'manual');
        }
        return back()->with('status', 'با موفقیت ثبت شد.');
    }

    /** تبدیل امتیاز به کیف پول */
    public function convert(Request $request, LoyaltyMember $member)
    {
        $data = $request->validate(['points' => 'required|integer|min:1']);
        $ok = $this->service->convertPointsToWallet($member, $data['points']);
        return back()->with('status', $ok ? 'امتیاز به اعتبار تبدیل شد.' : 'امتیاز کافی نیست.');
    }

    public function campaigns()
    {
        $campaigns = LoyaltyCampaign::with(['channels','rewardRules'])
            ->withCount(['clicks','referrals','rewards'])
            ->latest('id')->paginate(20);
        $pendingRewards = LoyaltyCampaignReward::where('status','pending')->count();
        return view('loyalty::campaigns', [
            'campaigns' => $campaigns,
            'types' => LoyaltyCampaign::TYPES,
            'statuses' => LoyaltyCampaign::STATUSES,
            'channels' => LoyaltyCampaignChannel::CHANNELS,
            'rewardEvents' => LoyaltyCampaignRewardRule::EVENTS,
            'rewardTypes' => LoyaltyCampaignRewardRule::REWARD_TYPES,
            'beneficiaries' => LoyaltyCampaignRewardRule::BENEFICIARIES,
            'releasePolicies' => LoyaltyCampaignRewardRule::RELEASE_POLICIES,
            'pendingRewards' => $pendingRewards,
        ]);
    }

    public function campaignReport(LoyaltyCampaign $campaign)
    {
        $campaign->load(['channels','rewardRules']);
        $stats = [
            'clicks' => $campaign->clicks()->count(),
            'referrals' => $campaign->referrals()->count(),
            'rewards_pending' => $campaign->rewards()->where('status','pending')->count(),
            'rewards_released' => $campaign->rewards()->where('status','released')->count(),
            'reward_points' => $campaign->rewards()->where('status','released')->where('reward_type','points_fixed')->sum('amount'),
            'reward_wallet' => $campaign->rewards()->where('status','released')->where('reward_type','cashback_fixed')->sum('amount'),
        ];
        $conversion = $stats['clicks'] > 0 ? round($stats['referrals'] * 100 / $stats['clicks'], 1) : 0;

        $channels = DB::table('loyalty_campaign_channels as ch')
            ->where('ch.campaign_id', $campaign->id)
            ->select('ch.channel','ch.label')
            ->get()
            ->map(function ($ch) use ($campaign) {
                $clicks = DB::table('loyalty_campaign_clicks')->where('campaign_id',$campaign->id)->where('channel',$ch->channel)->count();
                $refs = DB::table('loyalty_referrals')->where('campaign_id',$campaign->id)->where('campaign_channel',$ch->channel)->count();
                return (object) ['channel'=>$ch->channel,'label'=>$ch->label,'clicks'=>$clicks,'referrals'=>$refs,'conversion'=>$clicks ? round($refs*100/$clicks,1) : 0];
            });

        $topReferrers = LoyaltyMember::with('customer')
            ->select('loyalty_members.*')
            ->selectSub(function($q) use ($campaign){
                $q->from('loyalty_referrals')->selectRaw('count(*)')->whereColumn('loyalty_referrals.referrer_member_id','loyalty_members.id')->where('campaign_id',$campaign->id)->where('level',1);
            }, 'campaign_referrals_count')
            ->orderByDesc('campaign_referrals_count')->limit(20)->get();

        $rewards = LoyaltyCampaignReward::with(['member.customer','referral.referredCustomer'])->where('campaign_id',$campaign->id)->latest('id')->paginate(30);

        return view('loyalty::campaign_report', compact('campaign','stats','conversion','channels','topReferrers','rewards'));
    }

    public function storeCampaign(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'slug' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|string|max:40',
            'status' => 'required|string|max:40',
            'starts_at' => 'nullable|string|max:30',
            'ends_at' => 'nullable|string|max:30',
            'channels' => 'nullable|array',
        ]);
        $slug = $data['slug'] ?: Str::slug($data['title']);
        if (! $slug) $slug = 'campaign-' . time();
        $base = $slug; $i = 2;
        while (LoyaltyCampaign::where('slug', $slug)->exists()) $slug = $base . '-' . $i++;

        $campaign = LoyaltyCampaign::create([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'starts_at' => \Modules\Core\Support\Jalali::parse($data['starts_at'] ?? null),
            'ends_at' => \Modules\Core\Support\Jalali::parse($data['ends_at'] ?? null)?->endOfDay(),
        ]);
        foreach (($data['channels'] ?? ['direct']) as $ch) {
            LoyaltyCampaignChannel::create([
                'campaign_id' => $campaign->id,
                'channel' => $ch,
                'label' => LoyaltyCampaignChannel::CHANNELS[$ch] ?? $ch,
                'is_active' => true,
            ]);
        }
        return back()->with('status', 'کمپین ساخته شد.');
    }

    public function storeCampaignRewardRule(Request $request, LoyaltyCampaign $campaign)
    {
        $data = $request->validate([
            'event' => 'required|string|max:40',
            'beneficiary' => 'required|string|max:30',
            'reward_type' => 'required|string|max:30',
            'reward_value' => 'required|numeric|min:0',
            'release_policy' => 'required|string|max:40',
            'release_days' => 'nullable|integer|min:0|max:365',
        ]);
        LoyaltyCampaignRewardRule::create($data + ['campaign_id' => $campaign->id, 'is_active' => true]);
        return back()->with('status', 'قانون پاداش کمپین ثبت شد.');
    }

    public function toggleCampaignRewardRule(LoyaltyCampaignRewardRule $rule)
    {
        $rule->update(['is_active' => ! $rule->is_active]);
        return back()->with('status', 'وضعیت قانون پاداش تغییر کرد.');
    }

    public function releaseCampaignReward(LoyaltyCampaignReward $reward)
    {
        $ok = $this->service->releaseCampaignReward($reward);
        return back()->with('status', $ok ? 'پاداش آزاد شد.' : 'این پاداش قابل آزادسازی نیست.');
    }

    public function releaseDueCampaignRewards()
    {
        $count = $this->service->releaseDueCampaignRewards();
        return back()->with('status', $count . ' پاداش سررسیدشده آزاد شد.');
    }

    public function freezeCampaignReward(LoyaltyCampaignReward $reward)
    {
        $this->service->freezeCampaignReward($reward);
        return back()->with('status', 'پاداش فریز شد.');
    }

    public function cancelCampaignReward(LoyaltyCampaignReward $reward)
    {
        $this->service->cancelCampaignReward($reward);
        return back()->with('status', 'پاداش لغو شد.');
    }

    public function toggleCampaign(LoyaltyCampaign $campaign)
    {
        $campaign->update(['status' => $campaign->status === 'active' ? 'paused' : 'active']);
        return back()->with('status', 'وضعیت کمپین تغییر کرد.');
    }

    public function destroyCampaign(LoyaltyCampaign $campaign)
    {
        $campaign->delete();
        return back()->with('status', 'کمپین حذف شد.');
    }

    /** مدیریت سطوح و قوانین */
    public function settings()
    {
        $tiers = LoyaltyTier::orderBy('min_points')->get();
        $rules = LoyaltyRule::latest('id')->get();
        $missions = LoyaltyMission::latest('id')->get();
        $badges = LoyaltyBadge::latest('id')->get();
        $coupons = LoyaltyCoupon::latest('id')->limit(30)->get();
        $redemptionRules = LoyaltyRedemptionRule::latest('id')->get();
        return view('loyalty::settings', [
            'tiers'       => $tiers,
            'rules'       => $rules,
            'events'      => LoyaltyRule::EVENTS,
            'rewardTypes' => LoyaltyRule::REWARD_TYPES,
            'missions'    => $missions,
            'missionEvents' => LoyaltyMission::EVENTS,
            'badges'      => $badges,
            'coupons'     => $coupons,
            'redemptionRules' => $redemptionRules,
        ]);
    }

    public function storeTier(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:20',
            'min_points' => 'required|integer|min:0',
            'cashback_rate' => 'nullable|numeric|min:0|max:100',
        ]);
        LoyaltyTier::create($data + ['order' => LoyaltyTier::max('order') + 1]);
        return back()->with('status', 'سطح جدید ایجاد شد.');
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:120',
            'event'        => 'required|string|max:30',
            'reward_type'  => 'required|string|max:30',
            'reward_value' => 'required|numeric|min:0',
            'max_reward'   => 'nullable|numeric|min:0',
        ]);
        LoyaltyRule::create($data + ['is_active' => true]);
        return back()->with('status', 'قانون پاداش ایجاد شد.');
    }

    public function toggleRule(LoyaltyRule $rule)
    {
        $rule->update(['is_active' => ! $rule->is_active]);
        return back();
    }

    public function storeMission(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'event' => 'required|string|max:50',
            'target' => 'required|integer|min:1',
            'reward_type' => 'required|in:points_fixed,cashback_fixed',
            'reward_value' => 'required|numeric|min:0',
        ]);
        LoyaltyMission::create($data + ['is_active' => true]);
        return back()->with('status', 'مأموریت ایجاد شد.');
    }

    public function storeBadge(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'icon' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'condition_type' => 'required|in:manual,points,referrals,orders',
            'condition_value' => 'required|integer|min:0',
        ]);
        LoyaltyBadge::create($data + ['is_active' => true]);
        return back()->with('status', 'نشان ایجاد شد.');
    }

    public function storeRedemptionRule(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'points_required' => 'required|integer|min:1',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'min_order_total' => 'nullable|numeric|min:0',
            'expires_days' => 'required|integer|min:1|max:365',
            'usage_limit' => 'required|integer|min:1|max:99',
        ]);
        LoyaltyRedemptionRule::create($data + ['is_active' => true]);
        return back()->with('status', 'قانون تبدیل امتیاز به کوپن ساخته شد.');
    }

    public function toggleRedemptionRule(LoyaltyRedemptionRule $rule)
    {
        $rule->update(['is_active' => ! $rule->is_active]);
        return back()->with('status', 'وضعیت قانون تبدیل تغییر کرد.');
    }

    public function exportCoupons()
    {
        $coupons = LoyaltyCoupon::whereNull('used_at')
            ->where(function($q){ $q->whereNull('expires_at')->orWhere('expires_at','>=',now()); })
            ->latest('id')->get()
            ->map(fn($c) => [
                'code' => $c->code,
                'title' => $c->title,
                'discount_type' => $c->discount_type,
                'discount_value' => (float) $c->discount_value,
                'min_order_total' => (float) $c->min_order_total,
                'expires_at' => $c->expires_at?->format('Y-m-d'),
                'usage_limit' => (int) ($c->usage_limit ?: 1),
                'email' => $c->member?->customer?->email,
                'phone' => $c->member?->customer?->phone,
                'source' => $c->source,
            ])->values();
        return response()->json(['exported_at'=>now()->toIso8601String(), 'app'=>'مشتری‌یار', 'coupons'=>$coupons], 200, [
            'Content-Disposition' => 'attachment; filename="moshtariyar-coupons-'.now()->format('Ymd-His').'.json"',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function storeCoupon(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'min_order_total' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|string|max:30',
        ]);
        $data['expires_at'] = \Modules\Core\Support\Jalali::parse($data['expires_at'] ?? null);
        LoyaltyCoupon::create($data + ['code' => strtoupper('MY-' . Str::random(8)), 'source' => 'admin']);
        return back()->with('status', 'کوپن عمومی ایجاد شد.');
    }

    /** صفحهٔ مدیریت گردونهٔ شانس */
    public function wheel()
    {
        $prizes = \Modules\Loyalty\Entities\WheelPrize::latest('id')->get();
        $spins  = \Modules\Loyalty\Entities\WheelSpin::latest('id')->limit(20)->get();
        return view('loyalty::wheel', compact('prizes', 'spins'));
    }

    public function storeWheelPrize(Request $request)
    {
        $channels = implode(',', array_keys(\Modules\Loyalty\Entities\WheelPrize::DELIVERY_CHANNELS));
        $data = $request->validate([
            'title' => 'required|string|max:80',
            'prize_type' => 'required|in:point,wallet,coupon,nothing',
            'amount' => 'required|integer|min:0',
            'chance' => 'required|integer|min:1|max:100',
            'color' => 'nullable|string|max:20',
            'image' => 'nullable|image|max:2048',
            'delivery_channels' => 'nullable|array',
            'delivery_channels.*' => 'string|in:' . $channels,
            'delivery_message' => 'nullable|string|max:1200',
            'coupon_discount_type' => 'nullable|in:fixed,percent',
            'coupon_discount_value' => 'nullable|numeric|min:0',
            'coupon_expires_days' => 'nullable|integer|min:1|max:365',
        ]);
        $data = $this->prepareWheelPrizeData($data);
        $data['image'] = $this->storeWheelImage($request);
        \Modules\Loyalty\Entities\WheelPrize::create($data + ['is_active' => true]);
        return back()->with('status', 'جایزه گردونه اضافه شد.');
    }

    public function updateWheelPrize(Request $request, \Modules\Loyalty\Entities\WheelPrize $prize)
    {
        $channels = implode(',', array_keys(\Modules\Loyalty\Entities\WheelPrize::DELIVERY_CHANNELS));
        $data = $request->validate([
            'title' => 'required|string|max:80',
            'prize_type' => 'required|in:point,wallet,coupon,nothing',
            'amount' => 'required|integer|min:0',
            'chance' => 'required|integer|min:1|max:100',
            'color' => 'nullable|string|max:20',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
            'delivery_channels' => 'nullable|array',
            'delivery_channels.*' => 'string|in:' . $channels,
            'delivery_message' => 'nullable|string|max:1200',
            'coupon_discount_type' => 'nullable|in:fixed,percent',
            'coupon_discount_value' => 'nullable|numeric|min:0',
            'coupon_expires_days' => 'nullable|integer|min:1|max:365',
        ]);
        $data = $this->prepareWheelPrizeData($data);
        if ($img = $this->storeWheelImage($request)) {
            $data['image'] = $img;
        }
        $data['is_active'] = $request->boolean('is_active');
        $prize->update($data);
        return back()->with('status', 'جایزه به‌روزرسانی شد.');
    }

    public function destroyWheelPrize(\Modules\Loyalty\Entities\WheelPrize $prize)
    {
        $prize->delete();
        return back()->with('status', 'جایزه حذف شد.');
    }

    private function prepareWheelPrizeData(array $data): array
    {
        $data['delivery_channels'] = array_values($data['delivery_channels'] ?? ['portal']);
        if (! in_array('portal', $data['delivery_channels'], true)) {
            array_unshift($data['delivery_channels'], 'portal');
        }
        $data['coupon_discount_type'] = $data['coupon_discount_type'] ?? 'fixed';
        $data['coupon_discount_value'] = $data['coupon_discount_value'] ?? (int) ($data['amount'] ?? 0);
        $data['coupon_expires_days'] = $data['coupon_expires_days'] ?? 7;

        return $data;
    }

    private function storeWheelImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return app(ImageOptimizerService::class)->storeAsWebp($request->file('image'), 'img/wheel');
    }

    public function spinWheel(LoyaltyMember $member)
    {
        $result = app(\Modules\Loyalty\Services\WheelService::class)->spin($member);
        return back()->with('status', $result['ok'] ? ('نتیجهٔ گردونه: ' . $result['prize']) : $result['message']);
    }

    /**
     * ثبت پاداش سریع (امتیاز، سکه، کیف پول، کد تخفیف) برای یک عضو باشگاه.
     * استفاده در فرم «ثبت پاداش سریع» صفحه اعضای باشگاه.
     */
    public function quickReward(Request $request)
    {
        $data = $request->validate([
            'member_search' => 'required|string|max:120',
            'reward_type'   => 'required|in:point,coin,wallet,discount',
            'operation'     => 'required|in:add,subtract',
            'amount'        => 'required|numeric|min:0',
            'expires_at'    => 'nullable|string|max:20',
            'reason'        => 'required|string|max:180',
            'notes'         => 'nullable|string|max:500',
        ]);

        $search = trim($data['member_search']);
        $member = LoyaltyMember::query()
            ->with('customer')
            ->where('referral_code', $search)
            ->orWhereHas('customer', function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->first();

        if (! $member) {
            return back()->with('status', 'عضوی با این مشخصات پیدا نشد.');
        }

        $amount    = (int) $data['amount'];
        $signed    = $data['operation'] === 'subtract' ? -1 * $amount : $amount;
        $rewardKey = $data['reward_type'];

        DB::transaction(function () use ($member, $rewardKey, $signed, $amount, $data) {
            switch ($rewardKey) {
                case 'point':
                    $member->increment('points', $signed);
                    break;
                case 'coin':
                    if (isset($member->coin_balance)) {
                        $member->increment('coin_balance', $signed);
                    } else {
                        $member->increment('points', $signed);
                    }
                    break;
                case 'wallet':
                    $member->increment('wallet_balance', $signed);
                    break;
                case 'discount':
                    LoyaltyCoupon::create([
                        'loyalty_member_id' => $member->id,
                        'code'              => strtoupper(Str::random(8)),
                        'type'              => 'percent',
                        'value'             => $amount,
                        'reason'            => $data['reason'],
                        'notes'             => $data['notes'] ?? null,
                        'expires_at'        => $data['expires_at'] ?? null,
                        'is_active'         => true,
                    ]);
                    break;
            }

            LoyaltyTransaction::create([
                'loyalty_member_id' => $member->id,
                'type'              => $rewardKey,
                'amount'            => $signed,
                'reason'            => $data['reason'],
                'description'       => $data['notes'] ?? null,
                'expires_at'        => $data['expires_at'] ?? null,
            ]);
        });

        return back()->with('status', 'پاداش با موفقیت برای عضو ثبت شد.');
    }
}