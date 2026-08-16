<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCampaignChannel extends Model
{
    protected $table = 'loyalty_campaign_channels';
    protected $fillable = ['campaign_id','channel','label','is_active'];
    protected $casts = ['is_active'=>'boolean'];

    public const CHANNELS = [
        'direct' => 'لینک مستقیم',
        'whatsapp' => 'واتساپ',
        'telegram' => 'تلگرام',
        'instagram' => 'اینستاگرام',
        'eitaa' => 'ایتا',
        'bale' => 'بله',
        'sms' => 'پیامک',
    ];
}
