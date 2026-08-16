<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\IranPack\Managers\SmsManager;
use Modules\Core\Entities\Setting;

class SmsTest extends Command
{
    protected $signature = 'sms:test {phone} {--message=} {--template=}';
    protected $description = 'تست ارسال پیامک - بررسی تنظیمات پنل پیامک و ارسال تست OTP';

    public function handle(): int
    {
        $phone = $this->argument('phone');
        $message = $this->option('message') ?: 'تست ارسال پیامک از مشتری‌یار - ' . now()->format('Y/m/d H:i');
        $template = $this->option('template');

        $this->info('شماره مقصد: ' . $phone);
        $this->info('پیام: ' . $message);
        if ($template) $this->info('قالب: ' . $template);

        $this->line('--- تنظیمات فعلی ---');
        $this->line('sms_provider: ' . Setting::get('sms_provider', '---'));
        $this->line('sms_default: ' . Setting::get('sms_default', '---'));
        $this->line('sms_sender: ' . Setting::get('sms_sender', '---'));
        $this->line('smsir_api_key: ' . (Setting::get('smsir_api_key') ? '***' . substr(Setting::get('smsir_api_key'), -4) : 'خالی'));
        $this->line('smsir_line_number: ' . Setting::get('smsir_line_number', 'خالی'));
        $this->line('smsir_verify_template_id: ' . Setting::get('smsir_verify_template_id', 'خالی'));
        $this->line('kavenegar_api_key: ' . (Setting::get('kavenegar_api_key') ? '***' . substr(Setting::get('kavenegar_api_key'), -4) : (Setting::get('sms_kavenegar_api_key') ? '*** با پیشوند sms_' : 'خالی')));
        $this->line('kavenegar_otp_template: ' . Setting::get('kavenegar_otp_template', Setting::get('sms_kavenegar_otp_template', 'خالی')));

        try {
            $manager = app(SmsManager::class);
            $this->line('پنل پیش‌فرض: ' . $manager->default());

            if ($template) {
                $this->info('در حال ارسال با قالب OTP...');
                $sent = $manager->sendOtp($phone, '123456', $template);
            } else {
                $this->info('در حال ارسال OTP تستی با کد 123456...');
                $sent = $manager->sendOtp($phone, '123456');
            }

            if ($sent) {
                $this->info('✅ ارسال موفق بود!');
                return 0;
            } else {
                $this->error('❌ ارسال ناموفق بود - لاگ را بررسی کنید: storage/logs/laravel.log');
                $this->line('نکات:');
                $this->line('۱. برای sms.ir حتماً قالب Verify بسازید و شناسه آن را در تنظیمات → قالب‌های OTP → شناسه قالب Verify وارد کنید');
                $this->line('۲. خط اختصاصی باید از نوع خدماتی باشد یا از متد Verify استفاده کنید تا به بلک‌لیست هم ارسال شود');
                $this->line('۳. API Key را دوباره چک کنید - بدون فاصله اضافه');
                return 1;
            }

        } catch (\Throwable $e) {
            $this->error('خطا: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}