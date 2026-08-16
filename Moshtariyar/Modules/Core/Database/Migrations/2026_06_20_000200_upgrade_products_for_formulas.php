<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_formula_based')) {
                $table->boolean('is_formula_based')->default(false)->after('price');
            }
            if (!Schema::hasColumn('products', 'formula_type')) {
                $table->string('formula_type', 50)->default('standard')->after('is_formula_based'); // standard / standard_math / gold_jewelry
            }
            if (!Schema::hasColumn('products', 'formula')) {
                $table->text('formula')->nullable()->after('formula_type');
            }
            if (!Schema::hasColumn('products', 'formula_variables')) {
                $table->json('formula_variables')->nullable()->after('formula');
            }
            if (!Schema::hasColumn('products', 'live_gold_weight')) {
                $table->decimal('live_gold_weight', 8, 3)->nullable()->after('formula_variables'); // وزن طلا به گرم
            }
            if (!Schema::hasColumn('products', 'live_gold_ajrat')) {
                $table->decimal('live_gold_ajrat', 15, 2)->nullable()->after('live_gold_weight'); // اجرت ساخت
            }
            if (!Schema::hasColumn('products', 'live_gold_profit')) {
                $table->decimal('live_gold_profit', 5, 2)->default(0.07)->after('live_gold_ajrat'); // سود فروشگاه معمولاً ۷٪
            }
            if (!Schema::hasColumn('products', 'meta')) {
                $table->json('meta')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'meta')) $table->dropColumn('meta');
            if (Schema::hasColumn('products', 'live_gold_profit')) $table->dropColumn('live_gold_profit');
            if (Schema::hasColumn('products', 'live_gold_ajrat')) $table->dropColumn('live_gold_ajrat');
            if (Schema::hasColumn('products', 'live_gold_weight')) $table->dropColumn('live_gold_weight');
            if (Schema::hasColumn('products', 'formula_variables')) $table->dropColumn('formula_variables');
            if (Schema::hasColumn('products', 'formula')) $table->dropColumn('formula');
            if (Schema::hasColumn('products', 'formula_type')) $table->dropColumn('formula_type');
            if (Schema::hasColumn('products', 'is_formula_based')) $table->dropColumn('is_formula_based');
        });
    }
};
