<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('gateway', 30);                 // zarinpal / zibal
            $table->unsignedBigInteger('amount');          // ریال
            $table->string('authority', 191)->nullable();  // authority/trackId درگاه
            $table->string('ref_id', 191)->nullable();     // کد رهگیری پس از تأیید
            $table->enum('status', ['pending', 'paid', 'failed', 'canceled'])->default('pending');
            $table->string('description', 191)->nullable();
            $table->string('mobile', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('authority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
