<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Segment extends Model
{
    protected $fillable = [
        'name', 'type', 'logic', 'description',
        'is_active', 'members_count', 'evaluated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'evaluated_at' => 'datetime',
    ];

    public function conditions(): HasMany
    {
        return $this->hasMany(SegmentCondition::class)->orderBy('sort_order');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'segment_member')
            ->withTimestamps();
    }
}
