<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToBusiness;

class Lead extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'phone',
        'source',
        'status_id',
        'assigned_to',
        'score',
        'value',
        'converted_customer_id',
    ];
}