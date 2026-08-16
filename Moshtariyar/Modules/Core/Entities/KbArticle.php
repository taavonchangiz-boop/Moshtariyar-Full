<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticle extends Model
{
    protected $table = 'kb_articles';
    protected $fillable = ['category_id','title','slug','question','answer','visibility','tags','views','is_active'];
    protected $casts = ['tags'=>'array','is_active'=>'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'category_id');
    }
}
