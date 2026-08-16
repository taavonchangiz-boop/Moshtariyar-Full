<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyBadge extends Model
{
    protected $table = 'loyalty_badges';
    protected $fillable = ['title','icon','color','description','condition_type','condition_value','is_active'];
    protected $casts = ['is_active'=>'boolean'];
}
