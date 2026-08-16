<?php

namespace Modules\Core\Support;

/**
 * فارسی‌سازی اعداد (ارقام انگلیسی → فارسی) برای نمایش.
 */
class Num
{
    private const EN = ['0','1','2','3','4','5','6','7','8','9'];
    private const FA = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

    /** تبدیل ارقام انگلیسی هر رشته به فارسی */
    public static function fa(string|int|float|null $value): string
    {
        if ($value === null) return '';
        return str_replace(self::EN, self::FA, (string) $value);
    }

    /** عدد با جداکنندهٔ هزارگان + ارقام فارسی */
    public static function money(int|float|null $value): string
    {
        return self::fa(number_format((float) ($value ?? 0)));
    }
}
