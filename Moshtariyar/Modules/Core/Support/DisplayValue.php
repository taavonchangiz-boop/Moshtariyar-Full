<?php

namespace Modules\Core\Support;

class DisplayValue
{
    public static function show(mixed $value, ?string $label = null): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'بله' : 'خیر';
        }

        if (is_numeric($value)) {
            return self::formatNumber((float) $value, $label);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '—';
        }

        if (self::looksLikeDate($text) || self::labelMeansDate($label)) {
            try {
                $dt = \Carbon\Carbon::parse($text);
                return str_contains($text, ':')
                    ? Jalali::datetime($dt)
                    : Jalali::date($dt);
            } catch (\Throwable $e) {
                return Num::fa($text);
            }
        }

        return Num::fa($text);
    }

    private static function formatNumber(float $value, ?string $label = null): string
    {
        if (self::labelMeansMoney($label)) {
            return Money::show($value) . ' ' . Money::unitLabel();
        }

        if (floor($value) == $value) {
            return Num::fa(number_format($value));
        }

        $text = rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');
        return Num::fa($text);
    }

    private static function labelMeansMoney(?string $label): bool
    {
        $label = trim((string) $label);
        if ($label === '') {
            return false;
        }

        foreach (['مبلغ', 'مالیات', 'نرخ', 'ارزش', 'اجرت', 'سود', 'پرداخت', 'کیف پول', 'جمع'] as $word) {
            if (mb_strpos($label, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function labelMeansDate(?string $label): bool
    {
        $label = trim((string) $label);
        if ($label === '') {
            return false;
        }

        foreach (['زمان', 'تاریخ', 'مهلت', 'یادآوری'] as $word) {
            if (mb_strpos($label, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function looksLikeDate(string $text): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $text);
    }
}
