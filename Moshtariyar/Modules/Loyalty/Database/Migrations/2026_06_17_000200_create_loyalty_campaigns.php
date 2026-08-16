<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['referral', 'purchase', 'profile', 'mission', 'seasonal'])->default('referral');
            $table->enum('status', ['draft', 'active', 'paused', 'expired'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('loyalty_campaign_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('loyalty_campaigns')->cascadeOnDelete();
            $table->string('channel', 40); // direct / whatsapp / telegram / instagram / eitaa / bale / sms
            $table->string('label', 80);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['campaign_id', 'channel'], 'uniq_campaign_channel');
        });

        Schema::create('loyalty_campaign_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('loyalty_campaigns')->cascadeOnDelete();
            $table->string('channel', 40)->default('direct');
            $table->foreignId('referrer_member_id')->nullable()->constrained('loyalty_members')->nullOnDelete();
            $table->string('referral_code', 20)->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
            $table->index(['campaign_id', 'channel']);
        });

        Schema::table('loyalty_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('loyalty_referrals', 'campaign_id')) {
                $table->foreignId('campaign_id')->nullable()->after('referral_code')->constrained('loyalty_campaigns')->nullOnDelete();
            }
            if (! Schema::hasColumn('loyalty_referrals', 'campaign_channel')) {
                $table->string('campaign_channel', 40)->nullable()->after('campaign_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_referrals', 'campaign_id')) {
                $table->dropConstrainedForeignId('campaign_id');
            }
            if (Schema::hasColumn('loyalty_referrals', 'campaign_channel')) {
                $table->dropColumn('campaign_channel');
            }
        });
        Schema::dropIfExists('loyalty_campaign_clicks');
        Schema::dropIfExists('loyalty_campaign_channels');
        Schema::dropIfExists('loyalty_campaigns');
    }
};
