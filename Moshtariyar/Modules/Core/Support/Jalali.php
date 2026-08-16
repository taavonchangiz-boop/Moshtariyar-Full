<?php

namespace Modules\Core\Support;

/**
 * تبدیل تاریخ میلادی ↔ شمسی (جلالی) — خالص PHP، بدون وابستگی خارجی.
 * مناسب نمایش تاریخ شمسی و فیلتر بازهٔ تاریخ در گزارش‌ها.
 */
class Jalali
{
    /** میلادی (Y,m,d) → جلالی [jy,jm,jd] */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
              + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return [$jy, $jm, $jd];
    }

    /** جلالی (jy,jm,jd) → میلادی [gy,gm,gd] */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd
              + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = [0, 31, ((($gy % 4 == 0) && ($gy % 100 != 0)) || ($gy % 400 == 0)) ? 29 : 28,
                  31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($gm = 0; $gm < 13 && $gd > $sal_a[$gm]; $gm++) {
            $gd -= $sal_a[$gm];
        }
        return [$gy, $gm, $gd];
    }

    /** رشتهٔ تاریخ شمسی از یک شیء تاریخ (با ارقام فارسی) */
    public static function date(?\DateTimeInterface $dt, string $sep = '/'): string
    {
        if (! $dt) return '—';
        [$jy, $jm, $jd] = self::toJalali((int) $dt->format('Y'), (int) $dt->format('n'), (int) $dt->format('j'));
        $s = sprintf('%04d%s%02d%s%02d', $jy, $sep, $jm, $sep, $jd);
        return Num::fa($s);
    }

    /** تاریخ و ساعت شمسی */
    public static function datetime(?\DateTimeInterface $dt): string
    {
        if (! $dt) return '—';
        // قانون نمایش تاریخ/زمان در کل برنامه: تاریخ و ساعت باید با جداکنندهٔ واضح نمایش داده شوند.
        return self::date($dt) . ' - ' . Num::fa($dt->format('H:i'));
    }

    /** تبدیل رشتهٔ شمسی «۱۴۰۳/۰۱/۰۱» یا «1403-01-01» به Carbon میلادی */
    public static function parse(?string $jalali): ?\Carbon\Carbon
    {
        if (! $jalali) return null;
        $en = strtr($jalali, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
        if (! preg_match('/(\d{4})\D(\d{1,2})\D(\d{1,2})/', $en, $m)) return null;
        [$gy, $gm, $gd] = self::toGregorian((int) $m[1], (int) $m[2], (int) $m[3]);
        try {
            return \Carbon\Carbon::createFromDate($gy, $gm, $gd)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** نام ماه شمسی */
    public static function monthName(int $jm): string
    {
        $names = ['', 'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
        return $names[$jm] ?? '';
    }
}
