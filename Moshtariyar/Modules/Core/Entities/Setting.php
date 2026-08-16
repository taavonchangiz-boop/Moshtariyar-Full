<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'is_secret'];
    protected $casts = ['is_secret' => 'boolean'];

    /** خواندن یک تنظیم؛ اگر در دیتابیس نبود، از .env (پیش‌فرض) */
    public static function get(string $key, $default = null)
    {
        $all = static::allCached();
        if (! array_key_exists($key, $all)) {
            return env(strtoupper($key), $default);
        }
        $row = $all[$key];
        $val = $row['value'];
        if ($row['is_secret'] && $val) {
            try { $val = Crypt::decryptString($val); } catch (\Throwable $e) {}
        }
        return $val ?? $default;
    }

    /** ذخیرهٔ یک تنظیم */
    public static function put(string $group, string $key, $value, bool $secret = false): void
    {
        if ($secret && $value) {
            $value = Crypt::encryptString($value);
        }
        static::updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $value, 'is_secret' => $secret]
        );
        Cache::forget('settings.all');
    }

    private static function allCached(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return static::all()->keyBy('key')
                ->map(fn ($s) => ['value' => $s->value, 'is_secret' => $s->is_secret])
                ->toArray();
        });
    }

    public static function flush(): void
    {
        Cache::forget('settings.all');
    }
}
