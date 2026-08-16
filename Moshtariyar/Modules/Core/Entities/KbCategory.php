<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbCategory extends Model
{
    protected $table = 'kb_categories';
    protected $fillable = ['title','slug','description','order','is_active'];
    protected $casts = ['is_active'=>'boolean'];

    public function articles(): HasMany
    {
        return $this->hasMany(KbArticle::class, 'category_id');
    }
}
