<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyRedemptionRule extends Model
{
    protected $table = 'loyalty_redemption_rules';
    protected $fillable = ['title','description','points_required','discount_type','discount_value','min_order_total','expires_days','usage_limit','is_active','starts_at','ends_at'];
    protected $casts = ['is_active'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime','discount_value'=>'decimal:2','min_order_total'=>'decimal:2'];

    public function isAvailable(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->lte(now())) && (! $this->ends_at || $this->ends_at->gte(now()));
    }
}
