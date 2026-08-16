<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    protected $table = 'loyalty_transactions';
    protected $fillable = [
        'member_id', 'kind', 'direction', 'amount', 'balance_after',
        'reason', 'ref_type', 'ref_id', 'expires_at',
    ];
    protected $casts = ['expires_at' => 'datetime'];

    public const KINDS = ['point' => 'امتیاز', 'wallet' => 'اعتبار کیف پول'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(LoyaltyMember::class, 'member_id');
    }
}
