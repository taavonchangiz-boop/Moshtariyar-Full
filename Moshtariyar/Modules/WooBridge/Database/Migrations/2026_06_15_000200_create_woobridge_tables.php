<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اتصال هر فروشگاه ووکامرس (پشتیبانی چند فروشگاه)
        Schema::create('wb_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('store_url');
            $table->text('consumer_key')->nullable();     // رمزنگاری‌شده (Crypt)
            $table->text('consumer_secret')->nullable();   // رمزنگاری‌شده
            $table->string('webhook_secret');              // برای تأیید HMAC
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        // نگاشت شناسه woo_id ↔ crm_id (قلب منطق ضدتکرار و sync دوطرفه)
        Schema::create('wb_id_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('wb_connections')->cascadeOnDelete();
            $table->enum('entity', ['customer', 'order', 'product']);
            $table->unsignedBigInteger('woo_id');
            $table->unsignedBigInteger('crm_id');
            $table->timestamps();

            $table->unique(['connection_id', 'entity', 'woo_id'], 'uniq_woo_map');
            $table->index(['entity', 'crm_id'], 'idx_crm_map');
        });

        // نگاشت فیلد دلخواه
        Schema::create('wb_field_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('wb_connections')->cascadeOnDelete();
            $table->string('entity', 20);
            $table->string('woo_field', 120);
            $table->string('crm_field', 120);
            $table->boolean('is_unique_key')->default(false); // کلید یکتا برای upsert
        });

        // لاگ همگام‌سازی + امکان resend
        Schema::create('wb_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->nullable()->constrained('wb_connections')->nullOnDelete();
            $table->string('entity', 20);
            $table->unsignedBigInteger('woo_id')->nullable();
            $table->string('delivery_id')->nullable();       // برای idempotency
            $table->enum('direction', ['in', 'out'])->default('in');
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('delivery_id');
        });

        // سبد خرید رهاشده
        Schema::create('wb_abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->nullable()->constrained('wb_connections')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->json('cart')->nullable();
            $table->decimal('value', 15, 2)->default(0);
            $table->boolean('recovered')->default(false);
            $table->boolean('notified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_abandoned_carts');
        Schema::dropIfExists('wb_sync_logs');
        Schema::dropIfExists('wb_field_maps');
        Schema::dropIfExists('wb_id_map');
        Schema::dropIfExists('wb_connections');
    }
};
