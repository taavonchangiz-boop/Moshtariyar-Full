<?php

namespace App\Traits;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness()
    {
        static::addGlobalScope('business', function (Builder $builder) {
            // اگر کاربری وارد شده بود و مدیر کل هلدینگ نبود، فقط اطلاعات کسب‌وکار خودش را ببیند
            if (Auth::check() && !Auth::user()->is_superadmin) {
                $builder->where('business_id', Auth::user()->business_id);
            }
        });

        static::creating(function ($model) {
            // موقع ساخت رکورد جدید، شناسه کسب‌وکار شخص را به صورت خودکار ثبت کن
            if (Auth::check() && !Auth::user()->is_superadmin && empty($model->business_id)) {
                $model->business_id = Auth::user()->business_id;
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}