<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WheelPrize extends Model
{
    protected $table = 'wheel_prizes';

    protected $fillable = [
        'title',
        'prize_type',
        'amount',
        'chance',
        'color',
        'image',
        'delivery_channels',
        'delivery_message',
        'coupon_discount_type',
        'coupon_discount_value',
        'coupon_expires_days',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_channels' => 'array',
        'coupon_discount_value' => 'decimal:2',
        'coupon_expires_days' => 'integer',
    ];

    public const TYPES = [
        'point' => 'امتیاز',
        'wallet' => 'اعتبار کیف پول',
        'coupon' => 'کد تخفیف اختصاصی',
        'nothing' => 'پوچ',
    ];

    public const DELIVERY_CHANNELS = [
        'portal' => 'اعلان داخل پنل',
        'sms' => 'پیامک',
        'email' => 'ایمیل',
        'whatsapp' => 'واتساپ',
        'telegram' => 'تلگرام',
        'bale' => 'بله',
        'rubika' => 'روبیکا',
        'eitaa' => 'ایتا',
    ];

    public function spins(): HasMany
    {
        return $this->hasMany(WheelSpin::class, 'prize_id');
    }

    public function selectedDeliveryChannels(): array
    {
        $channels = $this->delivery_channels;
        if (! is_array($channels) || empty($channels)) {
            return ['portal'];
        }

        return array_values(array_intersect(array_keys(self::DELIVERY_CHANNELS), $channels));
    }
}