<?php

namespace Modules\Automation\Entities;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'name', 'referral_slug', 'channel', 'subject', 'message',
        'segment', 'rfm_group', 'status', 'total', 'sent', 'sent_at',
        'reward_rules', 'roi_revenue', 'clicks_count', 'conversions_count', 'meta'
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'reward_rules' => 'array',
        'meta'         => 'array',
        'roi_revenue'  => 'decimal:2',
    ];

    public const SEGMENTS = [
        'all'            => 'همهٔ مشتریان',
        'woocommerce'    => 'مشتریان فروشگاه اینترنتی',
        'has_orders'     => 'مشتریان دارای سابقه خرید',
        'referral_buyers'=> 'خریداران معرفی‌شده',
        'custom_segs'    => 'اعضای یک گروه ذخیره‌شده',
    ];

    public const RFM_GROUPS = [
        'all'          => 'همهٔ رفتارهای خرید',
        'champions'    => 'قهرمانان (پرتکرار و پرارزش)',
        'loyal'        => 'وفاداران (خریدهای مکرر)',
        'potential'    => 'در حال رشد (پتانسیل بالا)',
        'new'          => 'تازه‌واردها (اولین خرید)',
        'at_risk'      => 'در آستانه ریزش (نیازمند توجه)',
        'hibernating'  => 'خفته (مدت طولانی بدون خرید)',
        'lost'         => 'از دست رفته (ماه‌ها بدون مراجعه)',
    ];

    public const CHANNELS = [
        'sms'       => 'پیامک',
        'email'     => 'ایمیل',
        'whatsapp'  => 'واتس‌اپ',
        'bale'      => 'پیام‌رسان بله',
        'eitaa'     => 'پیام‌رسان ایتا',
        'telegram'  => 'تلگرام',
    ];
}
