<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToBusiness; // اضافه شد

class Order extends Model
{
    use BelongsToBusiness; // اضافه شد

    protected $fillable = [
        'business_id', // اضافه شد
        'customer_id',
        'number',
        'status',
        'total',
        'tax_total',
        'currency',
        'source',
        'placed_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'total' => 'decimal:2',
        'tax_total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}