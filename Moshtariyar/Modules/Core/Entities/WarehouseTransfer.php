<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToBusiness;

class WarehouseTransfer extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'number',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'from_warehouse_id' => 'integer',
        'to_warehouse_id' => 'integer',
    ];

    public function fromWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function items(): HasMany { return $this->hasMany(WarehouseTransferItem::class, 'transfer_id'); }
    public function getTotalQtyAttribute(): int { return (int) $this->items()->sum('qty'); }
}

class WarehouseTransferItem extends Model
{
    protected $fillable = ['transfer_id','product_id','qty'];
    protected $casts = ['qty' => 'integer'];

    public function transfer(): BelongsTo { return $this->belongsTo(WarehouseTransfer::class, 'transfer_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}