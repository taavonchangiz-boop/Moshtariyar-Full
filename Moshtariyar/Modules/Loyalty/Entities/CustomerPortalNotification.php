<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Customer;

class CustomerPortalNotification extends Model
{
    protected $table = 'customer_portal_notifications';
    protected $fillable = ['customer_id', 'title', 'body', 'type', 'url', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
