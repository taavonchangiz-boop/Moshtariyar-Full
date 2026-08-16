<?php

namespace Modules\IranPack\Tax;

/**
 * تولید شناسهٔ یکتای مالیاتی (۲۲ کاراکتری) مطابق ساختار سامانه مودیان:
 *   6 رقم: شناسهٔ حافظهٔ مالیاتی (Memory/Fiscal ID)
 *   5 رقم: تاریخ صدور به‌صورت روزشمار مبنا (به‌صورت ساده‌شده hex/dec)
 *   10 رقم: شمارهٔ سریال داخلی
 *   1 رقم: رقم کنترلی
 * نکته: این پیاده‌سازی ساختار را می‌سازد؛ الگوریتم رقم کنترلی رسمی باید
 * در زمان اتصال واقعی با مستندات روز سامانه تطبیق داده شود.
 */
class TaxIdGenerator
{
    public static function generate(string $memoryId, int $serial, ?\DateTimeInterface $date = null): string
    {
        $date ??= new \DateTimeImmutable();

        $mem = str_pad(substr(preg_replace('/\D/', '', $memoryId) ?: '0', 0, 6), 6, '0', STR_PAD_LEFT);

        // تعداد روز از مبدأ (۲۰۰۰-۰۱-۰۱) به‌صورت ۵ رقمی
        $epoch = (int) floor(($date->getTimestamp() - strtotime('2000-01-01')) / 86400);
        $dayPart = str_pad(substr((string) $epoch, -5), 5, '0', STR_PAD_LEFT);

        $serialPart = str_pad((string) ($serial % 10_000_000_000), 10, '0', STR_PAD_LEFT);

        $body = $mem . $dayPart . $serialPart;
        $check = self::checkDigit($body);

        return $body . $check; // 22 char
    }

    private static function checkDigit(string $body): int
    {
        $sum = 0;
        foreach (str_split($body) as $i => $d) {
            $sum += ((int) $d) * (($i % 9) + 2);
        }
        return $sum % 10;
    }
}
