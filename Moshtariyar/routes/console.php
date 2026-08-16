<?php

use Illuminate\Support\Facades\Schedule;

// هر روز ساعت ۸ صبح گزارش استراتژیک تولید شود
Schedule::command('strategic:generate-briefing')->dailyAt('08:00');

/*
| زمان‌بند مرکزی. روی cPanel با یک Cron زیر هر دقیقه اجرا می‌شود:
|   * * * * * cd /home/USER/crm && php artisan schedule:run >> /dev/null 2>&1
*/

// خواندن مداوم تغییرات ووکامرس و ورود به مشتری‌یار
Schedule::command('woobridge:pull --per-page=50 --minutes=30')->everyFiveMinutes()->withoutOverlapping();

// ری‌تری رکوردهای ناموفق همگام‌سازی
Schedule::command('woobridge:retry-failed')->everyTenMinutes()->withoutOverlapping();

// پیگیری سبدهای رهاشده - نسخه قدیمی برای سازگاری
Schedule::command('woobridge:recover-carts')->everyFiveMinutes()->withoutOverlapping();

// نسخه جدید و حرفه‌ای با اتصال به WorkflowEngine و ارسال از طریق تمام کانال‌ها
Schedule::command('automation:check-abandoned-carts')->everyThirtyMinutes()->withoutOverlapping();

// محاسبهٔ مجدد ارزش طول عمر مشتری (CLV)
Schedule::command('crm:recalc-clv')->hourly()->withoutOverlapping();

// استعلام وضعیت فاکتورهای رسمی از سامانه مودیان
Schedule::command('moadian:inquire')->everyFifteenMinutes()->withoutOverlapping();

// اجرای خودکار سفرهای زمانی - نسخه حرفه‌ای جدید با بالاترین دقت
Schedule::command('crm:run-time-journeys')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('crm:run-time-journeys')->dailyAt('20:00')->withoutOverlapping();

// تبریک تولد - هر روز صبح ساعت ۸:۳۰
Schedule::command('automation:check-birthdays')->dailyAt('08:30')->withoutOverlapping();

// بررسی تولدهای نزدیک - هر روز عصر ساعت ۱۷
Schedule::command('automation:check-birthdays')->dailyAt('17:00')->withoutOverlapping();

// بخش‌بندی خودکار بر اساس CLV پیشرفته با پیش‌بینی ریزش
Schedule::command('customers:auto-segment')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('customers:auto-segment')->dailyAt('14:00')->withoutOverlapping();

// گزارش هفتگی خودکار درآمد نجات یافته - هر شنبه ساعت ۹ صبح برای مدیر (قدیمی - برای سازگاری)
Schedule::command('recovery:weekly-report')->weeklyOn(6, '09:00')->withoutOverlapping();

// بررسی هوشمند زمان‌بندی قابل تنظیم توسط مدیر از تنظیمات - هر ساعت
Schedule::command('recovery:check-schedule')->hourly()->withoutOverlapping();
// همچنین هر ۳۰ دقیقه برای دقت بیشتر اگر هاست هر دقیقه کرون نداشته باشد
Schedule::command('recovery:check-schedule')->everyThirtyMinutes()->withoutOverlapping();
