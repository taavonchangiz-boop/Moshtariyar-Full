<?php

namespace Modules\Loyalty\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loyalty\Entities\LoyaltyCampaign;
use Modules\Loyalty\Entities\LoyaltyCampaignRewardRule;
use Illuminate\Support\Facades\Schema;

class LoyaltyCampaignController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = LoyaltyCampaign::with('rewardRules')->latest();

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('q')) {
                $q = $request->input('q');
                $query->where('title', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%");
            }

            $campaigns = $query->paginate(20);
        } catch (\Exception $e) {
            $campaigns = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        $totalCampaigns = LoyaltyCampaign::count();
        $activeCampaigns = LoyaltyCampaign::where('status', 'active')->count();

        return view('loyalty::campaigns.index', compact('campaigns', 'totalCampaigns', 'activeCampaigns'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'description' => 'nullable|string|max:500',
        ]);

        LoyaltyCampaign::create([
            'title' => $data['name'],
            'slug' => 'camp-' . substr(md5(uniqid()), 0, 8),
            'type' => 'mission',
            'status' => 'active',
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'description' => $data['description'],
        ]);

        return back()->with('status', 'کمپین جدید با موفقیت ساخته شد.');
    }

    public function toggle(LoyaltyCampaign $campaign)
    {
        $newStatus = $campaign->status === 'active' ? 'paused' : 'active';
        $campaign->update(['status' => $newStatus]);
        return back()->with('status', 'وضعیت کمپین تغییر کرد.');
    }

    public function destroy(LoyaltyCampaign $campaign)
    {
        $campaign->delete();
        return back()->with('status', 'کمپین با موفقیت حذف شد.');
    }

    public function storeRewardRule(Request $request, LoyaltyCampaign $campaign)
    {
        $data = $request->validate([
            'action_type' => 'required|string|max:50', 
            'reward_type' => 'required|in:points_fixed,cashback_fixed,coupon_percent',
            'reward_value' => 'required|integer|min:1',
            'condition_details' => 'nullable|string', 
        ]);

        LoyaltyCampaignRewardRule::create([
            'campaign_id' => $campaign->id,
            'event' => $data['action_type'],
            'beneficiary' => 'referrer',
            'reward_type' => $data['reward_type'],
            'reward_value' => $data['reward_value'],
            'release_policy' => 'immediate',
            'release_days' => 0,
            'is_active' => true,
        ]);

        return back()->with('status', 'مأموریت جدید برای این کمپین ثبت شد.');
    }

    public function toggleRewardRule(LoyaltyCampaignRewardRule $rule)
    {
        $rule->update(['is_active' => !$rule->is_active]);
        return back()->with('status', 'وضعیت مأموریت تغییر کرد.');
    }
}