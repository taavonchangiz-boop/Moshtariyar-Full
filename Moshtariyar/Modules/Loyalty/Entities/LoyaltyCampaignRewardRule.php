<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCampaignRewardRule extends Model
{
    protected $table = 'loyalty_campaign_reward_rules';
    protected $fillable = ['campaign_id','event','beneficiary','reward_type','reward_value','release_policy','release_days','is_active'];
    protected $casts = ['is_active'=>'boolean'];

    public const EVENTS = ['referral_registered'=>'ثبت‌نام زیرمجموعه','first_purchase'=>'اولین خرید زیرمجموعه','profile_complete'=>'تکمیل پروفایل زیرمجموعه'];
    public const BENEFICIARIES = ['referrer'=>'معرف','referred'=>'دعوت‌شده'];
    public const REWARD_TYPES = ['points_fixed'=>'امتیاز ثابت','cashback_fixed'=>'کیف پول ثابت','coupon_fixed'=>'کوپن مبلغی','coupon_percent'=>'کوپن درصدی'];
    public const RELEASE_POLICIES = ['immediate'=>'آزادسازی فوری','after_days'=>'بعد از چند روز','manual'=>'دستی'];
}
