<?php

namespace Modules\IranPack\Managers;

use Modules\IranPack\Contracts\SmsProvider;
use Modules\IranPack\Sms\SmsIrProvider;
use Modules\IranPack\Sms\MeliPayamakProvider;
use Modules\IranPack\Sms\KavenegarProvider;
use Modules\Core\Entities\Setting;

class SmsManager
{
    public const PROVIDERS = [
        'smsir'        => SmsIrProvider::class,
        'melipayamak'  => MeliPayamakProvider::class,
        'kavenegar'    => KavenegarProvider::class,
    ];

    public function driver(?string $name = null): SmsProvider
    {
        $name = $name ?: $this->default();

        if (! isset(self::PROVIDERS[$name])) {
            $map = [
                'kavenegar' => 'kavenegar',
                'sms_ir' => 'smsir',
                'smsir' => 'smsir',
                'melipayamak' => 'melipayamak',
                'meli' => 'melipayamak',
            ];
            $name = $map[$name] ?? 'smsir';
        }

        if (! isset(self::PROVIDERS[$name])) {
            $name = 'smsir';
        }

        return app(self::PROVIDERS[$name]);
    }

    public function default(): string
    {
        $val = (string) Setting::get('sms_default', '');
        if ($val !== '') return $val;

        $val = (string) Setting::get('sms_provider', '');
        if ($val !== '') {
            $map = [
                'kavenegar' => 'kavenegar',
                'smsir' => 'smsir',
                'melipayamak' => 'melipayamak',
                'farapayamak' => 'melipayamak',
                'ippanel' => 'melipayamak',
            ];
            return $map[$val] ?? $val;
        }

        return (string) env('SMS_DEFAULT', 'smsir');
    }

    public function available(): array
    {
        return [
            'smsir'       => 'SMS.IR',
            'melipayamak' => 'ملی‌پیامک',
            'kavenegar'   => 'کاوه‌نگار',
        ];
    }

    /** میانبر ارسال با پنل پیش‌فرض */
    public function send(string $to, string $message): bool
    {
        try {
            return $this->driver()->send($to, $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ارسال پیامک ناموفق: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ارسال OTP با الگوی Verify در صورت وجود - بهترین روش برای کدهای ورود
     * بر اساس داکیومنت رسمی sms.ir: POST /v1/send/verify با mobile, templateId, parameters
     * این متد دارای اولویت بالا، ارسال به بلک‌لیست، و 24 ساعته است
     */
    public function sendOtp(string $to, string $code, ?string $templateId = null): bool
    {
        $driverName = $this->default();
        $driver = $this->driver($driverName);

        // تلاش برای یافتن templateId از تنظیمات اگر ورودی نداشت
        if (!$templateId) {
            $templateId = $this->resolveOtpTemplateId($driverName);
        }

        // اگر templateId داریم، ابتدا با متد Verify (یا lookup) ارسال کن - بر اساس داکیومنت
        if ($templateId) {
            try {
                // برای SMS.ir: پارامترها معمولاً CODE یا Code
                $params = [
                    'Code' => $code,
                    'code' => $code,
                    'CODE' => $code,
                    'VerificationCode' => $code,
                ];

                // تلاش با نام‌های مختلف پارامتر که در داکیومنت رایج هستند
                if (method_exists($driver, 'sendPattern')) {
                    // ابتدا با نام Code
                    $sent = $driver->sendPattern($to, $templateId, ['Code' => $code]);
                    if ($sent) return true;

                    // تلاش با نام code کوچک
                    $sent = $driver->sendPattern($to, $templateId, ['code' => $code]);
                    if ($sent) return true;

                    // تلاش با token و token2 برای کاوه‌نگار
                    $sent = $driver->sendPattern($to, $templateId, ['token' => $code]);
                    if ($sent) return true;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ارسال OTP با الگو ناموفق، تلاش با متن ساده: ' . $e->getMessage());
            }
        }

        // fallback به ارسال متن ساده
        $message = "کد ورود شما: {$code}";
        try {
            $customMessage = Setting::get('portal_otp_message', '');
            if ($customMessage) {
                $message = str_replace(['{code}', '{minutes}'], [$code, '5'], $customMessage);
            }
        } catch (\Throwable $e) {}

        return $this->send($to, $message);
    }

    private function resolveOtpTemplateId(string $driverName): ?string
    {
        $keysByDriver = [
            'smsir' => ['smsir_verify_template_id', 'sms_smsir_verify_template_id', 'smsir_template_id', 'sms_template_id', 'portal_otp_template_id', 'otp_template_id'],
            'kavenegar' => ['kavenegar_otp_template', 'sms_kavenegar_otp_template', 'kavenegar_template', 'kavenegar_verify_template', 'otp_template_id'],
            'melipayamak' => ['melipayamak_otp_body_id', 'sms_melipayamak_otp_body_id', 'melipayamak_template_id', 'otp_template_id'],
        ];

        $keys = $keysByDriver[$driverName] ?? ['otp_template_id', 'sms_template_id', 'portal_otp_template_id'];

        foreach ($keys as $k) {
            try {
                $v = (string) Setting::get($k, '');
                if ($v !== '' && $v !== '0') return $v;
            } catch (\Throwable $e) {}
        }

        // جستجوی عمومی
        foreach (['otp_template_id', 'sms_template_id', 'portal_otp_template_id', 'verify_template_id'] as $k) {
            try {
                $v = (string) Setting::get($k, '');
                if ($v !== '' && $v !== '0') return $v;
            } catch (\Throwable $e) {}
        }

        return null;
    }
}

