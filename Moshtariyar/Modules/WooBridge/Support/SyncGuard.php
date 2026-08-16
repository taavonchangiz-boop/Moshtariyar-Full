<?php

namespace Modules\WooBridge\Support;

/**
 * جلوگیری از حلقهٔ بازگشتی (echo loop) در همگام‌سازی دوطرفه.
 * هنگام نوشتن دادهٔ ورودی از ووکامرس، observerهای خروجی نباید فعال شوند.
 */
class SyncGuard
{
    private static bool $muted = false;

    public static function isMuted(): bool
    {
        return self::$muted;
    }

    /** اجرای یک callback بدون فعال‌شدن همگام‌سازی خروجی */
    public static function muted(callable $callback): mixed
    {
        $previous = self::$muted;
        self::$muted = true;
        try {
            return $callback();
        } finally {
            self::$muted = $previous;
        }
    }
}
