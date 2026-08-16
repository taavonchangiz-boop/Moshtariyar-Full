<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToBusiness;

class Warehouse extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'type',
        'location',
        'phone',
        'manager_id',
        'capacity',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    public function manager()
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    public function products(): HasMany { return $this->hasMany(WarehouseProduct::class); }
    public function movements(): HasMany { return $this->hasMany(StockMovement::class); }
    public function supplies(): HasMany { return $this->hasMany(Supply::class); }

    public function getStockValueAttribute(): float
    {
        try { return (float) $this->products()->sum(\DB::raw('stock * avg_cost')); }
        catch (\Throwable $e) { return 0; }
    }

    public function getTotalItemsAttribute(): int { return (int) $this->products()->where('stock', '>', 0)->count(); }

    public function getLowStockCountAttribute(): int
    {
        return (int) $this->products()->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0)->count();
    }
}