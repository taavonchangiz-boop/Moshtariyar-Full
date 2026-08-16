<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // طول پیش‌فرض رشته برای سازگاری با MySQL/utf8mb4 روی هاست‌های قدیمی‌تر
        Schema::defaultStringLength(191);

        // اعمال تنظیمات ذخیره‌شده در دیتابیس روی config برند (در صورت وجود جدول)
        $this->applyDbSettings();
    }

    private function applyDbSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
            $map = [
                'brand_name'          => 'brand.name',
                'brand_tagline'       => 'brand.tagline',
                'brand_owner'         => 'brand.owner',
                'brand_url'           => 'brand.url',
                'brand_support_email' => 'brand.support',
                'support_phone'       => 'brand.phone',
            ];
            foreach ($map as $settingKey => $configKey) {
                $val = \Modules\Core\Entities\Setting::get($settingKey);
                if ($val !== null && $val !== '') {
                    config([$configKey => $val]);
                    if ($configKey === 'brand.name') {
                        config(['app.name' => $val]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // در زمان نصب/migration هنوز جدول نیست — بی‌خطر عبور می‌کنیم
        }
    }
}
