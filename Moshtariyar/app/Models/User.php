<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'business_id', 
        'name', 
        'email', 
        'phone', 
        'password', 
        'role', 
        'is_superadmin',
        'is_active'
    ];
    
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_superadmin' => 'boolean',
        ];
    }

    // ارتباط کاربر با کسب‌وکار خودش
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // آیا این کاربر مدیر کل پلتفرم (هلدینگ لوکس) است؟
    public function isSuperAdmin(): bool
    {
        return $this->is_superadmin === true;
    }

    // آیا کاربر مدیر ارشد کسب‌وکار خودش است؟
    public function isBusinessAdmin(): bool
    {
        return $this->role === Roles::ADMIN;
    }

    /** بررسی مجوز بر اساس نقش */
    public function hasPermission(string $permission): bool
    {
        // مدیر کل هلدینگ به همه چیز دسترسی دارد
        if ($this->isSuperAdmin()) {
            return true;
        }

        return Roles::can($this->role ?? '', $permission);
    }

    public function roleLabel(): string
    {
        if ($this->isSuperAdmin()) {
            return 'مدیر کل پلتفرم';
        }
        return Roles::labels()[$this->role] ?? $this->role;
    }
}