<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فاکتور رسمی (مطابق استاندارد سامانه مودیان)
        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // شناسه‌ها
            $table->string('serial', 64)->nullable();            // شمارهٔ سریال داخلی
            $table->string('tax_id', 64)->nullable();            // شناسهٔ یکتای مالیاتی (۲۲ رقمی)
            $table->string('reference_number', 64)->nullable();  // شمارهٔ مرجع از سامانه

            // نوع و الگوی صورت‌حساب
            $table->unsignedTinyInteger('invoice_type')->default(1);    // 1=فروش
            $table->unsignedTinyInteger('invoice_pattern')->default(1); // 1=فروش، ...
            $table->unsignedTinyInteger('settlement_type')->default(1); // 1=نقدی، 2=نسیه، 3=نقدی/نسیه

            // مبالغ (ریال)
            $table->decimal('total_amount', 18, 0)->default(0);
            $table->decimal('discount', 18, 0)->default(0);
            $table->decimal('vat_amount', 18, 0)->default(0);    // مالیات بر ارزش افزوده
            $table->decimal('payable', 18, 0)->default(0);

            // وضعیت ارسال به سامانه
            $table->enum('status', ['draft', 'queued', 'sent', 'confirmed', 'rejected', 'failed'])
                  ->default('draft');
            $table->json('items')->nullable();        // اقلام به فرمت مودیان
            $table->json('response')->nullable();     // پاسخ سامانه
            $table->text('error')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('tax_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
    }
};
