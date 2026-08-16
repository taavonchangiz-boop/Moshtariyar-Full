<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Customer;

class LoyaltyReferral extends Model
{
    protected $table = 'loyalty_referrals';

    protected $fillable = [
        'referrer_member_id', 'root_referrer_member_id', 'referred_member_id', 'referred_customer_id',
        'referral_code', 'campaign_id', 'campaign_channel', 'level', 'status', 'reward_points', 'reward_wallet',
        'first_purchase_at', 'rewarded_at', 'meta',
    ];

    protected $casts = [
        'first_purchase_at' => 'datetime',
        'rewarded_at' => 'datetime',
        'meta' => 'array',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(LoyaltyMember::class, 'referrer_member_id');
    }

    public function rootReferrer(): BelongsTo
    {
        return $this->belongsTo(LoyaltyMember::class, 'root_referrer_member_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCampaign::class, 'campaign_id');
    }

    public function referredMember(): BelongsTo
    {
        return $this->belongsTo(LoyaltyMember::class, 'referred_member_id');
    }

    public function referredCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_customer_id');
    }
}
