<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_portal_login_codes', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_portal_login_codes', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('used_at');
            }
        });

        try {
            Schema::table('loyalty_referrals', function (Blueprint $table) {
                $table->dropUnique('loyalty_referrals_referred_member_id_unique');
            });
        } catch (\Throwable $e) {
            // نصب تازه این ایندکس را ندارد.
        }

        Schema::table('loyalty_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('loyalty_referrals', 'level')) {
                $table->unsignedTinyInteger('level')->default(1)->after('referral_code');
            }
            try { $table->unique(['referred_member_id', 'level'], 'uniq_referred_member_level'); } catch (\Throwable $e) {}
            if (! Schema::hasColumn('loyalty_referrals', 'root_referrer_member_id')) {
                $table->foreignId('root_referrer_member_id')->nullable()->after('referrer_member_id')->constrained('loyalty_members')->nullOnDelete();
            }
        });

        Schema::create('customer_portal_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('loyalty_members')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_portal_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('type', 40)->default('info');
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'read_at']);
        });

        Schema::create('loyalty_missions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('event', 50); // profile_complete / first_purchase / referrals_count / orders_count
            $table->unsignedInteger('target')->default(1);
            $table->string('reward_type', 30)->default('points_fixed');
            $table->decimal('reward_value', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['event', 'is_active']);
        });

        Schema::create('loyalty_mission_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('loyalty_missions')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('loyalty_members')->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['mission_id', 'member_id'], 'uniq_mission_member');
        });

        Schema::create('loyalty_badges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('icon', 20)->default('🏅');
            $table->string('color', 20)->default('#38bdf8');
            $table->text('description')->nullable();
            $table->string('condition_type', 50)->default('manual'); // manual / points / referrals / orders
            $table->unsignedInteger('condition_value')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('loyalty_member_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('loyalty_badges')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('loyalty_members')->cascadeOnDelete();
            $table->timestamp('awarded_at');
            $table->timestamps();
            $table->unique(['badge_id', 'member_id'], 'uniq_badge_member');
        });

        Schema::create('loyalty_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('loyalty_members')->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('title');
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('min_order_total', 15, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->string('source', 50)->nullable();
            $table->timestamps();
            $table->index(['member_id', 'used_at']);
        });

        \Illuminate\Support\Facades\DB::table('loyalty_missions')->insert([
            ['title'=>'تکمیل پروفایل','description'=>'پروفایل خود را کامل کنید و پاداش بگیرید.','event'=>'profile_complete','target'=>1,'reward_type'=>'points_fixed','reward_value'=>30,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'معرفی ۳ دوست','description'=>'۳ نفر را به باشگاه دعوت کنید.','event'=>'referrals_count','target'=>3,'reward_type'=>'points_fixed','reward_value'=>100,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
        \Illuminate\Support\Facades\DB::table('loyalty_badges')->insert([
            ['title'=>'مشتری وفادار','icon'=>'💎','color'=>'#38bdf8','description'=>'کسب ۱۰۰۰ امتیاز کل','condition_type'=>'points','condition_value'=>1000,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'معرف برتر','icon'=>'🤝','color'=>'#10b981','description'=>'معرفی ۱۰ نفر','condition_type'=>'referrals','condition_value'=>10,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'خریدار طلایی','icon'=>'🏆','color'=>'#f59e0b','description'=>'ثبت ۵ سفارش','condition_type'=>'orders','condition_value'=>5,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_coupons');
        Schema::dropIfExists('loyalty_member_badges');
        Schema::dropIfExists('loyalty_badges');
        Schema::dropIfExists('loyalty_mission_completions');
        Schema::dropIfExists('loyalty_missions');
        Schema::dropIfExists('customer_portal_notifications');
        Schema::dropIfExists('customer_portal_tokens');

        Schema::table('loyalty_referrals', function (Blueprint $table) {
            try { $table->dropUnique('uniq_referred_member_level'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('loyalty_referrals', 'root_referrer_member_id')) {
                $table->dropConstrainedForeignId('root_referrer_member_id');
            }
            if (Schema::hasColumn('loyalty_referrals', 'level')) {
                $table->dropColumn('level');
            }
        });

        Schema::table('customer_portal_login_codes', function (Blueprint $table) {
            if (Schema::hasColumn('customer_portal_login_codes', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
};
