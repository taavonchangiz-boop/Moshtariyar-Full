<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCoupon extends Model
{
    protected $table = 'loyalty_coupons';
    protected $fillable = ['member_id','redemption_rule_id','code','title','discount_type','discount_value','min_order_total','expires_at','used_at','usage_limit','source','points_spent'];
    protected $casts = ['expires_at'=>'datetime','used_at'=>'datetime','discount_value'=>'decimal:2','min_order_total'=>'decimal:2'];
}
