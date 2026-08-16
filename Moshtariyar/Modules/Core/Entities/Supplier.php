<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToBusiness;

class Supplier extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'category',
        'phone',
        'email',
        'address',
        'bank_account',
        'payment_type',
        'credit_limit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    public function supplies(): HasMany { return $this->hasMany(Supply::class); }

    public function getTotalPurchasesAttribute(): float
    {
        try { return (float) $this->supplies()->sum('total_cost'); }
        catch (\Throwable $e) { return 0; }
    }
}