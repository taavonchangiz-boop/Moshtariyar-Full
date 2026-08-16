<?php

namespace Modules\WooBridge\Entities;

use Illuminate\Database\Eloquent\Model;

class AbandonedCart extends Model
{
    protected $table = 'wb_abandoned_carts';

    protected $fillable = [
        'connection_id', 'email', 'phone', 'cart', 'value', 'recovered', 'notified',
    ];

    protected $casts = [
        'cart' => 'array',
        'recovered' => 'boolean',
        'notified' => 'boolean',
    ];
}
