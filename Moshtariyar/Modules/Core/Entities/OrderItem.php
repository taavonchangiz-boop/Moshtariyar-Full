<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'name', 'sku', 'qty', 'unit_price', 'line_total', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * ارتباط با مدل محصول
     * این متد باعث می‌شود سیستم بتواند اطلاعات محصول مرتبط با هر آیتم سفارش را بخواند.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * ارتباط با مدل سفارش
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}