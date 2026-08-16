<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. ساخت جدول کسب‌وکارها
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام کسب‌وکار');
            $table->string('domain')->nullable()->unique()->comment('دامنه اختصاصی یا زیردامنه');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعالیت');
            $table->string('logo')->nullable()->comment('لوگوی کسب‌وکار');
            $table->json('settings')->nullable()->comment('تنظیمات اختصاصی کسب‌وکار');
            $table->timestamps();
        });

        // ۲. ساخت جدول اشتراک‌ها و لایسنس‌ها
        Schema::create('business_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('plan_name')->comment('نام پلن (مثلا پایه، پیشرفته، کامل)');
            $table->json('active_modules')->nullable()->comment('ماژول‌های فعال (چت‌بات، فروشگاه، باشگاه مشتریان)');
            $table->timestamp('starts_at')->nullable()->comment('تاریخ شروع اشتراک');
            $table->timestamp('ends_at')->nullable()->comment('تاریخ پایان اشتراک');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ۳. اضافه کردن شناسه کسب‌وکار به جدول کاربران
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('is_superadmin')->default(false)->after('role')->comment('آیا مدیر کل هلدینگ است؟');
        });

        // ۴. ایزوله کردن جداول مهم (اضافه کردن business_id به جداول موجود)
        $tablesToIsolate = [
            'customers', 
            'leads', 
            'products', 
            'orders',
            'loyalty_campaigns',
            'tickets',
            'knowledge_bases',
            'kb_chatbots',
            'segments',
            'workflows' // در صورت وجود
        ];

        foreach ($tablesToIsolate as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'business_id')) {
                        $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->cascadeOnDelete();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $tablesToIsolate = [
            'customers', 'leads', 'products', 'orders', 'loyalty_campaigns', 
            'tickets', 'knowledge_bases', 'kb_chatbots', 'segments', 'workflows'
        ];

        foreach ($tablesToIsolate as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'business_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['business_id']);
                    $table->dropColumn('business_id');
                });
            }
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropColumn(['business_id', 'is_superadmin']);
            });
        }

        Schema::dropIfExists('business_subscriptions');
        Schema::dropIfExists('businesses');
    }
};