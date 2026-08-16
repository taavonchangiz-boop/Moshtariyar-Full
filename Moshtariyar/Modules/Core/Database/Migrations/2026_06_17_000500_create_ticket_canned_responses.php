<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('department', 60)->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['department', 'is_active']);
        });

        DB::table('ticket_canned_responses')->insert([
            ['title'=>'در حال بررسی','department'=>'پشتیبانی','body'=>'سلام. درخواست شما دریافت شد و در حال بررسی است. نتیجه بررسی از همین تیکت اطلاع‌رسانی می‌شود.','is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'درخواست اطلاعات بیشتر','department'=>'پشتیبانی','body'=>'سلام. برای بررسی دقیق‌تر، لطفاً اطلاعات/تصویر/شماره سفارش مرتبط را ارسال کنید.','is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'پیگیری پرداخت','department'=>'مالی','body'=>'سلام. لطفاً شماره سفارش، مبلغ پرداختی، تاریخ پرداخت و کد پیگیری بانکی را ارسال کنید تا بررسی شود.','is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'پاسخ فروش','department'=>'فروش','body'=>'سلام. ممنون از پیام شما. همکاران فروش در کوتاه‌ترین زمان اطلاعات تکمیلی را ارسال خواهند کرد.','is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['title'=>'مشکل فنی','department'=>'فنی','body'=>'سلام. موضوع به تیم فنی ارجاع شد. لطفاً در صورت امکان تصویر خطا و مراحل ایجاد مشکل را ارسال کنید.','is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_canned_responses');
    }
};
