<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerPortalToken extends Model
{
    protected $table = 'customer_portal_tokens';
    protected $fillable = ['member_id','token_hash','expires_at','last_used_at'];
    protected $casts = ['expires_at'=>'datetime','last_used_at'=>'datetime'];
}
