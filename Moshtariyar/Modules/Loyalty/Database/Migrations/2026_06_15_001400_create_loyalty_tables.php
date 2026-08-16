<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // عضویت هر مشتری در باشگاه (خودکار ساخته می‌شود)
        Schema::create('loyalty_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->integer('points')->default(0);              // امتیاز فعلی
            $table->integer('points_lifetime')->default(0);     // مجموع امتیاز کسب‌شده (برای سطح)
            $table->decimal('wallet_balance', 15, 0)->default(0); // اعتبار ریالی کیف پول
            $table->foreignId('tier_id')->nullable();           // سطح فعلی
            $table->string('referral_code', 20)->nullable()->unique();
            $table->boolean('profile_completed')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });

        // سطوح عضویت
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);                 // برنزی/نقره‌ای/طلایی/الماسی
            $table->string('color', 20)->default('#888');
            $table->integer('min_points')->default(0);  // حداقل امتیاز لازم
            $table->decimal('cashback_rate', 5, 2)->default(0); // درصد کش‌بک این سطح
            $table->integer('order')->default(0);
        });

        // تراکنش‌های امتیاز و کیف پول
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('loyalty_members')->cascadeOnDelete();
            $table->enum('kind', ['point', 'wallet']);  // امتیاز یا اعتبار
            $table->enum('direction', ['credit', 'debit']); // واریز یا برداشت
            $table->integer('amount');
            $table->integer('balance_after')->nullable();
            $table->string('reason', 120);              // علت (خرید، ثبت‌نام، کش‌بک، ...)
            $table->string('ref_type', 30)->nullable(); // order / manual / signup ...
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('member_id');
        });

        // قوانین کسب امتیاز/کش‌بک
        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // event: signup / profile_complete / purchase / birthday / referral / review
            $table->string('event', 30);
            // نوع پاداش: points_fixed / points_percent / cashback_percent / cashback_fixed
            $table->string('reward_type', 30);
            $table->decimal('reward_value', 10, 2)->default(0);
            $table->decimal('max_reward', 15, 0)->nullable();   // سقف پاداش
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_rules');
        Schema::dropIfExists('loyalty_members');
        Schema::dropIfExists('loyalty_tiers');
    }
};
