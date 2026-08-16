<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerPortalLoginCode extends Model
{
    protected $table = 'customer_portal_login_codes';

    protected $fillable = ['phone', 'code_hash', 'expires_at', 'used_at', 'ip', 'user_agent'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
