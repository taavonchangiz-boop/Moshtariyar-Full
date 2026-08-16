<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wheel_prizes')) {
            try {
                DB::statement("ALTER TABLE wheel_prizes MODIFY prize_type ENUM('point','wallet','coupon','nothing') NOT NULL DEFAULT 'point'");
            } catch (\Throwable $exception) {
                report($exception);
            }

            Schema::table('wheel_prizes', function (Blueprint $table) {
                if (! Schema::hasColumn('wheel_prizes', 'delivery_channels')) {
                    $table->json('delivery_channels')->nullable()->after('image');
                }
                if (! Schema::hasColumn('wheel_prizes', 'delivery_message')) {
                    $table->text('delivery_message')->nullable()->after('delivery_channels');
                }
                if (! Schema::hasColumn('wheel_prizes', 'coupon_discount_type')) {
                    $table->string('coupon_discount_type', 20)->nullable()->after('delivery_message');
                }
                if (! Schema::hasColumn('wheel_prizes', 'coupon_discount_value')) {
                    $table->decimal('coupon_discount_value', 15, 2)->default(0)->after('coupon_discount_type');
                }
                if (! Schema::hasColumn('wheel_prizes', 'coupon_expires_days')) {
                    $table->unsignedInteger('coupon_expires_days')->default(7)->after('coupon_discount_value');
                }
            });
        }

        if (Schema::hasTable('wheel_spins')) {
            Schema::table('wheel_spins', function (Blueprint $table) {
                if (! Schema::hasColumn('wheel_spins', 'prize_type')) {
                    $table->string('prize_type', 30)->nullable()->after('prize_title');
                }
                if (! Schema::hasColumn('wheel_spins', 'amount')) {
                    $table->integer('amount')->default(0)->after('prize_type');
                }
                if (! Schema::hasColumn('wheel_spins', 'prize_image')) {
                    $table->string('prize_image')->nullable()->after('amount');
                }
                if (! Schema::hasColumn('wheel_spins', 'coupon_id')) {
                    $table->foreignId('coupon_id')->nullable()->after('prize_image')->constrained('loyalty_coupons')->nullOnDelete();
                }
                if (! Schema::hasColumn('wheel_spins', 'delivery_channels')) {
                    $table->json('delivery_channels')->nullable()->after('coupon_id');
                }
                if (! Schema::hasColumn('wheel_spins', 'delivery_status')) {
                    $table->string('delivery_status', 30)->default('pending')->after('delivery_channels');
                }
                if (! Schema::hasColumn('wheel_spins', 'delivery_report')) {
                    $table->json('delivery_report')->nullable()->after('delivery_status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wheel_spins')) {
            Schema::table('wheel_spins', function (Blueprint $table) {
                if (Schema::hasColumn('wheel_spins', 'coupon_id')) {
                    $table->dropConstrainedForeignId('coupon_id');
                }
                foreach (['delivery_report', 'delivery_status', 'delivery_channels', 'prize_image', 'amount', 'prize_type'] as $column) {
                    if (Schema::hasColumn('wheel_spins', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('wheel_prizes')) {
            Schema::table('wheel_prizes', function (Blueprint $table) {
                foreach (['coupon_expires_days', 'coupon_discount_value', 'coupon_discount_type', 'delivery_message', 'delivery_channels'] as $column) {
                    if (Schema::hasColumn('wheel_prizes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });

            try {
                DB::statement("ALTER TABLE wheel_prizes MODIFY prize_type ENUM('point','wallet','nothing') NOT NULL DEFAULT 'point'");
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }
};