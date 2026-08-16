<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * اجرای مهاجرت: افزودن فیلد شناسهٔ کسب‌وکار به جدول سرنخ‌ها
     */
    public function up(): void
    {
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'business_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreignId('business_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('businesses')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * بازگشت مهاجرت: حذف شناسهٔ کسب‌وکار از جدول سرنخ‌ها
     */
    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'business_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropColumn('business_id');
            });
        }
    }
};