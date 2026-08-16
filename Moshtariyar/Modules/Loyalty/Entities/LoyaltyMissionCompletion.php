<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyMissionCompletion extends Model
{
    protected $table = 'loyalty_mission_completions';
    protected $fillable = ['mission_id','member_id','completed_at','meta'];
    protected $casts = ['completed_at'=>'datetime','meta'=>'array'];
}
