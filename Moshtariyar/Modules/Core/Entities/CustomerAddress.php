<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = ['customer_id', 'kind', 'province', 'city', 'address', 'postal_code'];
}
