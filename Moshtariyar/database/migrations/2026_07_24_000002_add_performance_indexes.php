<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * اجرای مهاجرت: افزودن ایندکس‌های ترکیبی برای افزایش سرعت
     *
     * این ایندکس‌ها مهم‌ترین کوئری‌های سامانه را هدف می‌گیرند:
     * - پروندهٔ ۳۶۰ درجهٔ مشتری (join سفارش‌ها و اقلام)
     * - داشبورد (فروش امروز، این ماه، وضعیت سفارش‌ها)
     * - جستجوی مشتری (نام، ایمیل، تلفن)
     * - تحلیل RFM (تاریخ + وضعیت سفارش)
     * - بخش‌بندی مشتریان (SegmentEvaluator)
     */
    public function up(): void
    {
        // ═══════════════════ ۱. جدول سفارش‌ها ═══════════════════

        if (Schema::hasTable('orders')) {

            // ایندکس ترکیبی برای داشبورد: وضعیت + تاریخ (پرتکرارترین کوئری)
            if (!$this->hasIndex('orders', 'idx_orders_status_placed')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index(['status', 'placed_at'], 'idx_orders_status_placed');
                });
            }

            // ایندکس ترکیبی برای پرونده مشتری: مشتری + تاریخ
            if (!$this->hasIndex('orders', 'idx_orders_customer_placed')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index(['customer_id', 'placed_at'], 'idx_orders_customer_placed');
                });
            }

            // ایندکس ترکیبی برای محاسبهٔ CLV: مشتری + وضعیت
            if (!$this->hasIndex('orders', 'idx_orders_customer_status')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index(['customer_id', 'status'], 'idx_orders_customer_status');
                });
            }

            // ایندکس برای شمارهٔ سفارش (جستجو)
            if (!$this->hasIndex('orders', 'idx_orders_number')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index('number', 'idx_orders_number');
                });
            }

            // ایندکس برای تاریخ (گزارش‌های بازه‌ای)
            if (!$this->hasIndex('orders', 'idx_orders_placed_at')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index('placed_at', 'idx_orders_placed_at');
                });
            }
        }

        // ═══════════════════ ۲. جدول اقلام سفارش ═══════════════════

        if (Schema::hasTable('order_items')) {

            // ایندکس برای محصولات مورد علاقهٔ مشتری (Customer360)
            if (!$this->hasIndex('order_items', 'idx_orderitems_order_product')) {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->index(['order_id', 'product_id'], 'idx_orderitems_order_product');
                });
            }
        }

        // ═══════════════════ ۳. جدول مشتریان ═══════════════════

        if (Schema::hasTable('customers')) {

            // ایندکس ترکیبی برای جستجوی سریع (full_name + phone)
            if (!$this->hasIndex('customers', 'idx_customers_name_phone')) {
                Schema::table('customers', function (Blueprint $table) {
                    $table->index(['full_name', 'phone'], 'idx_customers_name_phone');
                });
            }

            // ایندکس برای تاریخ عضویت (گزارش‌ها)
            if (!$this->hasIndex('customers', 'idx_customers_created')) {
                Schema::table('customers', function (Blueprint $table) {
                    $table->index('created_at', 'idx_customers_created');
                });
            }
        }

        // ═══════════════════ ۴. جدول تیکت‌ها ═══════════════════

        if (Schema::hasTable('tickets')) {

            // ایندکس برای داشبورد: وضعیت (باز/بسته)
            if (!$this->hasIndex('tickets', 'idx_tickets_status')) {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index('status', 'idx_tickets_status');
                });
            }

            // ایندکس ترکیبی برای تیکت‌های باز هر مشتری
            if (Schema::hasColumn('tickets', 'customer_id') &&
                !$this->hasIndex('tickets', 'idx_tickets_customer_status')) {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index(['customer_id', 'status'], 'idx_tickets_customer_status');
                });
            }
        }

        // ═══════════════════ ۵. جدول فعالیت‌ها ═══════════════════

        if (Schema::hasTable('activities')) {

            // ایندکس برای وظایف باز (داشبورد)
            if (Schema::hasColumn('activities', 'type') &&
                Schema::hasColumn('activities', 'done') &&
                !$this->hasIndex('activities', 'idx_activities_type_done')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->index(['type', 'done'], 'idx_activities_type_done');
                });
            }

            // ایندکس ترکیبی برای فعالیت‌های هر مشتری
            if (Schema::hasColumn('activities', 'subject_type') &&
                Schema::hasColumn('activities', 'subject_id') &&
                !$this->hasIndex('activities', 'idx_activities_subject')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->index(['subject_type', 'subject_id'], 'idx_activities_subject');
                });
            }
        }

        // ═══════════════════ ۶. جدول لاگ همگام‌سازی ═══════════════════

        if (Schema::hasTable('sync_logs')) {

            if (Schema::hasColumn('sync_logs', 'status') &&
                !$this->hasIndex('sync_logs', 'idx_synclogs_status')) {
                Schema::table('sync_logs', function (Blueprint $table) {
                    $table->index('status', 'idx_synclogs_status');
                });
            }

            if (Schema::hasColumn('sync_logs', 'created_at') &&
                !$this->hasIndex('sync_logs', 'idx_synclogs_created')) {
                Schema::table('sync_logs', function (Blueprint $table) {
                    $table->index('created_at', 'idx_synclogs_created');
                });
            }
        }

        // ═══════════════════ ۷. جدول اعضای باشگاه ═══════════════════

        if (Schema::hasTable('loyalty_members')) {

            if (Schema::hasColumn('loyalty_members', 'customer_id') &&
                !$this->hasIndex('loyalty_members', 'idx_loyalty_customer')) {
                Schema::table('loyalty_members', function (Blueprint $table) {
                    $table->index('customer_id', 'idx_loyalty_customer');
                });
            }
        }
    }

    /**
     * بازگشت مهاجرت: حذف ایندکس‌های اضافه‌شده
     */
    public function down(): void
    {
        $this->dropIndexIfExists('orders', 'idx_orders_status_placed');
        $this->dropIndexIfExists('orders', 'idx_orders_customer_placed');
        $this->dropIndexIfExists('orders', 'idx_orders_customer_status');
        $this->dropIndexIfExists('orders', 'idx_orders_number');
        $this->dropIndexIfExists('orders', 'idx_orders_placed_at');
        $this->dropIndexIfExists('order_items', 'idx_orderitems_order_product');
        $this->dropIndexIfExists('customers', 'idx_customers_name_phone');
        $this->dropIndexIfExists('customers', 'idx_customers_created');
        $this->dropIndexIfExists('tickets', 'idx_tickets_status');
        $this->dropIndexIfExists('tickets', 'idx_tickets_customer_status');
        $this->dropIndexIfExists('activities', 'idx_activities_type_done');
        $this->dropIndexIfExists('activities', 'idx_activities_subject');
        $this->dropIndexIfExists('sync_logs', 'idx_synclogs_status');
        $this->dropIndexIfExists('sync_logs', 'idx_synclogs_created');
        $this->dropIndexIfExists('loyalty_members', 'idx_loyalty_customer');
    }

    /**
     * بررسی وجود یک ایندکس روی جدول
     */
    private function hasIndex(string $table, string $index): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $idx) {
                if ($idx['name'] === $index) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // اگر جدول وجود نداشت، false برمی‌گردانیم
        }
        return false;
    }

    /**
     * حذف ایندکس در صورت وجود
     */
    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            if (Schema::hasTable($table) && $this->hasIndex($table, $index)) {
                Schema::table($table, function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        } catch (\Throwable $e) {
            // بی‌صدا رد شو
        }
    }
};