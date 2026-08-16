<?php

namespace Modules\Automation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyExecution extends Model
{
    protected $table = 'journey_executions';

    protected $fillable = [
        'workflow_id', 'customer_id', 'current_step_order',
        'status', 'context', 'next_run_at',
    ];

    protected $casts = [
        'context' => 'array',
        'next_run_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Entities\Customer::class, 'customer_id');
    }
}
