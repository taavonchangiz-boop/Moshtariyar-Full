<?php

namespace Modules\WooBridge\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $table = 'wb_sync_logs';

    protected $fillable = [
        'connection_id', 'entity', 'woo_id', 'delivery_id',
        'direction', 'status', 'payload', 'error',
    ];

    protected $casts = ['payload' => 'array'];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(WooConnection::class, 'connection_id');
    }

    public function entityLabel(): string
    {
        return [
            'order' => 'سفارش',
            'customer' => 'مشتری',
            'product' => 'کالا',
            'connection' => 'اتصال',
        ][$this->entity] ?? ($this->entity ?: 'نامشخص');
    }

    public function directionLabel(): string
    {
        return $this->direction === 'in' ? 'از فروشگاه به سامانه' : 'از سامانه به فروشگاه';
    }

    public function statusLabel(): string
    {
        return [
            'success' => 'موفق',
            'failed' => 'ناموفق',
            'pending' => 'در صف',
        ][$this->status] ?? ($this->status ?: 'نامشخص');
    }

    public function shortError(): string
    {
        $text = trim((string) ($this->error ?? ''));
        if ($text === '') {
            return '—';
        }
        return mb_strimwidth($text, 0, 140, '...');
    }

    public function payloadSummary(): string
    {
        $payload = is_array($this->payload) ? $this->payload : [];
        if (empty($payload)) {
            return '—';
        }

        $parts = [];
        if (isset($payload['id'])) $parts[] = 'شناسه: ' . $payload['id'];
        if (isset($payload['number'])) $parts[] = 'شماره: ' . $payload['number'];
        if (isset($payload['name'])) $parts[] = 'نام: ' . $payload['name'];
        if (isset($payload['status'])) $parts[] = 'وضعیت: ' . $payload['status'];
        if (isset($payload['message'])) $parts[] = 'شرح: ' . $payload['message'];
        if (isset($payload['orders_count'])) $parts[] = 'سفارش: ' . $payload['orders_count'];
        if (isset($payload['customers_count'])) $parts[] = 'مشتری: ' . $payload['customers_count'];
        if (isset($payload['products_count'])) $parts[] = 'کالا: ' . $payload['products_count'];

        return $parts ? implode(' | ', $parts) : 'اطلاعات ثبت شده است';
    }

    public function suggestedAction(): string
    {
        if ($this->status === 'success') {
            return 'این مورد با موفقیت انجام شده و نیازی به اقدام ندارد.';
        }

        $error = mb_strtolower((string) $this->error);

        if (str_contains($error, 'زمان تعیین') || str_contains($error, 'timeout') || str_contains($error, 'cURL error 28')) {
            return 'فعلاً از ورود فایل داده استفاده کنید و از پشتیبانی هاست بخواهید ارتباط خروجی روی پورت ۴۴۳ و نسخه ۴ آی‌پی را بررسی کند.';
        }

        if (str_contains($error, 'کلید اتصال') || str_contains($error, 'رمز اتصال') || str_contains($error, '401')) {
            return 'کلید اتصال و رمز اتصال ووکامرس را دوباره بررسی کنید و مطمئن شوید دسترسی لازم داده شده است.';
        }

        if (str_contains($error, '403') || str_contains($error, 'فایروال')) {
            return 'تنظیمات امنیتی فروشگاه یا فایروال را بررسی کنید. ممکن است ارتباط سرور به سرور بسته شده باشد.';
        }

        if (str_contains($error, '404')) {
            return 'نشانی فروشگاه یا مسیر ووکامرس را بررسی کنید. ممکن است آدرس نادرست باشد.';
        }

        if ($this->direction === 'in') {
            return 'می‌توانید این مورد را دوباره در صف پردازش بگذارید و اگر خطا تکرار شد، داده ورودی فروشگاه را بررسی کنید.';
        }

        return 'می‌توانید این مورد را دوباره ارسال کنید و اگر خطا تکرار شد، تنظیمات اتصال را بررسی کنید.';
    }
}
