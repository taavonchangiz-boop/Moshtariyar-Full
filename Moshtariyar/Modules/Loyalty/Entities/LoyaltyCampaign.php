<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyCampaign extends Model
{
    protected $table = 'loyalty_campaigns';
    protected $fillable = ['title','slug','description','type','status','starts_at','ends_at','meta'];
    protected $casts = ['starts_at'=>'datetime','ends_at'=>'datetime','meta'=>'array'];

    public const TYPES = [
        'referral' => 'معرفی دوستان',
        'purchase' => 'خرید',
        'profile' => 'تکمیل پروفایل',
        'mission' => 'مأموریت',
        'seasonal' => 'مناسبتی',
    ];
    public const STATUSES = ['draft'=>'پیش‌نویس','active'=>'فعال','paused'=>'متوقف','expired'=>'منقضی'];

    public function channels(): HasMany { return $this->hasMany(LoyaltyCampaignChannel::class, 'campaign_id'); }
    public function clicks(): HasMany { return $this->hasMany(LoyaltyCampaignClick::class, 'campaign_id'); }
    public function referrals(): HasMany { return $this->hasMany(LoyaltyReferral::class, 'campaign_id'); }
    public function rewardRules(): HasMany { return $this->hasMany(LoyaltyCampaignRewardRule::class, 'campaign_id'); }
    public function rewards(): HasMany { return $this->hasMany(LoyaltyCampaignReward::class, 'campaign_id'); }

    public function isRunning(): bool
    {
        return $this->status === 'active'
            && (! $this->starts_at || $this->starts_at->lte(now()))
            && (! $this->ends_at || $this->ends_at->gte(now()));
    }
}
