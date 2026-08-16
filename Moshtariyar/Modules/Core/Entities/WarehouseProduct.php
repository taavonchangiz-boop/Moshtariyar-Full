<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseProduct extends Model
{
    protected $table = 'warehouse_product';

    protected $fillable = ['warehouse_id','product_id','stock','min_stock','avg_cost'];

    protected $casts = ['stock' => 'integer','min_stock' => 'integer','avg_cost' => 'decimal:2'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function getIsLowAttribute(): bool { return $this->stock > 0 && $this->min_stock > 0 && $this->stock <= $this->min_stock; }
    public function getIsEmptyAttribute(): bool { return $this->stock <= 0; }
}