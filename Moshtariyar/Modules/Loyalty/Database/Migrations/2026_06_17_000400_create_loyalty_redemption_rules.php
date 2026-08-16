<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_redemption_rules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('points_required');
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('min_order_total', 15, 2)->default(0);
            $table->unsignedInteger('expires_days')->default(30);
            $table->unsignedInteger('usage_limit')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'points_required']);
        });

        Schema::table('loyalty_coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('loyalty_coupons', 'redemption_rule_id')) {
                $table->foreignId('redemption_rule_id')->nullable()->after('member_id')->constrained('loyalty_redemption_rules')->nullOnDelete();
            }
            if (! Schema::hasColumn('loyalty_coupons', 'points_spent')) {
                $table->unsignedInteger('points_spent')->default(0)->after('source');
            }
            if (! Schema::hasColumn('loyalty_coupons', 'usage_limit')) {
                $table->unsignedInteger('usage_limit')->default(1)->after('used_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_coupons', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_coupons', 'redemption_rule_id')) $table->dropConstrainedForeignId('redemption_rule_id');
            if (Schema::hasColumn('loyalty_coupons', 'points_spent')) $table->dropColumn('points_spent');
            if (Schema::hasColumn('loyalty_coupons', 'usage_limit')) $table->dropColumn('usage_limit');
        });
        Schema::dropIfExists('loyalty_redemption_rules');
    }
};
