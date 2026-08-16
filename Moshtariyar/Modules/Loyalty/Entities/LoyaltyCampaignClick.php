<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCampaignClick extends Model
{
    protected $table = 'loyalty_campaign_clicks';
    protected $fillable = ['campaign_id','channel','referrer_member_id','referral_code','ip','user_agent'];
}
