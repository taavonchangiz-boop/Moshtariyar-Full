<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToBusiness;

class Supply extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'warehouse_id',
        'supplier_id',
        'number',
        'total_cost',
        'note',
        'received_at',
        'created_by',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function items(): HasMany { return $this->hasMany(SupplyItem::class); }

    public function getTotalQtyAttribute(): int { return (int) $this->items()->sum('qty'); }
}

class SupplyItem extends Model
{
    protected $fillable = ['supply_id','product_id','qty','unit_cost','total_cost'];
    protected $casts = ['qty' => 'integer','unit_cost' => 'decimal:2','total_cost' => 'decimal:2'];

    public function supply(): BelongsTo { return $this->belongsTo(Supply::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}