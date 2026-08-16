<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSubscription extends Model
{
    protected $fillable = [
        'business_id',
        'plan_name',
        'active_modules',
        'starts_at',
        'ends_at',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'active_modules' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}