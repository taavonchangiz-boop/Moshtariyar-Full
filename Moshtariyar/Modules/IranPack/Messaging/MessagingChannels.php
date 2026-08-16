<?php

namespace Modules\IranPack\Messaging;

use Modules\Core\Entities\Setting;

/**
 * مدیریت کانال‌های پیام‌رسان (واتساپ، تلگرام، بله، روبیکا، ایتا).
 * چند کانال می‌توانند هم‌زمان فعال باشند بدون اختلال در یکدیگر.
 */
class MessagingChannels
{
    public const CHANNELS = [
        'whatsapp' => 'واتساپ',
        'telegram' => 'تلگرام',
        'bale'     => 'بله',
        'rubika'   => 'روبیکا',
        'eitaa'    => 'ایتا',
    ];

    /** آیا یک کانال فعال است؟ */
    public static function isEnabled(string $channel): bool
    {
        return (bool) Setting::get("channel_{$channel}_enabled", false);
    }

    /** فهرست کانال‌های فعال */
    public static function enabled(): array
    {
        $out = [];
        foreach (self::CHANNELS as $key => $label) {
            if (self::isEnabled($key)) {
                $out[$key] = $label;
            }
        }
        return $out;
    }

    /**
     * ارسال پیام از طریق همهٔ کانال‌های فعال.
     * اگر $to خالی باشد، مقصد پیش‌فرض هر کانال از تنظیمات خوانده می‌شود.
     */
    public static function broadcast(?string $to, string $message): array
    {
        $results = [];
        foreach (self::enabled() as $key => $label) {
            try {
                $results[$key] = self::sendVia($key, $to, $message);
            } catch (\Throwable $e) {
                $results[$key] = false;
                report($e);
            }
        }
        return $results;
    }

    /** ارسال از طریق یک کانال مشخص */
    public static function sendVia(string $channel, ?string $to, string $message): bool
    {
        $token = Setting::get("channel_{$channel}_token");
        if (! $token) {
            return false;
        }

        $to = $to ?: self::defaultTarget($channel);
        if (! $to) {
            return false;
        }

        return match ($channel) {
            'telegram' => self::telegram($token, $to, $message),
            'bale'     => self::bale($token, $to, $message),
            'eitaa'    => self::eitaa($token, $to, $message),
            'rubika'   => self::rubika($token, $to, $message),
            'whatsapp' => self::whatsapp($token, $to, $message),
            default    => false,
        };
    }

    private static function defaultTarget(string $channel): ?string
    {
        return match ($channel) {
            'whatsapp' => Setting::get('channel_whatsapp_to'),
            'telegram' => Setting::get('channel_telegram_chatid'),
            'bale'     => Setting::get('channel_bale_chatid'),
            'rubika'   => Setting::get('channel_rubika_chatid'),
            'eitaa'    => Setting::get('channel_eitaa_chatid'),
            default    => null,
        };
    }

    private static function http(): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client(['timeout' => 20]);
    }

    private static function telegram(string $token, string $chatId, string $text): bool
    {
        $res = self::http()->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'json' => ['chat_id' => $chatId, 'text' => $text],
        ]);
        return str_contains((string) $res->getBody(), '"ok":true');
    }

    private static function bale(string $token, string $chatId, string $text): bool
    {
        $res = self::http()->post("https://tapi.bale.ai/bot{$token}/sendMessage", [
            'json' => ['chat_id' => $chatId, 'text' => $text],
        ]);
        return str_contains((string) $res->getBody(), '"ok":true');
    }

    private static function eitaa(string $token, string $chatId, string $text): bool
    {
        $res = self::http()->post("https://eitaayar.ir/api/{$token}/sendMessage", [
            'form_params' => ['chat_id' => $chatId, 'text' => $text],
        ]);
        return str_contains((string) $res->getBody(), '"ok":true');
    }

    private static function rubika(string $token, string $chatId, string $text): bool
    {
        $endpoint = rtrim((string) Setting::get('channel_rubika_endpoint', 'https://botapi.rubika.ir/v3'), '/');
        $res = self::http()->post("{$endpoint}/{$token}/sendMessage", [
            'json' => ['chat_id' => $chatId, 'text' => $text],
        ]);
        return $res->getStatusCode() === 200;
    }

    private static function whatsapp(string $token, string $to, string $text): bool
    {
        $endpoint = Setting::get('channel_whatsapp_endpoint');
        if (! $endpoint) return false;
        $res = self::http()->post($endpoint, [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'json'    => ['to' => $to, 'message' => $text],
        ]);
        return $res->getStatusCode() < 300;
    }
}
