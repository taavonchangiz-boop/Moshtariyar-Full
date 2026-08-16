<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToBusiness; // اضافه شد

class Customer extends Model
{
    use BelongsToBusiness; // اضافه شد

    // فیلد business_id به لیست مجاز اضافه شد
    protected $fillable = [
        'business_id', 'type', 'full_name', 'company_name', 'email', 'phone', 'portal_password',
        'national_id', 'economic_code', 'source', 'lifetime_value', 'tags', 'meta',
    ];

    protected $hidden = ['portal_password'];

    protected $casts = [
        'tags' => 'array',
        'meta' => 'array',
        'lifetime_value' => 'decimal:2',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(Segment::class, 'segment_member')
            ->withTimestamps();
    }

    /**
     * فعالیت‌های ثبت شده برای مشتری
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'subject_id')
            ->where('subject_type', Customer::class)
            ->orWhere('subject_type', 'customer')
            ->latest();
    }

    /** محاسبهٔ مجدد ارزش خرید مشتری از روی سفارش‌های معتبر */
    public function recalcLifetimeValue(): void
    {
        $this->lifetime_value = $this->orders()
            ->whereIn('status', ['processing', 'completed'])
            ->sum('total');
        $this->save();
    }

    /** روزهای گذشته از آخرین خرید موفق */
    public function getDaysSinceLastPurchase(): int
    {
        $last = $this->orders()
            ->whereIn('status', ['processing', 'completed'])
            ->latest('placed_at')
            ->first();

        if (!$last || !$last->placed_at) {
            return 9999;
        }

        return (int) now()->diffInDays($last->placed_at);
    }

    /**
     * RFM Segment (safe fallback)
     */
    public function getRfmSegmentAttribute(): string
    {
        try {
            if (class_exists(\Modules\Core\Services\Insights::class)) {
                $rfm = \Modules\Core\Services\Insights::rfm($this);
                [$segment] = \Modules\Core\Services\Insights::segment($rfm);
                return $segment ?: 'نامشخص';
            }
        } catch (\Throwable $e) {}
        return 'نامشخص';
    }

    /** تعداد سفارش‌های موفق */
    public function getCompletedOrdersCountAttribute(): int
    {
        return $this->orders()
            ->where('status', 'completed')
            ->count();
    }

    /** آیا مشتری فعال است (خرید در ۳۰ روز اخیر) */
    public function getIsActiveAttribute(): bool
    {
        return $this->orders()
            ->whereIn('status', ['processing', 'completed'])
            ->where('placed_at', '>=', now()->subDays(30))
            ->exists();
    }
}