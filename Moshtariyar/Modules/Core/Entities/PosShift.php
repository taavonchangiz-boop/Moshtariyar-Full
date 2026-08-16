<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToBusiness;

class PosShift extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id','user_id','warehouse_id','opened_at','closed_at',
        'opening_cash','closing_cash','cash_sales','card_sales','transfer_sales',
        'total_sales','total_discount','orders_count','status','note',
    ];

    protected $casts = [
        'opened_at' => 'datetime','closed_at' => 'datetime',
        'opening_cash' => 'decimal:2','closing_cash' => 'decimal:2',
        'cash_sales' => 'decimal:2','card_sales' => 'decimal:2','transfer_sales' => 'decimal:2',
        'total_sales' => 'decimal:2','total_discount' => 'decimal:2',
    ];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function getIsOpenAttribute(): bool { return $this->status === 'open' && is_null($this->closed_at); }
    public function getDurationAttribute(): string
    {
        $start = $this->opened_at;
        $end = $this->closed_at ?? now();
        $diff = $start->diff($end);
        return $diff->h . ' ساعت و ' . $diff->i . ' دقیقه';
    }
}