<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCampaignReward extends Model
{
    protected $table = 'loyalty_campaign_rewards';
    protected $fillable = ['campaign_id','rule_id','member_id','referral_id','reward_type','amount','status','release_at','released_at','note','meta'];
    protected $casts = ['release_at'=>'datetime','released_at'=>'datetime','meta'=>'array'];

    public function campaign(){ return $this->belongsTo(LoyaltyCampaign::class, 'campaign_id'); }
    public function rule(){ return $this->belongsTo(LoyaltyCampaignRewardRule::class, 'rule_id'); }
    public function member(){ return $this->belongsTo(LoyaltyMember::class, 'member_id'); }
    public function referral(){ return $this->belongsTo(LoyaltyReferral::class, 'referral_id'); }
}
