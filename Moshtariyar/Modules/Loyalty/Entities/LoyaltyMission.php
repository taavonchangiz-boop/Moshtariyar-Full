<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyMission extends Model
{
    protected $table = 'loyalty_missions';
    protected $fillable = ['title','description','event','target','reward_type','reward_value','is_active','starts_at','ends_at'];
    protected $casts = ['is_active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];

    public const EVENTS = [
        'profile_complete' => 'تکمیل پروفایل',
        'first_purchase' => 'اولین خرید',
        'referrals_count' => 'تعداد معرفی موفق',
        'orders_count' => 'تعداد سفارش',
    ];

    public function completions(): HasMany
    {
        return $this->hasMany(LoyaltyMissionCompletion::class, 'mission_id');
    }
}
