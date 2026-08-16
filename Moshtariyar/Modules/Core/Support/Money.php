<?php

namespace Modules\Core\Support;

use Modules\Core\Entities\Setting;

class Money
{
    public static function unit(): string
    {
        return Setting::get('currency_unit', env('CURRENCY_UNIT', 'toman'));
    }

    public static function unitLabel(): string
    {
        return self::unit() === 'rial' ? 'ریال' : 'تومان';
    }

    public static function divisor(): int
    {
        return 1;
    }

    public static function show($amount): string
    {
        return Num::fa(number_format((float) ($amount ?? 0)));
    }

    public static function toRial($displayAmount): int
    {
        return (int) round((float) $displayAmount);
    }

    public static function fromToman(float $amount): float
    {
        return self::unit() === 'rial' ? $amount * 10 : $amount;
    }
}
