<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyRule extends Model
{
    protected $table = 'loyalty_rules';
    protected $fillable = ['name', 'event', 'reward_type', 'reward_value', 'max_reward', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public const EVENTS = [
        'signup'           => 'ثبت‌نام در باشگاه',
        'profile_complete' => 'تکمیل پروفایل',
        'purchase'         => 'هر خرید',
        'birthday'         => 'تولد',
        'referral'         => 'معرفی دوستان - سطح ۱',
        'referral_level_2' => 'معرفی دوستان - سطح ۲',
        'referral_level_3' => 'معرفی دوستان - سطح ۳',
        'referral_level_4' => 'معرفی دوستان - سطح ۴',
        'referral_level_5' => 'معرفی دوستان - سطح ۵',
        'review'           => 'ثبت نظر',
    ];

    public const REWARD_TYPES = [
        'points_fixed'     => 'امتیاز ثابت',
        'points_percent'   => 'امتیاز درصدی از مبلغ خرید',
        'cashback_percent' => 'کش‌بک درصدی به کیف پول',
        'cashback_fixed'   => 'کش‌بک مبلغ ثابت',
    ];
}
