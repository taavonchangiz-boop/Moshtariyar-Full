<?php

namespace App\Support;

use App\Models\CrmRole;
use Illuminate\Support\Facades\Schema;

/**
 * نقش‌ها و مجوزهای پلتفرم هوش مصنوعی مدیریت کسب‌وکار.
 */
class Roles
{
    public const ADMIN   = 'admin';
    public const SALES   = 'sales';
    public const SUPPORT = 'support';

    public static function catalog(): array
    {
        return [
            'عمومی کسب‌وکار' => [
                'dashboard.view' => 'مشاهده داشبورد کسب‌وکار',
                'settings.manage' => 'مدیریت تنظیمات کسب‌وکار',
                'users.manage' => 'مدیریت اپراتورها و نقش‌ها',
            ],
            'مشتریان و فروش' => [
                'customers.view' => 'مشاهده مشتریان و پایگاه داده',
                'customers.export' => 'خروجی اطلاعات مشتریان',
                'leads.view' => 'مشاهده قیف فروش',
                'leads.manage' => 'مدیریت قیف فروش',
                'orders.view' => 'مشاهده سفارش‌ها',
                'orders.manage' => 'مدیریت سفارش‌ها',
                'orders.invoice' => 'صدور فاکتور سفارش',
            ],
            'گیمیفیکیشن و کمپین‌ها' => [
                'loyalty.view' => 'مشاهده جدول پاداش و ماموریت‌ها',
                'loyalty.manage' => 'مدیریت کمپین‌ها، لینک‌های رفرال و کدهای تخفیف',
            ],
            'پشتیبانی و هسته هوش مصنوعی' => [
                'tickets.view' => 'مشاهده گفتگوها و تیکت‌ها',
                'tickets.manage' => 'مدیریت و پاسخ به کاربران',
                'kb.view' => 'مشاهده پایگاه دانش هوش مصنوعی',
                'kb.manage' => 'آموزش هوش مصنوعی (آپلود فایل و چت مستقیم)',
            ],
            'مالی و فروشگاه' => [
                'woocommerce.view' => 'مشاهده وضعیت فروشگاه',
                'woocommerce.manage' => 'مدیریت فرمول‌های قیمت‌گذاری و محصولات',
                'payments.view' => 'مشاهده پرداخت‌ها',
                'tax.view' => 'مشاهده فاکتورهای رسمی',
                'tax.manage' => 'مدیریت فاکتورهای رسمی',
            ],
            'اتوماسیون هوشمند' => [
                'automation.view' => 'مشاهده سفرهای مشتری',
                'automation.manage' => 'مدیریت قوانین و اتوماسیون هوشمند',
            ],
        ];
    }

    public static function flatCatalog(): array
    {
        return collect(self::catalog())->flatMap(fn($items) => $items)->all();
    }

    public static function labels(): array
    {
        if (self::dbReady()) {
            return CrmRole::where('is_active', true)->orderBy('id')->pluck('label', 'key')->all();
        }
        return [self::ADMIN=>'مدیر کسب‌وکار', self::SALES=>'کارشناس فروش', self::SUPPORT=>'اپراتور پاسخگو'];
    }

    public static function permissions(): array
    {
        if (self::dbReady()) {
            $out = [];
            $roles = CrmRole::with('permissions')->where('is_active', true)->get();
            foreach ($roles as $role) $out[$role->key] = $role->permissions->pluck('permission')->all();
            return $out;
        }
        return [
            self::ADMIN => ['*'],
            self::SALES => ['dashboard.view','customers.view','customers.export','orders.view','orders.manage','orders.invoice','leads.view','leads.manage','woocommerce.view','tax.view'],
            self::SUPPORT => ['dashboard.view','customers.view','orders.view','tickets.view','tickets.manage'],
        ];
    }

    public static function can(string $role, string $permission): bool
    {
        $perms = static::permissions()[$role] ?? [];
        if (in_array('*', $perms, true)) return true;
        if (in_array($permission, $perms, true)) return true;

        // نگاشت سازگاری: مجوزهای جدید مدیریتی با مجوزهای قدیمی
        $aliases = [
            'settings.manage' => ['users.manage','loyalty.manage','kb.manage','woocommerce.manage','automation.manage'],
            'customers.view' => ['loyalty.view'],
            'tickets.view' => ['kb.view'],
            'tickets.manage' => ['kb.manage'],
            'woocommerce.view' => ['payments.view'],
            'orders.invoice' => ['tax.manage'],
            'dashboard.view' => ['automation.view'],
        ];
        foreach ($aliases as $base => $also) {
            if (in_array($base, $perms, true) && in_array($permission, $also, true)) return true;
        }
        return false;
    }

    public static function all(): array
    {
        return array_keys(static::labels());
    }

    private static function dbReady(): bool
    {
        try { return Schema::hasTable('crm_roles') && Schema::hasTable('crm_role_permissions'); }
        catch (\Throwable $e) { return false; }
    }
}