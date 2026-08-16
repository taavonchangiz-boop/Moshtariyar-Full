<?php

namespace Modules\Automation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyStep extends Model
{
    protected $fillable = [
        'workflow_id', 'step_order', 'type', 'label',
        'wait_hours', 'condition_field', 'condition_value',
        'action_type', 'action_channel', 'action_template',
        'branch_yes_step_order', 'branch_no_step_order',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
