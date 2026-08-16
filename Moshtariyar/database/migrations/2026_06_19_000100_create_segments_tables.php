<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول اصلی سگمنت‌ها
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // نام سگمنت (مثلاً: «تهرانی‌های پولدار»)
            $table->string('type')->default('dynamic');       // dynamic یا list
            $table->string('logic')->default('and');          // and یا or
            $table->text('description')->nullable();          // توضیحات
            $table->boolean('is_active')->default(true);      // فعال/غیرفعال
            $table->integer('members_count')->default(0);     // تعداد اعضا (کش شده)
            $table->timestamp('evaluated_at')->nullable();    // آخرین زمان ارزیابی
            $table->timestamps();
        });

        // شرط‌های هر سگمنت
        Schema::create('segment_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('segments')->cascadeOnDelete();
            $table->string('field');       // فیلد: total_spent, orders_count, last_order, created_at, rfm_group, city
            $table->string('operator');    // عملگر: >=, <=, =, >, <, between, in, days_ago, days_ahead
            $table->string('value');       // مقدار: 5000000 یا تهران
            $table->string('value2')->nullable(); // مقدار دوم (برای between)
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // جدول ارتباطی: کدام مشتری‌ها عضو کدام سگمنت هستند
        Schema::create('segment_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('segments')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['segment_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segment_member');
        Schema::dropIfExists('segment_conditions');
        Schema::dropIfExists('segments');
    }
};
