<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToBusiness;

class WriteOffReason extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id','title','color','is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function writeOffs(): HasMany { return $this->hasMany(WriteOff::class, 'reason_id'); }
}

class WriteOff extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id','warehouse_id','reason_id','number','note','created_by'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function reason() { return $this->belongsTo(WriteOffReason::class, 'reason_id'); }
    public function items() { return $this->hasMany(WriteOffItem::class); }
}

class WriteOffItem extends Model
{
    protected $fillable = ['write_off_id','product_id','qty'];
    protected $casts = ['qty' => 'integer'];

    public function writeOff() { return $this->belongsTo(WriteOff::class); }
    public function product() { return $this->belongsTo(Product::class); }
}