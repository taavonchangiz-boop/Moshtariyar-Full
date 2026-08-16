<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // مدیر کل
        User::updateOrCreate(
            ['email' => 'admin@hoomanweb.ir'],
            ['name' => 'مدیر سیستم', 'password' => Hash::make('password'),
             'role' => \App\Support\Roles::ADMIN, 'is_active' => true]
        );
        // نمونه کارشناس فروش و پشتیبانی
        User::updateOrCreate(
            ['email' => 'sales@hoomanweb.ir'],
            ['name' => 'کارشناس فروش', 'password' => Hash::make('password'),
             'role' => \App\Support\Roles::SALES, 'is_active' => true]
        );
        User::updateOrCreate(
            ['email' => 'support@hoomanweb.ir'],
            ['name' => 'کارشناس پشتیبانی', 'password' => Hash::make('password'),
             'role' => \App\Support\Roles::SUPPORT, 'is_active' => true]
        );

        $statuses = ['جدید' => '#3b82f6', 'در حال پیگیری' => '#f59e0b', 'برنده' => '#10b981', 'بازنده' => '#ef4444'];
        $i = 0;
        foreach ($statuses as $name => $color) {
            DB::table('lead_statuses')->updateOrInsert(
                ['name' => $name],
                ['color' => $color, 'order' => $i++]
            );
        }

        // سطوح باشگاه مشتریان
        if (Schema::hasTable('loyalty_tiers')) {
            $tiers = [
                ['برنزی', '#cd7f32', 0, 1, 0],
                ['نقره‌ای', '#c0c0c0', 1000, 2, 1],
                ['طلایی', '#f59e0b', 5000, 3, 2],
                ['الماسی', '#38bdf8', 15000, 5, 3],
            ];
            foreach ($tiers as [$name, $color, $min, $cb, $ord]) {
                DB::table('loyalty_tiers')->updateOrInsert(
                    ['name' => $name],
                    ['color' => $color, 'min_points' => $min, 'cashback_rate' => $cb, 'order' => $ord]
                );
            }
        }

        // واحد پول پیش‌فرض: تومان
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(['key' => 'currency_unit'], ['group' => 'currency', 'value' => 'toman', 'is_secret' => 0, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('settings')->updateOrInsert(['key' => 'currency_divisor'], ['group' => 'currency', 'value' => '10', 'is_secret' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }

        // جوایز پیش‌فرض گردونهٔ شانس
        if (Schema::hasTable('wheel_prizes')) {
            $prizes = [
                ['۱۰۰ امتیاز', 'point', 100, 25, '#10b981'],
                ['۵۰ امتیاز', 'point', 50, 30, '#38bdf8'],
                ['۲۰٬۰۰۰ ریال اعتبار', 'wallet', 20000, 15, '#f59e0b'],
                ['۲۰۰ امتیاز', 'point', 200, 10, '#a78bfa'],
                ['پوچ', 'nothing', 0, 20, '#94a3b8'],
            ];
            foreach ($prizes as [$t, $ty, $am, $ch, $co]) {
                DB::table('wheel_prizes')->updateOrInsert(['title' => $t],
                    ['prize_type' => $ty, 'amount' => $am, 'chance' => $ch, 'color' => $co, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // قوانین پاداش باشگاه
        if (Schema::hasTable('loyalty_rules')) {
            $rules = [
                ['امتیاز ثبت‌نام', 'signup', 'points_fixed', 100, null],
                ['امتیاز تکمیل پروفایل', 'profile_complete', 'points_fixed', 200, null],
                ['کش‌بک خرید', 'purchase', 'cashback_percent', 3, 500000],
                ['امتیاز خرید', 'purchase', 'points_percent', 5, null],
                ['پاداش معرفی دوستان', 'referral', 'points_fixed', 300, null],
            ];
            foreach ($rules as [$name, $event, $type, $val, $max]) {
                DB::table('loyalty_rules')->updateOrInsert(
                    ['name' => $name],
                    ['event' => $event, 'reward_type' => $type, 'reward_value' => $val, 'max_reward' => $max, 'is_active' => 1,
                     'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
