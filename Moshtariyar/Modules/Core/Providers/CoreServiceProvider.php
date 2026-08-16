<?php

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Console\RecalcClv;
use Modules\Core\Support\Num;
use Modules\Core\Support\Money;
use Modules\Core\Support\Jalali;

class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // معرفی مسیر نماها (Views) به لاراول برای رفع خطای No hint path defined for [core]
        // این خط به لاراول می‌گوید که هر وقت core:: دید، به پوشه Resources/views در این ماژول نگاه کند
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'core');

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../Routes/api.php');

        // Blade directives برای فارسی‌سازی اعداد
        Blade::directive('fa', fn ($expr) => "<?php echo \\Modules\\Core\\Support\\Num::fa($expr); ?>");
        // مبلغ بر اساس واحد انتخابی (ریال/تومان) + ارقام فارسی
        Blade::directive('money', fn ($expr) => "<?php echo \\Modules\\Core\\Support\\Money::show($expr); ?>");
        // برچسب واحد پول
        Blade::directive('unit', fn () => "<?php echo \\Modules\\Core\\Support\\Money::unitLabel(); ?>");
        // Blade directives برای تاریخ شمسی
        Blade::directive('jdate', fn ($expr) => "<?php echo \\Modules\\Core\\Support\\Jalali::date($expr); ?>");
        Blade::directive('jdatetime', fn ($expr) => "<?php echo \\Modules\\Core\\Support\\Jalali::datetime($expr); ?>");

        if ($this->app->runningInConsole()) {
            $this->commands([RecalcClv::class]);
        }
    }
}