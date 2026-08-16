<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'customer_id', 'subject', 'priority', 'status', 'department', 'assigned_to', 'customer_unread', 'staff_unread', 'last_reply_at', 'first_response_at', 'sla_due_at', 'sla_breached_at', 'closed_at',
    ];
    protected $casts = ['last_reply_at' => 'datetime', 'first_response_at' => 'datetime', 'sla_due_at' => 'datetime', 'sla_breached_at' => 'datetime', 'closed_at' => 'datetime', 'customer_unread' => 'boolean', 'staff_unread' => 'boolean'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }
}
