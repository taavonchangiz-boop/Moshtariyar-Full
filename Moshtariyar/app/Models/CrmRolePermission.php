<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmRolePermission extends Model
{
    protected $table = 'crm_role_permissions';
    protected $fillable = ['role_id','permission'];
}
