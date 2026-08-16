<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMessage extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'channel',
        'message',
    ];

    /**
     * ارتباط با مشتری (چه کسی پیام را گرفته)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * ارتباط با کاربر/کارمند (چه کسی پیام را فرستاده)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}