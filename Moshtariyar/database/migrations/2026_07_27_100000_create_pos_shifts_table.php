<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_shifts')) {
            Schema::create('pos_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
                $table->decimal('opening_cash', 15, 2)->default(0);
                $table->decimal('closing_cash', 15, 2)->default(0);
                $table->decimal('cash_sales', 15, 2)->default(0);
                $table->decimal('card_sales', 15, 2)->default(0);
                $table->decimal('transfer_sales', 15, 2)->default(0);
                $table->decimal('total_sales', 15, 2)->default(0);
                $table->decimal('total_discount', 15, 2)->default(0);
                $table->integer('orders_count')->default(0);
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'status']);
                $table->index(['user_id', 'opened_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_shifts');
    }
};