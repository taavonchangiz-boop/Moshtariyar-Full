<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('full_name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('national_id', 20)->nullable();      // کد ملی / شناسه ملی
            $table->string('economic_code', 20)->nullable();    // کد اقتصادی
            $table->string('source', 50)->nullable();           // woocommerce/form/manual
            $table->decimal('lifetime_value', 15, 2)->default(0); // CLV
            $table->json('tags')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('email', 'uniq_customer_email');
            $table->unique('phone', 'uniq_customer_phone');
            $table->index('source');
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['billing', 'shipping'])->default('shipping');
            $table->string('province', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('color', 20)->default('#888');
            $table->unsignedInteger('order')->default(0);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('source', 50)->nullable();
            $table->foreignId('status_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->integer('score')->default(0);                // lead scoring
            $table->decimal('value', 15, 2)->default(0);
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamps();
            $table->index('source');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 80)->nullable();
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->integer('stock')->nullable();
            $table->timestamps();
            $table->unique('sku', 'uniq_product_sku');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('number', 50)->nullable();
            $table->string('status', 40)->default('pending');
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->string('currency', 10)->default('IRT');
            $table->string('source', 50)->default('woocommerce');
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('sku', 80)->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_statuses');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
