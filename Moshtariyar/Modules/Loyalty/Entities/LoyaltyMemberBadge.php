<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyMemberBadge extends Model
{
    protected $table = 'loyalty_member_badges';
    protected $fillable = ['badge_id','member_id','awarded_at'];
    protected $casts = ['awarded_at'=>'datetime'];
}
