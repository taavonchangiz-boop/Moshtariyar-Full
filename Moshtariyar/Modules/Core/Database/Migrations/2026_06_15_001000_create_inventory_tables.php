<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تکمیل جدول محصولات با فیلدهای انبار
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'cost_price')) $table->decimal('cost_price', 15, 2)->default(0)->after('price');
            if (! Schema::hasColumn('products', 'min_stock'))  $table->integer('min_stock')->default(0)->after('stock');
            if (! Schema::hasColumn('products', 'category'))   $table->string('category', 80)->nullable()->after('name');
            if (! Schema::hasColumn('products', 'is_active'))  $table->boolean('is_active')->default(true);
        });

        // حرکات انبار (ورود/خروج/اصلاح)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'adjust']);
            $table->integer('qty');                 // مقدار تغییر (می‌تواند منفی برای adjust)
            $table->integer('balance_after')->nullable();
            $table->string('reason', 120)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'min_stock', 'category', 'is_active']);
        });
    }
};
