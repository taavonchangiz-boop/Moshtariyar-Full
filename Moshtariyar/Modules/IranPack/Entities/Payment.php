<?php

namespace Modules\IranPack\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'order_id', 'customer_id', 'gateway', 'amount', 'authority',
        'ref_id', 'status', 'description', 'mobile', 'meta', 'paid_at',
    ];

    protected $casts = ['meta' => 'array', 'paid_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Entities\Order::class);
    }
}
