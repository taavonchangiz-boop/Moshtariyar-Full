<?php

namespace Modules\WooBridge\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class WooConnection extends Model
{
    protected $table = 'wb_connections';

    protected $fillable = [
        'name', 'store_url', 'consumer_key', 'consumer_secret',
        'webhook_secret', 'is_active', 'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = ['consumer_key', 'consumer_secret', 'webhook_secret'];

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'connection_id')->latest('id');
    }

    public function idMaps(): HasMany
    {
        return $this->hasMany(IdMap::class, 'connection_id');
    }

    public function hasApiCredentials(): bool
    {
        return filled($this->consumer_key) && filled($this->consumer_secret);
    }

    public function connectionStatusLabel(): string
    {
        if (! $this->is_active) {
            return 'غیرفعال';
        }

        if (! $this->hasApiCredentials()) {
            return 'فقط ورود فایل داده';
        }

        return 'اتصال مستقیم فعال';
    }

    public function maskedWebhookSecret(): string
    {
        $value = (string) ($this->webhook_secret ?? '');
        if (mb_strlen($value) <= 8) {
            return $value;
        }

        return mb_substr($value, 0, 4) . '••••••' . mb_substr($value, -4);
    }

    // کلیدهای ووکامرس به‌صورت رمزنگاری‌شده ذخیره می‌شوند
    public function setConsumerKeyAttribute($v): void
    {
        $this->attributes['consumer_key'] = $v ? Crypt::encryptString($v) : null;
    }

    public function getConsumerKeyAttribute($v): ?string
    {
        return $v ? Crypt::decryptString($v) : null;
    }

    public function setConsumerSecretAttribute($v): void
    {
        $this->attributes['consumer_secret'] = $v ? Crypt::encryptString($v) : null;
    }

    public function getConsumerSecretAttribute($v): ?string
    {
        return $v ? Crypt::decryptString($v) : null;
    }
}
