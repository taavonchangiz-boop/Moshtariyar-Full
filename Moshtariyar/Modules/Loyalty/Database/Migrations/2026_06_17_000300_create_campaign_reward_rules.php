<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_campaign_reward_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('loyalty_campaigns')->cascadeOnDelete();
            $table->string('event', 40)->default('referral_registered');
            $table->string('beneficiary', 30)->default('referrer');
            $table->string('reward_type', 30)->default('points_fixed');
            $table->decimal('reward_value', 15, 2)->default(0);
            $table->string('release_policy', 40)->default('immediate');
            $table->unsignedInteger('release_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['campaign_id', 'event', 'is_active']);
        });

        Schema::create('loyalty_campaign_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('loyalty_campaigns')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('loyalty_campaign_reward_rules')->nullOnDelete();
            $table->foreignId('member_id')->constrained('loyalty_members')->cascadeOnDelete();
            $table->foreignId('referral_id')->nullable()->constrained('loyalty_referrals')->nullOnDelete();
            $table->string('reward_type', 30);
            $table->integer('amount')->default(0);
            $table->enum('status', ['pending', 'released', 'frozen', 'cancelled'])->default('pending');
            $table->timestamp('release_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['member_id', 'status']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_campaign_rewards');
        Schema::dropIfExists('loyalty_campaign_reward_rules');
    }
};
