<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * ساخت/به‌روزرسانی کاربر مدیر کل در زمان نصب.
 * استفاده توسط نصاب تحت مرورگر: php artisan install:admin "نام" ایمیل رمز
 */
class InstallAdmin extends Command
{
    protected $signature = 'install:admin {name} {email} {password}';
    protected $description = 'ساخت کاربر مدیر کل';

    public function handle(): int
    {
        User::updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name'      => $this->argument('name'),
                'password'  => Hash::make($this->argument('password')),
                'role'      => Roles::ADMIN,
                'is_active' => true,
            ]
        );
        $this->info('کاربر مدیر ساخته شد.');
        return self::SUCCESS;
    }
}
