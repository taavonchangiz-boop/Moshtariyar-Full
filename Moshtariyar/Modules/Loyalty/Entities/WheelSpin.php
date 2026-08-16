<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WheelSpin extends Model
{
    protected $table = 'wheel_spins';

    protected $fillable = [
        'member_id',
        'prize_id',
        'prize_title',
        'prize_type',
        'amount',
        'prize_image',
        'coupon_id',
        'delivery_channels',
        'delivery_status',
        'delivery_report',
    ];

    protected $casts = [
        'amount' => 'integer',
        'delivery_channels' => 'array',
        'delivery_report' => 'array',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(LoyaltyMember::class, 'member_id');
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(WheelPrize::class, 'prize_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCoupon::class, 'coupon_id');
    }

    public function deliveryStatusLabel(): string
    {
        return [
            'pending' => 'در انتظار ارسال',
            'done' => 'ارسال شده',
            'partial' => 'ارسال ناقص',
            'failed' => 'ناموفق',
            'internal' => 'ثبت داخل پنل',
        ][$this->delivery_status] ?? 'نامشخص';
    }
}