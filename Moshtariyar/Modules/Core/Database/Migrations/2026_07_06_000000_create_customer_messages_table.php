<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * اجرای عملیات ساخت جدول
     */
    public function up(): void
    {
        Schema::create('customer_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id'); // مشتری دریافت کننده
            $table->unsignedBigInteger('user_id');     // کارمند ارسال کننده
            $table->string('channel');                 // sms, email, messenger
            $table->text('message');                   // متن پیام
            $table->timestamps();                      // تاریخ ساخت و ویرایش

            // ایجاد ارتباط بین جدول‌ها برای سرعت بیشتر در جستجو
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * بازگرداندن تغییرات در صورت نیاز
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_messages');
    }
};