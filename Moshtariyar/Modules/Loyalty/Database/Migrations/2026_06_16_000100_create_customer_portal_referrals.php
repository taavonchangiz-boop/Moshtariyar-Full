<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_member_id')->constrained('loyalty_members')->cascadeOnDelete();
            $table->foreignId('referred_member_id')->nullable()->constrained('loyalty_members')->nullOnDelete();
            $table->foreignId('referred_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('referral_code', 20)->index();
            $table->string('status', 30)->default('registered'); // registered / qualified / rewarded / cancelled
            $table->integer('reward_points')->default(0);
            $table->integer('reward_wallet')->default(0);
            $table->timestamp('first_purchase_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['referrer_member_id', 'status']);
            // $table->unique(['referred_member_id', 'level'], 'uniq_referred_member_level');
        });

        Schema::create('customer_portal_login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32)->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_login_codes');
        Schema::dropIfExists('loyalty_referrals');
    }
};
