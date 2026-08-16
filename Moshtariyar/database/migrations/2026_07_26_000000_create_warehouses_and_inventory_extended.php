<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
                $table->string('name', 120)->comment('نام انبار مثلا انبار مرکزی، شعبه تهران');
                $table->string('code', 40)->nullable()->comment('کد کوتاه انبار');
                $table->string('location', 191)->nullable()->comment('آدرس یا موقعیت');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
                $table->string('name', 191);
                $table->string('phone', 30)->nullable();
                $table->string('email', 191)->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('warehouse_product')) {
            Schema::create('warehouse_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('stock')->default(0);
                $table->integer('min_stock')->default(0);
                $table->decimal('avg_cost', 15, 2)->default(0);
                $table->timestamps();
                $table->unique(['warehouse_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('write_off_reasons')) {
            Schema::create('write_off_reasons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
                $table->string('title', 120);
                $table->string('color', 20)->default('#ef4444');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('supplies')) {
            Schema::create('supplies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('number', 60)->nullable();
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->text('note')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('supply_items')) {
            Schema::create('supply_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('qty');
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('warehouse_transfers')) {
            Schema::create('warehouse_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('number', 60)->nullable();
                $table->enum('status', ['draft', 'sent', 'received', 'cancelled'])->default('draft');
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('warehouse_transfer_items')) {
            Schema::create('warehouse_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id')->constrained('warehouse_transfers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('qty');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('write_offs')) {
            Schema::create('write_offs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('reason_id')->nullable()->constrained('write_off_reasons')->nullOnDelete();
                $table->string('number', 60)->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('write_off_items')) {
            Schema::create('write_off_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('write_off_id')->constrained('write_offs')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('qty');
                $table->timestamps();
            });
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'warehouse_id')) $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained('warehouses')->nullOnDelete();
            if (!Schema::hasColumn('stock_movements', 'supply_id')) $table->foreignId('supply_id')->nullable()->after('warehouse_id')->constrained('supplies')->nullOnDelete();
            if (!Schema::hasColumn('stock_movements', 'transfer_id')) $table->foreignId('transfer_id')->nullable()->after('supply_id')->constrained('warehouse_transfers')->nullOnDelete();
            if (!Schema::hasColumn('stock_movements', 'write_off_id')) $table->foreignId('write_off_id')->nullable()->after('transfer_id')->constrained('write_offs')->nullOnDelete();
            if (!Schema::hasColumn('stock_movements', 'business_id')) $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']); $table->dropForeign(['supply_id']);
            $table->dropForeign(['transfer_id']); $table->dropForeign(['write_off_id']);
            $table->dropForeign(['business_id']);
            $table->dropColumn(['warehouse_id', 'supply_id', 'transfer_id', 'write_off_id', 'business_id']);
        });
        Schema::dropIfExists('write_off_items'); Schema::dropIfExists('write_offs');
        Schema::dropIfExists('warehouse_transfer_items'); Schema::dropIfExists('warehouse_transfers');
        Schema::dropIfExists('supply_items'); Schema::dropIfExists('supplies');
        Schema::dropIfExists('write_off_reasons'); Schema::dropIfExists('warehouse_product');
        Schema::dropIfExists('suppliers'); Schema::dropIfExists('warehouses');
    }
};