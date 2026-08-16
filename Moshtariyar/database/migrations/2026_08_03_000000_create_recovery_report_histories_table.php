<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_report_histories', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('j_today', 32)->nullable()->comment('تاریخ امروز شمسی - مثل ۱۴۰۴/۰۵/۱۳');
            $table->string('j_from', 32)->nullable()->comment('شروع بازه شمسی');
            $table->string('j_to', 32)->nullable()->comment('پایان بازه شمسی');
            $table->unsignedInteger('business_id')->nullable()->index();

            $table->unsignedInteger('recovered_7d')->default(0)->comment('تعداد بازگشته این هفته');
            $table->unsignedBigInteger('recovered_revenue_7d')->default(0)->comment('درآمد بازگشتی این هفته');
            $table->unsignedInteger('recovered_30d')->default(0)->comment('بازگشته ۳۰ روزه');
            $table->unsignedBigInteger('recovered_revenue_30d')->default(0);
            $table->unsignedTinyInteger('recovery_rate')->default(0);
            $table->unsignedInteger('total_at_risk')->default(0);
            $table->unsignedBigInteger('total_recovered_12m')->default(0)->comment('کل ۱۲ ماه اخیر');
            $table->unsignedInteger('total_count_12m')->default(0);

            $table->json('monthly_labels')->nullable()->comment('برچسب ماه‌های شمسی یونیک مثل مرداد ۱۴۰۴');
            $table->json('monthly_recovered')->nullable()->comment('مبالغ ماهانه');
            $table->json('monthly_count')->nullable()->comment('تعداد ماهانه');
            $table->json('recovered_list')->nullable()->comment('لیست ۵ برتر');
            $table->json('emails_sent')->nullable()->comment('ایمیل‌هایی که بهشان ارسال شد');

            $table->text('summary')->nullable()->comment('خلاصه متنی برای activities');
            $table->longText('insights_json')->nullable()->comment('json کامل reportData');

            $table->timestamps();
            $table->index(['report_date', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_report_histories');
    }
};