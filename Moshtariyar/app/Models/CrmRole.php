<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmRole extends Model
{
    protected $table = 'crm_roles';
    protected $fillable = ['key','label','description','is_system','is_active'];
    protected $casts = ['is_system'=>'boolean','is_active'=>'boolean'];

    public function permissions(): HasMany
    {
        return $this->hasMany(CrmRolePermission::class, 'role_id');
    }
}
