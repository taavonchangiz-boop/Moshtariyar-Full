<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToBusiness;

class StockMovement extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'product_id',
        'warehouse_id',
        'supply_id',
        'transfer_id',
        'write_off_id',
        'type',
        'qty',
        'balance_after',
        'reason',
        'user_id',
    ];

    protected $casts = [
        'qty' => 'integer',
        'balance_after' => 'integer',
    ];

    public const TYPES = [
        'in' => 'ورود',
        'out' => 'خروج',
        'adjust' => 'اصلاح',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransfer::class, 'transfer_id');
    }

    public function writeOff(): BelongsTo
    {
        return $this->belongsTo(WriteOff::class, 'write_off_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}