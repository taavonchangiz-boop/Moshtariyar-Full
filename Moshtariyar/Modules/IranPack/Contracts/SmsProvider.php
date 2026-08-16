<?php

namespace Modules\IranPack\Contracts;

interface SmsProvider
{
    /** ارسال پیامک ساده */
    public function send(string $to, string $message): bool;

    /** ارسال پیامک با الگو/پترن (مطابق الزامات پنل‌های ایرانی) */
    public function sendPattern(string $to, string $patternCode, array $tokens): bool;
}
