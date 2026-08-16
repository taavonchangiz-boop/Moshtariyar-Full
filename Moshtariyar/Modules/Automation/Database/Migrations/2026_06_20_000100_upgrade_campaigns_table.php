<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'rfm_group')) {
                $table->string('rfm_group', 50)->nullable()->after('segment');
            }
            if (!Schema::hasColumn('campaigns', 'referral_slug')) {
                $table->string('referral_slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('campaigns', 'reward_rules')) {
                $table->json('reward_rules')->nullable()->after('status');
            }
            if (!Schema::hasColumn('campaigns', 'roi_revenue')) {
                $table->decimal('roi_revenue', 15, 2)->default(0)->after('sent_at');
            }
            if (!Schema::hasColumn('campaigns', 'clicks_count')) {
                $table->unsignedInteger('clicks_count')->default(0)->after('roi_revenue');
            }
            if (!Schema::hasColumn('campaigns', 'conversions_count')) {
                $table->unsignedInteger('conversions_count')->default(0)->after('clicks_count');
            }
            if (!Schema::hasColumn('campaigns', 'meta')) {
                $table->json('meta')->nullable()->after('conversions_count');
            }

            // ارتقای کانال برای پشتیبانی از پیام‌رسان‌ها اگر enum باشد
            // در دیتابیس‌های موجود، بهتر است ستون را به varchar تبدیل کنیم تا هر مقداری بپذیرد
        });

        // تغییر نوع ستون channel به string جهت سازگاری با واتس‌اپ، بله، ایتا و تلگرام
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE campaigns MODIFY channel VARCHAR(50) NOT NULL DEFAULT 'sms'");
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'meta')) $table->dropColumn('meta');
            if (Schema::hasColumn('campaigns', 'conversions_count')) $table->dropColumn('conversions_count');
            if (Schema::hasColumn('campaigns', 'clicks_count')) $table->dropColumn('clicks_count');
            if (Schema::hasColumn('campaigns', 'roi_revenue')) $table->dropColumn('roi_revenue');
            if (Schema::hasColumn('campaigns', 'reward_rules')) $table->dropColumn('reward_rules');
            if (Schema::hasColumn('campaigns', 'referral_slug')) $table->dropColumn('referral_slug');
            if (Schema::hasColumn('campaigns', 'rfm_group')) $table->dropColumn('rfm_group');
        });
    }
};
