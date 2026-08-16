<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'is_active',
        'logo',
        'settings'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(BusinessSubscription::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // این تابع بررسی می‌کند آیا کسب‌وکار به یک ماژول خاص دسترسی دارد یا خیر
    public function hasModule(string $moduleName): bool
    {
        $subscription = $this->subscription;
        if (!$subscription || !$subscription->is_active) {
            return false;
        }

        $activeModules = $subscription->active_modules ?? [];
        return in_array($moduleName, $activeModules, true);
    }
}