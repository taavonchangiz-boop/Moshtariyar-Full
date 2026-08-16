<?php

namespace Modules\IranPack\Tax;

/**
 * محاسبهٔ مالیات بر ارزش افزوده طبق نرخ جاری.
 * نرخ از .env قابل تنظیم است (پیش‌فرض ۱۰٪ مطابق قانون فعلی).
 */
class Vat
{
    public static function rate(): float
    {
        return (float) env('VAT_RATE', 0.10); // ۱۰ درصد
    }

    /** مبلغ مالیات روی یک مبلغ پایه (ریال، گرد به ریال) */
    public static function amount(float $base): int
    {
        return (int) round($base * self::rate());
    }

    /** قیمت با احتساب مالیات */
    public static function withVat(float $base): int
    {
        return (int) round($base + self::amount($base));
    }
}
