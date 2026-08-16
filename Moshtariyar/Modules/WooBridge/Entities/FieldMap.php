<?php

namespace Modules\WooBridge\Entities;

use Illuminate\Database\Eloquent\Model;

class FieldMap extends Model
{
    protected $table = 'wb_field_maps';
    public $timestamps = false;

    protected $fillable = ['connection_id', 'entity', 'woo_field', 'crm_field', 'is_unique_key'];

    protected $casts = ['is_unique_key' => 'boolean'];
}
