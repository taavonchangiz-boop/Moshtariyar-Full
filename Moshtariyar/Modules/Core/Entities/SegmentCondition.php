<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentCondition extends Model
{
    protected $fillable = [
        'segment_id', 'field', 'operator', 'value', 'value2', 'sort_order',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
