<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    protected $table = 'loyalty_tiers';
    public $timestamps = false;
    protected $fillable = ['name', 'color', 'min_points', 'cashback_rate', 'order'];
}
